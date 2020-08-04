<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Deposito\EnderecoRepository;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Filial;
use Wms\Domain\Entity\FilialRepository;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository;
use Wms\Math;
use Wms\Util\WMS_Exception;

class EtiquetaSeparacaoRepository extends EntityRepository
{

    public $qtdIteracoesMapa = 0;
    public $qtdIteracoesMapaProduto = 0;
    private $mapas = array();

    /**
     * @param $idPedido Código interno do pedido
     * @param int $status
     * @return array
     */
    public function  getEtiquetasByPedido ($idPedido, $status = null)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(' es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->where('es.codEntrega = :idPedido')
            ->setParameter('idPedido', $idPedido);

        if ($status != null) {
            $dql->andWhere('es.codStatus = :Status')
                ->setParameter('Status', $status);
        }

        return $dql->getQuery()->getResult();
    }

    /**
     * @param $idPedido
     * @return array
     */
    public function getMapaByPedido($idPedido)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id pedido,  ms.id mapaSeparacao')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('wms:Expedicao\PedidoProduto', 'pp', 'WITH', 'pp.pedido = p.id')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.codPedidoProduto = pp.id')
            ->innerJoin('wms:Expedicao\MapaSeparacao', 'ms', 'WITH', 'ms.id = msp.mapaSeparacao')
            ->innerJoin('wms:Expedicao\MapaSeparacaoConferencia', 'msc', 'WITH', 'msc.mapaSeparacao = ms.id')
            ->where("p.id = $idPedido");

        return $sql->getQuery()->getResult();
    }

    /**
     * @param $status
     * @param $idExpedicao
     * @return mixed
     */
    public function countByStatus ($status = null, $expedicaoEn = null, $centralEntrega = null, $placaCarga = null, $idCarga = null)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(es.codBarras)')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
            ->where('es.codExpedicao = :idExpedicao')
            ->setParameter('idExpedicao', $expedicaoEn->getId());

        if ($status != null) {
            $dql->andWhere('es.codStatus = :Status')
                ->setParameter('Status', $status);
        }

        if ($centralEntrega != NULL) {
            if ($expedicaoEn->getStatus()->getId() == Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
                $dql->andWhere('es.pontoTransbordo = :centralEntrega');
            } else {
                $dql->andWhere('es.codEstoque = :centralEntrega');
            }
            $dql->setParameter('centralEntrega', $centralEntrega);
        }

        if ($placaCarga != NULL) {
            $dql->andWhere('c.placaCarga = :placaCarga')
                ->setParameter('placaCarga', $placaCarga);
        }

        if ($idCarga != NULL) {
            $dql->andWhere('c.id = :idCarga')
                ->setParameter('idCarga', $idCarga);
        }

        return $dql->getQuery()->getSingleScalarResult();
    }

    public function countByPontoTransbordo ($status, $idExpedicao, $centralEntrega = null, $placaCarga = null, $codCargaExterno = null)
    {

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(es.codBarras)')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
            ->where('es.codExpedicao = :idExpedicao')
            ->andWhere('es.codStatus = :Status')
            ->andWhere('es.pontoTransbordo = :centralEntrega')
            ->andWhere('es.codStatus != ' . EtiquetaSeparacao::STATUS_PENDENTE_CORTE )
            ->andWhere('es.codStatus != ' . EtiquetaSeparacao::STATUS_CORTADO )
            ->setParameter('idExpedicao', $idExpedicao)
            ->setParameter('Status', $status)
            ->setParameter('centralEntrega', $centralEntrega);

        if ($placaCarga != NULL) {
            $dql->andWhere('c.placaCarga = :placaCarga')
                ->setParameter('placaCarga', $placaCarga);
        }

        if ($codCargaExterno != NULL) {
            $dql->andWhere('c.codCargaExterno = :codCargaExterno')
                ->setParameter('codCargaExterno', $codCargaExterno);
        }

        return $dql->getQuery()->getSingleScalarResult();
    }


    public function getCountGroupByCentralPlaca ($idExpedicao)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('count(distinct es.id) as qtdEtiqueta,
                      c.placaCarga, ped.pontoTransbordo, c.codCargaExterno, c.sequencia,c.id as carga,
                      sum(pv.cubagem) as cubagem,
                      sum(pv.peso) as peso,
                      count(distinct nfs.id) as qtdNotas,
                      count(distinct ree.id) as qtdEntregas')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin('es.pedido', 'ped')
            ->innerJoin('ped.carga', 'c')
            ->innerJoin('c.expedicao', 'exp')
            ->innerJoin('wms:Expedicao\PedidoProduto', 'pp', 'WITH', 'pp.codPedido = ped.id')
            ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'pv.codProduto = pp.codProduto AND pv.grade = pp.grade')
            ->leftJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsp', 'WITH', 'nfsp.pedido = ped.id')
            ->leftJoin('nfsp.notaFiscalSaida', 'nfs')
            ->leftJoin('wms:Expedicao\Reentrega', 'ree', 'WITH', 'ree.notaFiscalSaida = nfs.id')
            ->where('exp.id = :idExpedicao')
            ->andWhere('es.codStatus != ' . EtiquetaSeparacao::STATUS_PENDENTE_CORTE)
            ->andWhere('es.codStatus != ' . EtiquetaSeparacao::STATUS_CORTADO)
            ->groupBy('c.placaCarga, c.codCargaExterno, c.sequencia, c.id')
            ->addGroupBy('ped.pontoTransbordo')
            ->setParameter('idExpedicao', $idExpedicao)
            ->orderBy('c.placaCarga, c.sequencia');

        return $dql->getQuery()->getArrayResult();
    }

    public function getEtiquetasByStatus($status, $idExpedicao)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('es.codBarras,
                      es.cliente,
                      es.produto,
                      es.grade,
                      es.dthConferencia')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->where('es.codExpedicao = :idExpedicao')
            ->setParameter('idExpedicao', $idExpedicao);


        if ($status != null) {
            $dql->andWhere('es.codStatus = :Status')
                ->setParameter('Status', $status);
        }
        return $dql->getQuery()->getResult();
    }

    /**
     * @param $status
     * @param $idEtiqueta
     * @return array
     */
    public function getByStatus($idEtiqueta, $status)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('es.codBarras, es.cliente, es.produto, es.grade, es.dthConferencia')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->where('es.codBarras = :idEtiqueta')
            ->setParameter('idEtiqueta', $idEtiqueta);

        if (is_array($status)) {
            $status = implode(',',$status);
            $dql->andWhere("es.codStatus in ($status)");
        }else if ($status) {
            $dql->andWhere("es.codStatus = :Status")
                ->setParameter('Status', $status);
        }

        return $dql->getQuery()->getResult();
    }

    public function getPendenciasByExpedicaoAndStatus($idExpedicao, $status, $tipoResult = "Array", $placaCarga = NULL, $transbordo = NULL, $embalado = NULL, $carga = NULL) {

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("es.codBarras,
                      es.cliente,
                      es.codProduto,
                      es.produto,
                      es.codCargaExterno,
                      es.grade,
                      es.codEstoque,
                      de.descricao as endereco,
                      es.pontoTransbordo,
                      ped.codExterno pedido,
                      CASE WHEN es.codStatus = 522 THEN 'PENDENTE DE IMPRESSÃO'
                           WHEN es.codStatus = 523 THEN 'PENDENTE DE CONFERENCIA'
                           ELSE 'Consulte o administrador do sistema'
                      END as pendencia,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      etq.dataConferencia")
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
            ->innerJoin('etq.pedido','ped')
            ->leftJoin('etq.produto','p')
            ->leftJoin('etq.produtoEmbalagem','emb')
            ->leftJoin('etq.produtoVolume','vol')
            ->leftjoin('wms:Deposito\Endereco','de','WITH','etq.codDepositoEndereco = de.id')
            ->where('es.codExpedicao = :idExpedicao')
            ->andWhere('es.codStatus IN (' . $status . ')');

        if (!is_null($placaCarga)) {
            $dql->andwhere("es.placaCarga = '$placaCarga'");
        }

        if (!is_null($carga)) {
            $dql->andwhere("es.codCargaExterno = '$carga'");
        }

        if (!is_null($embalado)) {
            if ($embalado == "S") {
                $dql->andwhere("emb.embalado = 'S'");
            }
            if ($embalado == "N") {
                $dql->andwhere("(emb.embalado = 'N' OR emb.embalado IS NULL)");
            }
        }

        if (!is_null($transbordo)) {
            $dql->andWhere("es.pontoTransbordo = $transbordo");
        }

        $dql->setParameter('idExpedicao', $idExpedicao)
            ->orderBy('ped.codExterno, es.codCargaExterno, es.codBarras, p.descricao, es.codProduto, es.grade');

        $expedicaoEn = $this->getEntityManager()->getRepository("wms:Expedicao")->findOneBy(array('id'=>$idExpedicao));
        if ($expedicaoEn->getStatus()->getId() == Expedicao::STATUS_SEGUNDA_CONFERENCIA) {
            $dql->leftJoin("wms:Expedicao\EtiquetaConferencia",'ec','WITH','ec.codEtiquetaSeparacao = es.codBarras');
            $dql->andWhere("ec.status = " . Expedicao::STATUS_PRIMEIRA_CONFERENCIA);
        }

        if ($tipoResult == "Array") {
            $result = $dql->getQuery()->getResult();
        } else {
            $result = $dql;
        }
        return $result;
    }

    public function getEtiquetasByExpedicao($idExpedicao = null, $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $pontoTransbordo = null, $idEtiquetas = null, $idEtiquetaMae = null, $reentrega = false)
    {

        if ($reentrega == true) {
            $origemEstoque = 'es.pontoTransbordo as codEstoque';
        } else {
            $origemEstoque = 'es.codEstoque as codEstoque';
        }

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("etq.id, p.codExterno as codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.lote, es.fornecedor, es.tipoComercializacao, es.linhaSeparacao, $origemEstoque,  es.codExpedicao,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, etq.codEtiquetaMae, es.posVolume, es.posEntrega, es.totalEntrega,
                    IDENTITY(etq.produtoEmbalagem) as codProdutoEmbalagem, etq.qtdProduto, p.id pedido, de.descricao endereco, c.sequencia, 
                    p.sequencia as sequenciaPedido, NVL(pe.quantidade,1) as quantidade, etq.tipoSaida, c.placaExpedicao, p.numSequencial, de.idCaracteristica,
                    cl.id as codCliente, r.numSeq seqRota, r.nomeRota, pr.numSeq seqPraca, pr.nomePraca, NVL(b.descricao, 'N/D') dscBox, uf.referencia siglaEstado, 
                    pedEnd.localidade as cidadeEntrega, pedEnd.descricao ruaEntrega, pedEnd.numero numeroEntrega
                ")
            ->addSelect("
                        (
                            SELECT COUNT(et.id)
                            FROM wms:Expedicao\EtiquetaSeparacao et
                            INNER JOIN wms:Expedicao\Pedido pedi WITH pedi.id = et.pedido
                            INNER JOIN wms:Expedicao\Carga carg WITH carg.id = pedi.codCarga
                            INNER JOIN wms:Deposito\Endereco ender WITH ender.id = et.depositoEndereco
                            WHERE et.codProduto = es.codProduto AND et.dscGrade = es.grade AND es.codExpedicao = carg.codExpedicao
                                AND ender.idCaracteristica = de.idCaracteristica
                        )
                         AS qtdProdDist
                        ")
            ->addSelect("
                        (
                            SELECT COUNT(sep.id)
                            FROM wms:Expedicao\Carga carga
                            INNER JOIN wms:Expedicao\Pedido ped WITH ped.codCarga = carga.id
                            INNER JOIN wms:Expedicao\EtiquetaSeparacao sep WITH sep.pedido = ped.id
                            WHERE es.codCargaExterno = carga.codCargaExterno
                        )
                         AS qtdCargaDist
                        ")
            ->addSelect("
                        (
                            SELECT COUNT(etiqueta.codBarras) 
                            FROM wms:Expedicao\VEtiquetaSeparacao etiqueta
                            WHERE etiqueta.codExpedicao = es.codExpedicao AND es.codClienteExterno = etiqueta.codClienteExterno
                            GROUP BY etiqueta.codClienteExterno
                        ) AS qtdEtiquetaCliente
                        ")
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('wms:Expedicao', 'e', "WITH", "es.codExpedicao = e.id")
            ->leftJoin("e.box", "b")
            ->innerJoin('p.pessoa', 'cl')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq' , 'WITH', 'etq.id = es.codBarras')
            ->leftJoin("cl.rota", "r")
            ->leftJoin("cl.praca", "pr")
            ->leftJoin('wms:Expedicao\EtiquetaMae', 'em', 'WITH', 'em.id = etq.etiquetaMae')
            ->leftJoin('wms:Produto\Embalagem','pe','WITH','pe.id = etq.produtoEmbalagem')
            ->leftJoin(PedidoEndereco::class, 'pedEnd', 'WITH', 'pedEnd.pedido = p')
            ->leftjoin('etq.codDepositoEndereco', 'de')
            ->leftJoin('pedEnd.uf', 'uf');



        if ($reentrega == true) {
            $dql->innerJoin('etq.reentrega','rtg')
                ->innerJoin('rtg.carga','c');
        } else {
            $dql->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga');
        }

        $dql->distinct(true);

        if (isset($idEtiquetaMae) && !empty($idEtiquetaMae)) {
            $dql->andWhere("em.id IN ($idEtiquetaMae)");
        }

        if ($idExpedicao != null) {
            $dql->andWhere('es.codExpedicao = :idExpedicao')
                ->setParameter('idExpedicao', $idExpedicao);
        }

        if ($status != null) {
            $dql->andWhere('es.codStatus = :Status')
                ->setParameter('Status', $status);
        }

        if ($idEtiquetas != null) {
            $dql->andWhere('etq.id IN (' . $idEtiquetas .')');
        }

        if ($pontoTransbordo != null) {
            $expedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
            $expedicaoEntity = $expedicaoRepo->find($idExpedicao);

            if ($expedicaoEntity->getStatus()->getId() == Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
                $dql->andWhere('p.pontoTransbordo = :pontoTransbordo');
            } else {
                $dql->andWhere('es.codEstoque = :pontoTransbordo');
            }

            $dql->setParameter('pontoTransbordo', $pontoTransbordo);
        }

        $sequencia = $this->getSystemParameterValue("SEQUENCIA_ETIQUETA_SEPARACAO");
        switch ($sequencia) {
            case 2:
                $dql->orderBy("es.codBarras","DESC");
                break;
            default:
                $dql->orderBy("es.codBarras");
        }
        $result = $dql->getQuery()->getResult();
        foreach ($result as $key => $value){
            if(!empty($value['numSequencial']) && $value['numSequencial'] > 1){
                $result[$key]['codEntrega'] = $value['codEntrega'].' - '.$value['numSequencial'];
            }
        }
        return $result;

    }

    public function getEtiquetasReimpressaoByFaixa($codigoInicial, $codigoFinal)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(' p.codExterno as codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao, es.posVolume, es.posEntrega, es.totalEntrega, etq.codStatus,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, p.id pedido, IDENTITY(etq.produtoEmbalagem) AS codProdutoEmbalagem, 
                    etq.qtdProduto, r.numSeq seqRota, r.nomeRota, pr.numSeq seqPraca, pr.nomePraca, NVL(b.descricao, \'N/D\') dscBox, uf.referencia siglaEstado, NVL(pe.quantidade,1) as quantidade, 
                    pedEnd.localidade as cidadeEntrega, pedEnd.descricao ruaEntrega, pedEnd.numero numeroEntrega')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao', 'e', "WITH", "es.codExpedicao = e.id")
            ->leftJoin("e.box", "b")
            ->leftJoin('wms:Expedicao\EtiquetaSeparacao','etq','WITH','etq.id = es.codBarras')
            ->leftJoin('wms:Produto\Embalagem','pe','WITH','pe.id = etq.produtoEmbalagem')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('p.pessoa', 'cl')
            ->leftJoin(PedidoEndereco::class, 'pedEnd', 'WITH', 'pedEnd.pedido = p')
            ->leftJoin('pedEnd.uf', 'uf')
            ->leftJoin("cl.rota", "r")
            ->leftJoin("cl.praca", "pr")
            ->andWhere('etq.id >= '.$codigoInicial)
            ->andWhere('etq.id <= '.$codigoFinal)
            ->andWhere('etq.reimpressao IS NULL')
            ->orderBy("es.codBarras","DESC");

        $result = $dql->getQuery()->getResult();
        return $result;

    }


    /**
     * @param $idExpedicao
     * @param $idEtiqueta
     * @return array
     */
    public function getEtiquetaByExpedicaoAndId($idEtiqueta)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(" es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.codStatus, s.sigla status, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.placaCarga, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, es.pontoTransbordo,
                    emb.embalado, es.posVolume, es.posEntrega, es.totalEntrega, pedEnd.localidade as cidadeEntrega, uf.referencia siglaEstado,
                    exp.id as reentregaExpedicao, pedEnd.descricao ruaEntrega, pedEnd.numero numeroEntrega,
                    r.id as codReentrega,
                    CASE WHEN emb.descricao    IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                    CASE WHEN emb.CBInterno    IS NULL THEN vol.CBInterno ELSE emb.CBInterno END as CBInterno,
                    CASE WHEN emb.codigoBarras IS NULL THEN vol.codigoBarras ELSE emb2.codigoBarras END as codBarrasProduto
                ")
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Util\Sigla', 's', 'WITH', 'es.codStatus = s.id')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
            ->leftJoin('etq.reentrega','r')
            ->leftJoin(PedidoEndereco::class, 'pedEnd', 'WITH', 'pedEnd.pedido = etq.pedido')
            ->leftJoin('wms:Util\Sigla', 'uf', 'WITH', 'uf.id = pedEnd.uf')
            ->leftJoin('r.carga','c')
            ->leftJoin('c.expedicao','exp')
            ->leftJoin('etq.produtoEmbalagem','emb')
            ->leftJoin('wms:Produto\Embalagem','emb2', 'WITH', 'emb.codProduto = emb2.codProduto
                                                            AND emb.quantidade = emb2.quantidade
                                                            AND emb.grade      = emb2.grade')
            ->leftJoin('etq.produtoVolume','vol')
            ->where('es.codBarras = :codBarras')
            ->setParameter('codBarras', $idEtiqueta);

        $result = $dql->getQuery()->getArrayResult();
        return $result;
    }

    public function getExpedicaoByEtiqueta ($idEtiqueta)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('es.codExpedicao')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->where('es.codBarras = :codBarras')
            ->setParameter('codBarras', $idEtiqueta);

        $result =$dql->getQuery()->getOneOrNullResult();
        if ($result != null) {
            return $result['codExpedicao'];
        }
        return false;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getEtiquetaById($id)
    {

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(' p.codExterno as codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao, es.posVolume, es.posEntrega, es.totalEntrega,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, es.codBarrasProduto, c.sequencia, p.id pedido,
					IDENTITY(etq.produtoEmbalagem) as codProdutoEmbalagem, etq.qtdProduto, NVL(pe.quantidade,1) as quantidade, etq.tipoSaida, p.numSequencial,
					de.descricao endereco, de.idCaracteristica, r.numSeq seqRota, r.nomeRota, pr.numSeq seqPraca, pr.nomePraca, uf.referencia siglaEstado, 
					pedEnd.localidade as cidadeEntrega, pedEnd.descricao ruaEntrega, pedEnd.numero numeroEntrega
                ')
            ->addSelect("
                        (
                            SELECT COUNT(et.id)
                            FROM wms:Expedicao\EtiquetaSeparacao et
                            INNER JOIN wms:Expedicao\Pedido pedi WITH pedi.id = et.pedido
                            INNER JOIN wms:Expedicao\Carga carg WITH carg.id = pedi.codCarga
                            LEFT JOIN wms:Deposito\Endereco ender WITH ender.id = et.depositoEndereco                            
                            WHERE et.codProduto = es.codProduto AND et.dscGrade = es.grade AND es.codExpedicao = carg.codExpedicao
                                AND ender.idCaracteristica = de.idCaracteristica
                        )
                         AS qtdProdDist
                        ")
            ->addSelect("
                        (
                            SELECT COUNT(sep.id)
                            FROM wms:Expedicao\Carga carga
                            INNER JOIN wms:Expedicao\Pedido ped WITH ped.codCarga = carga.id
                            INNER JOIN wms:Expedicao\EtiquetaSeparacao sep WITH sep.pedido = ped.id
                            WHERE es.codCargaExterno = carga.codCargaExterno
                        )
                         AS qtdCargaDist
                        ")
            ->addSelect("
                        (
                            SELECT COUNT(etiqueta.codBarras) 
                            FROM wms:Expedicao\VEtiquetaSeparacao etiqueta
                            WHERE etiqueta.codExpedicao = es.codExpedicao AND es.codClienteExterno = etiqueta.codClienteExterno
                            GROUP BY etiqueta.codClienteExterno
                        ) AS qtdEtiquetaCliente
                        ")
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq' , 'WITH', 'etq.id = es.codBarras')
            ->innerJoin('p.pessoa', 'cl')
            ->leftJoin(PedidoEndereco::class, 'pedEnd', 'WITH', 'pedEnd.pedido = p')
            ->leftJoin('pedEnd.uf', 'uf')
            ->leftJoin("cl.rota", "r")
            ->leftJoin("cl.praca", "pr")
            ->leftJoin('wms:Produto\Embalagem','pe','WITH','pe.id = etq.produtoEmbalagem')
            ->leftJoin('etq.codDepositoEndereco', 'de')
            ->where('es.codBarras = :id')
            ->setParameter('id', $id);


        $result = $dql->getQuery()->getSingleResult();
        if(!empty($result['numSequencial']) && $result['numSequencial'] > 1){
            $result['codEntrega'] = $result['codEntrega'].' - '.$result['numSequencial'];
        }
        return $result;

    }

    /**
     * @param $codEtiqueta
     */
    public function efetivaImpressao($codEtiqueta, $centralEntregaPedido)
    {
        $em = $this->getEntityManager();
        $EsEntity = $this->find($codEtiqueta);
        $statusEntity = $em->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
        $EsEntity->setStatus($statusEntity);
        $em->persist($EsEntity);

    }

    public function savePosVolumeImpresso($idEtiqueta, $posVolume, $volEntrega, $totalEntrega)
    {
        $sql = "UPDATE ETIQUETA_SEPARACAO SET POS_VOLUME = $posVolume, POS_ENTREGA = $volEntrega, TOTAL_ENTREGA = $totalEntrega WHERE COD_ETIQUETA_SEPARACAO = $idEtiqueta";
        $this->_em->getConnection()->query($sql)->execute();
    }

    /**
     * @param array $dadosEtiqueta
     * @param int $statusEntity
     * @return EtiquetaSeparacao
     * @throws \Exception
     */
    protected function save(array $dadosEtiqueta, $statusEntity)
    {
        $enEtiquetaSeparacao = new EtiquetaSeparacao();
        $enEtiquetaSeparacao->setStatus($statusEntity);
        $enEtiquetaSeparacao->setDataGeracao(new \DateTime());

        if ( !empty($dadosEtiqueta['codEtiquetaMae']) ){
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaMae $EtiquetaMaeRepo */
            $EtiquetaMaeRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaMae');
            $etiquetaMae = $EtiquetaMaeRepo->find($dadosEtiqueta['codEtiquetaMae']);
            $enEtiquetaSeparacao->setEtiquetaMae($etiquetaMae);
        }

        \Zend\Stdlib\Configurator::configure($enEtiquetaSeparacao, $dadosEtiqueta);

        $this->_em->persist($enEtiquetaSeparacao);
        $enEtiquetaSeparacao->setId("10".$enEtiquetaSeparacao->getId());
        $this->_em->persist($enEtiquetaSeparacao);
        return $enEtiquetaSeparacao;
    }


    public function geraEtiquetaReentrega ($etiquetaSeparacanEn, $reentregaEn) {
        $statusReentrega = $this->_em->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO);

        $etiquetaSeparacanEn->setReentrega($reentregaEn);
        $etiquetaSeparacanEn->setCodReentrega($reentregaEn->getId());
        $this->getEntityManager()->persist($etiquetaSeparacanEn);

        $etiquetaReentrega = new EtiquetaSeparacaoReentrega();
        $etiquetaReentrega->setStatus($statusReentrega);
        $etiquetaReentrega->setCodStatus($statusReentrega->getId());
        $etiquetaReentrega->setEtiquetaSeparacao($etiquetaSeparacanEn);
        $etiquetaReentrega->setCodEtiquetaSeparacao($etiquetaSeparacanEn->getId());
        $etiquetaReentrega->setCodReentrega($reentregaEn->getId());
        $etiquetaReentrega->setReentrega($reentregaEn);
        $this->getEntityManager()->persist($etiquetaReentrega);
    }

    public function defineEtiquetaReentrega ($pedidos ,$codProduto, $grade, $qtdReentregar, $numReentrega, $arrayRepositorios) {
        $etiquetaRepo = $arrayRepositorios['etiquetaSeparacao'];
        $reentregaRepo = $arrayRepositorios['reentrega'];
        $qtdReentregue = $qtdReentregar;
        $reentregaEn = $reentregaRepo->findOneBy(array('id'=>$numReentrega));

        foreach ($pedidos as $pedido) {
            $codPedido = $pedido->getCodPedido();
            $etiquetas = $etiquetaRepo->findBy(array('pedido' => $codPedido,'codProduto' => $codProduto,'dscGrade' => $grade));
            foreach ($etiquetas as $etiqueta) {
                if ($etiqueta->getCodStatus() == EtiquetaSeparacao::STATUS_CORTADO) {continue;}
                if ($etiqueta->getCodReferencia() != null) {continue;}
                if ($qtdReentregue <= 0) {continue;}

                $this->geraEtiquetaReentrega($etiqueta,$reentregaEn);
                $qtdReentregue = $qtdReentregue - $etiqueta->getQtdProduto();
                $etiquetasReferentes = $etiquetaRepo->findBy(array('codReferencia'=>$etiqueta->getId()));
                foreach ($etiquetasReferentes as $etiquetaVolume) {
                    $this->geraEtiquetaReentrega($etiquetaVolume,$reentregaEn);
                }
            }
        }

        return $qtdReentregue;
    }

    public function geraMapaReentrega($produtoEntity, $quantidade, $expedicaoEntity, $arrayRepositorios, $arrMapaPedProd){

        if ($quantidade <= 0) return;

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $arrayRepositorios['modeloSeparacao'];

        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($expedicaoEntity->getId());
        $quebras = array(0=>array('tipoQuebra'=>MapaSeparacaoQuebra::QUEBRA_REENTREGA, 'dscQuebra' => 'MAPA DE REENTREGAS', 'codQuebra' => ''));

        $statusEntity = $this->_em->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO);
        $codProduto = $produtoEntity->getId();
        $grade = $produtoEntity->getGrade();

        if ($produtoEntity->getVolumes()->count() > 0) {
            $arrayVolumes = $produtoEntity->getVolumes()->toArray();

            usort($arrayVolumes, function ($a,$b){
                return $a->getCodigoSequencial() < $b->getCodigoSequencial();
            });

            foreach ($arrayVolumes as $volumeEntity) {
                $mapaSeparacao = $this->getMapaSeparacao($quebras, $statusEntity, $expedicaoEntity);
                $arrMapaPedProd = $this->salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,$quantidade,$volumeEntity,null,array(),null,null,null,$arrayRepositorios, null,null,$arrMapaPedProd);
            }

        }
        else if ($produtoEntity->getEmbalagens()->count() > 0) {
            $embalagensEn = $this->getEntityManager()->getRepository('wms:Produto\Embalagem')->findBy(array('codProduto'=>$codProduto,'grade'=>$grade),array('quantidade'=>'DESC'));

            $quantidadeRestantePedido = $quantidade;
            $menorEmbalagem = $embalagensEn[count($embalagensEn) -1];

            while ($quantidadeRestantePedido > 0) {
                $embalagemAtual = null;
                $quantidadeAtender = $quantidadeRestantePedido;

                if ($modeloSeparacaoEn->getUtilizaCaixaMaster() == "S") {
                    foreach ($embalagensEn as $embalagem) {
                        if (Math::compare($embalagem->getQuantidade(), $quantidadeAtender,"<=")) {
                            $embalagemAtual = $embalagem;
                            break;
                        }
//                        if (number_format($embalagem->getQuantidade(),3,'.','') <= number_format($quantidadeAtender,3,'.','')) {
//                            $embalagemAtual = $embalagem;
//                            break;
//                        }
                    }
                    if ($embalagemAtual == null) {
                        $mensagem = "Não existe embalagem para Atender o PRODUTO $codProduto GRADE $grade com a quantidade restante de $quantidadeAtender produtos";
                        throw new \Exception($mensagem);
                    }
                } else {
                    $embalagemAtual = $menorEmbalagem;
                }

                $quantidadeRestantePedido = Math::subtrair($quantidadeRestantePedido,$embalagemAtual->getQuantidade());

                $mapaSeparacao = $this->getMapaSeparacao($quebras, $statusEntity, $expedicaoEntity);
                $arrMapaPedProd = self::salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,1,null,$embalagemAtual, array(), null, null,null,null,null,null,$arrMapaPedProd);
                $this->getEntityManager()->flush();
            }

        }

    }

    public function gerarMapaEtiquetaReentrega($idExpedicao,$arrayRepositorios){
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
        $andamentoNFRepo = $arrayRepositorios['andamentoNf'];
        $reentregaRepo   = $arrayRepositorios['reentrega'];
        $nfProdutoRepo   = $arrayRepositorios['nfPedido'];
        $nfSaidaRepo     = $arrayRepositorios['nfSaida'];
        $expedicaoRepo   = $arrayRepositorios['expedicao'];
        $produtoRepo     = $arrayRepositorios['produto'];

        $this->iniciaMapaSeparacao($idExpedicao, EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO);

        $produtos = $reentregaRepo->getItemNotasByExpedicao($idExpedicao);
        $expedicaoEn = $expedicaoRepo->find($idExpedicao);
        $gerouReentrega = false;
        foreach ($produtos as $produto) {
            $numNF = $produto['COD_NOTA_FISCAL_SAIDA'];
            $qtdReentregue = $produto['QUANTIDADE'];
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            $numReentrega = $produto['COD_REENTREGA'];

            $produtoEn = $produtoRepo->findOneBy(array('id' =>$codProduto,'grade'=> $grade));
            $pedidos = $nfProdutoRepo->findBy(array('codNotaFiscalSaida'=>$numNF));

            $qtdMapa = $this->defineEtiquetaReentrega($pedidos,$codProduto,$grade,$qtdReentregue, $numReentrega, $arrayRepositorios);

            if ($qtdMapa > 0) {
                $this->geraMapaReentrega($produtoEn, $qtdMapa, $expedicaoEn, $arrayRepositorios);
            }

        }

        $reentregas = $reentregaRepo->getReentregasByExpedicao($idExpedicao);
        foreach ($reentregas as $reentrega) {
            $numNF = $reentrega['COD_NOTA_FISCAL_SAIDA'];
            $numReentrega = $reentrega['COD_REENTREGA'];
            $nfSaidaEn = $nfSaidaRepo->findOneBy(array('id'=>$numNF));

            $reentregaEn = $reentregaRepo->findOneBy(array('id'=>$numReentrega));
            $reentregaEn->setIndEtiquetaMapaGerado('S');
            $this->getEntityManager()->persist($reentregaEn);

            $andamentoNFRepo->save($nfSaidaEn, NotaFiscalSaida::REENTREGA_EM_SEPARACAO, false, $expedicaoEn, $reentregaEn);
        }

        $this->getEntityManager()->flush();
    }

    public function getCubagemPedidos(array $pedidosProdutos, $modeloSeparacaoEn)
    {
        /** @var \Wms\Domain\Entity\Produto\DadoLogisticoRepository $dadoLogisticoRepo */
        $dadoLogisticoRepo = $this->getEntityManager()->getRepository('wms:Produto\DadoLogistico');

        /** @var Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

        $cubagemPedido = array();
        /** @var PedidoProduto $pedidoProduto */
        foreach ($pedidosProdutos as $pedidoProduto) {
            $depositoEnderecoEn = null;
            $pedidoId           = $pedidoProduto->getPedido()->getId();
            $quantidade         = number_format($pedidoProduto->getQuantidade(),3,'.','') - number_format($pedidoProduto->getQtdCortada(),3,'.','');
            $codProduto         = $pedidoProduto->getProduto()->getId();
            $grade              = $pedidoProduto->getProduto()->getGrade();
            $quantidadeRestantePedido = $quantidade;

            $produtoEntity = $pedidoProduto->getProduto();

            $forcarEmbVenda = ($produtoEntity->getForcarEmbVenda() == 'S' || empty($produtoEntity->getForcarEmbVenda()) && $modeloSeparacaoEn->getForcarEmbVenda() == 'S');

            if($produtoEntity->getVolumes()->count() > 0) {
                continue;
            }

            $embVenda = null;
            $embalagensEn = null;
            $menorEmbalagem = null;
            $qtdEmbalagemPadraoRecebimento = 1;

            if ($forcarEmbVenda) {
                $embVenda = $embalagemRepo->findOneBy(['codProduto' => $codProduto, 'grade' => $grade, 'quantidade' => $pedidoProduto->getFatorEmbalagemVenda(), 'dataInativacao' => null]);
                if (empty($embVenda))
                    throw new \Exception("O item $codProduto grade $grade no pedido ". $pedidoProduto->getPedido()->getCodExterno().", exige fator de venda de '".$pedidoProduto->getFatorEmbalagemVenda()."', mas não foi encontrada embalagem ativa com esse fator!");

            } else {
                $embalagensEn = $produtoEntity->getEmbalagens()->filter(
                    function ($item) {
                        return is_null($item->getDataInativacao());
                    }
                )->toArray();

                usort($embalagensEn, function ($itemA, $itemB) {
                    return $itemA->getQuantidade() < $itemB->getQuantidade();
                });


                foreach ($embalagensEn as $embalagem) {
                    if ($embalagem->getIsPadrao() == "S") {
                        $qtdEmbalagemPadraoRecebimento = $embalagem->getQuantidade();
                        break;
                    }
                }
                if (!isset($embalagensEn[count($embalagensEn) - 1]) || empty($embalagensEn[count($embalagensEn) - 1])) {
                    $msg = "O produto $codProduto GRADE $grade não possui embalagens ativas!";
                    throw new WMS_Exception($msg);
                }
                $menorEmbalagem = $embalagensEn[count($embalagensEn) - 1];
            }
            $count = 0;
            while ($quantidadeRestantePedido > 0) {

                $count++;
                $quantidadeAtender = $quantidadeRestantePedido;

                /** @var Produto\Embalagem $embalagemAtual */
                $embalagemAtual = null;

                if ($forcarEmbVenda) {
                    $embalagemAtual = $embVenda;
                }
                elseif ($modeloSeparacaoEn->getUtilizaCaixaMaster() == "S") {
                    foreach ($embalagensEn as $embalagem) {

                        if (Math::compare($embalagem->getQuantidade(), $quantidadeAtender,"<=")) {
                            $embalagemAtual = $embalagem;
                            break;
                        }
                    }
                    if ($embalagemAtual == null) {
                        $msg = "Não existe embalagem para Atender o PRODUTO $codProduto GRADE $grade com a quantidade restante de $quantidadeAtender produtos";
                        throw new WMS_Exception($msg);
                    }
                } else {
                    $embalagemAtual = $menorEmbalagem;
                }

                if (!is_null($embalagemAtual->getDataInativacao()))
                    continue;

                $quantidadeRestantePedido = number_format($quantidadeRestantePedido,3,'.','') - number_format($embalagemAtual->getQuantidade(),3,'.','');

                if (isset($cubagemPedido[$pedidoId][$embalagemAtual->getId()])) {
                    continue;
                }

                $embalado = false;
                if ($modeloSeparacaoEn->getTipoDefaultEmbalado() == ModeloSeparacao::DEFAULT_EMBALADO_PRODUTO) {
                    if ($embalagemAtual->getEmbalado() == 'S') {
                        $embalado = true;
                    }
                } else {
                    if ($embalagemAtual->getQuantidade() < $qtdEmbalagemPadraoRecebimento) {
                        $embalado = true;
                    }
                }

                if ($embalado === true) {
                    $cubagemProduto = $this->tofloat($embalagemAtual->getCubagem());
                    if (empty($cubagemProduto)) {
                        $dadoLogisticoEn = $dadoLogisticoRepo->findOneBy(array('embalagem' => $embalagemAtual->getId()));
                        if (!empty($dadoLogisticoEn)) {
                            $numAltura       = $this->tofloat($dadoLogisticoEn->getAltura());
                            $numLargura      = $this->tofloat($dadoLogisticoEn->getLargura());
                            $numProfundidade = $this->tofloat($dadoLogisticoEn->getProfundidade());
                            $cubagemProduto  = $numAltura * $numLargura * $numProfundidade;
                        }
                    }

                    $cubagemProduto = $this->tofloat($cubagemProduto);
                    $cubagemProduto = (!empty($cubagemProduto)) ? $cubagemProduto : $this->tofloat('0.001');

                    $cubg = number_format(
                        Math::multiplicar(
                            $cubagemProduto, Math::dividir(
                                $quantidadeAtender, number_format(
                                    $embalagemAtual->getQuantidade(),3,'.','')
                            )
                        ),8);
                    $cubagemPedido[$pedidoId][$embalagemAtual->getId()] = $cubg;
                }
            }
        }

        return $cubagemPedido;
    }

    private function tofloat($num) {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
        );
    }

    /**
     * @param array $pedidosProdutos
     * @param int $status
     * @throws \Exception|WMS_Exception
     */

    public function gerarMapaEtiqueta($idExpedicao, array $pedidosProdutos, $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $idModeloSeparacao, $arrayRepositorios)
    {
        /** @var EnderecoRepository $depositoEnderecoRepo */
        $depositoEnderecoRepo = $arrayRepositorios['depositoEndereco'];
        /** @var FilialRepository $filialRepository */
        $filialRepository = $arrayRepositorios['filial'];
        /** @var ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $arrayRepositorios['modeloSeparacao'];
        /** @var EtiquetaConferenciaRepository $etiquetaConferenciaRepo */
        $etiquetaConferenciaRepo = $arrayRepositorios['etiquetaConferencia'];
        /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $arrayRepositorios['mapaSeparacaoProduto'];
        /** @var PedidoProdutoLoteRepository $pedProdLoteRepo */
        $pedProdLoteRepo = $this->getEntityManager()->getRepository("wms:Expedicao\PedidoProdutoLote");
        /** @var Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        /** @var \Wms\Domain\Entity\Produto\DadoLogisticoRepository $dadoLogisticoRepo */
        $dadoLogisticoRepo = $this->getEntityManager()->getRepository('wms:Produto\DadoLogistico');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');

        /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoRepo */
        if (isset($arrayRepositorios['expedicaoRepo'])) {
            $expedicaoRepo = $arrayRepositorios['expedicaoRepo'];
        } else {
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        }

        /** @var $pedidoProdutoRepo $pedidoProdutoRepo */
        if (isset($arrayRepositorios['pedidoProdutoRepo'])) {
            $pedidoProdutoRepo = $arrayRepositorios['pedidoProdutoRepo'];
        } else {
            $pedidoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\PedidoProduto");
        }
        /** @var ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->_em->getRepository("wms:Ressuprimento\ReservaEstoque");
        $verificaReentrega = $this->getSystemParameterValue('RECONFERENCIA_EXPEDICAO');


        $arrMapaPedProd = [];
        try {

            if ($this->getSystemParameterValue("COMPARA_PRODUTOS_EXPEDICAO_ERP") == "S") {
                $idPP = array();
                foreach ($pedidosProdutos as $pedidoProduto) {
                    $idPP[] = $pedidoProduto->getId();
                }

                $result  = $expedicaoRepo->validaConferenciaERP($idExpedicao);
                if (is_string($result)) {
                    throw new \Exception($result);
                }
                $enPP = array();
                foreach( $idPP as $id) {
                    $ppEn = $pedidoProdutoRepo->find($id);
                    $enPP[] = $ppEn;
                    $pedidosProdutos = $enPP;
                }
            }

            $this->iniciaMapaSeparacao($idExpedicao, EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO);

            if (empty($status)) {
                $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO;
            }
            $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);

            $expedicaoEntity = $this->getEntityManager()->find('wms:Expedicao',$idExpedicao);
            $modeloSeparacaoEn = $expedicaoEntity->getModeloSeparacao();
            if (empty($modeloSeparacaoEn)) {
                //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
                $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);
                $expedicaoEntity->setModeloSeparacao($modeloSeparacaoEn);
                $this->_em->persist($expedicaoEntity);
            }

            /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            if (empty($modeloSeparacaoEn))
                throw new \Exception("O modelo de separação $idModeloSeparacao não foi encontrado");

            $quebrasFracionado = $modeloSeparacaoRepo->getQuebraFracionado($idModeloSeparacao);
            $quebrasNaoFracionado = $modeloSeparacaoRepo->getQuebraNaoFracionado($idModeloSeparacao);
            $quebrasEmbalado = $modeloSeparacaoRepo->getQuebraEmbalado($idModeloSeparacao);
            $forcarEmbVendaDefault = $modeloSeparacaoEn->getForcarEmbVenda();
            $quebraUnidFracionavel = ($modeloSeparacaoEn->getQuebraUnidFracionavel() == 'S');

            $etiquetaMaePadrao = null;

            $this->qtdIteracoesMapa = 0;
            $this->qtdIteracoesMapaProduto = 0;
            $arrPedidos = array();
            $arrMapasEmbPP = array();
            $arrayEtiquetas = array();
            $arrEtqtSemControleEstoque = array();
            $arrEtqtPicking = array();
            $arrEtqtPulmaoDoca = array();
            $arrEtqtCrossDocking = array();
            $arrEtqtSeparacaoAerea = array();
            $codGrupo = 0;
            $expedicaoEntity = null;
            $quebras = array();

            /** @var PedidoProduto $pedidoProduto */
            foreach ($pedidosProdutos as $pedidoProduto) {
                $expedicaoEntity = $pedidoProduto->getPedido()->getCarga()->getExpedicao();

                /** @var \Wms\Domain\Entity\Expedicao\Pedido $pedidoEntity */
                $pedidoEntity = $pedidoProduto->getPedido();

                /** @var Produto $produtoEntity */
                $produtoEntity = $pedidoProduto->getProduto();

                $forcarEmbVenda = ($produtoEntity->getForcarEmbVenda() == 'S' || empty($produtoEntity->getForcarEmbVenda()) && $forcarEmbVendaDefault == 'S');

                /** @var Filial $filial */
                $filial = $filialRepository->findOneBy(array('codExterno' => $pedidoEntity->getCentralEntrega()));
                if ($filial == null) {
                    $msg = "Filial " . $pedidoProduto->getPedido()->getCentralEntrega() . " não encontrada";
                    throw new WMS_Exception($msg);
                }

                $reservas = [];
                if ($filial->getIndUtilizaRessuprimento() == "S") {
                    $reservas = $reservaEstoqueRepo->getReservasExpedicao($pedidoProduto);
                }
                else {
                    if ($produtoEntity->getIndControlaLote() == 'S') {
                        $qtdMax = Math::subtrair($pedidoProduto->getQuantidade(), $pedidoProduto->getQtdCortada());

                        /** @var PedidoProdutoLote[] $itensBylote */
                        $itensBylote = $pedProdLoteRepo->findBy(['pedidoProduto' => $pedidoProduto]);
                        foreach($itensBylote as $key => $item) {
                            $qtdItemLote = Math::subtrair($item->getQuantidade(), $item->getQtdCorte());
                            $reservas[] = [
                                'qtd' => $qtdItemLote,
                                'idEndereco' => null,
                                'quebraPulmaoDoca' => 'N',
                                'tipoSaida' => ReservaEstoqueExpedicao::SAIDA_SEM_CONTROLE_ESTOQUE,
                                'lote' => $item->getCodLote()
                            ];
                            $qtdMax = Math::subtrair($qtdMax, $qtdItemLote);
                        }

                        if (!empty($qtdMax)) {
                            $reservas[] = [
                                'qtd' => $qtdMax,
                                'idEndereco' => null,
                                'quebraPulmaoDoca' => 'N',
                                'tipoSaida' => ReservaEstoqueExpedicao::SAIDA_SEM_CONTROLE_ESTOQUE,
                                'lote' => Produto\Lote::LND
                            ];
                        }
                    }
                    else {
                        $reservas[] = [
                            'qtd' => Math::subtrair($pedidoProduto->getQuantidade(), $pedidoProduto->getQtdCortada()),
                            'idEndereco' => null,
                            'quebraPulmaoDoca' => 'N',
                            'tipoSaida' => ReservaEstoqueExpedicao::SAIDA_SEM_CONTROLE_ESTOQUE,
                            'lote' => Produto\Lote::LND
                        ];
                    }
                }

                if ($produtoEntity->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO) {
                    $tipoSeparacao = $modeloSeparacaoEn->getTipoSeparacaoNaoFracionado();
                    $arrayVolumes = $produtoEntity->getVolumes()->toArray();

                    usort($arrayVolumes, function ($a, $b) {
                        return $a->getCodigoSequencial() < $b->getCodigoSequencial();
                    });

                    if ($filial->getIndUtilizaRessuprimento() !== "S") {
                        $reserva = $reservas[0];
                        $novasReservas = [];
                        foreach($arrayVolumes as $volume) {
                            $reserva['codProdutoVolume'] = $volume->getId();
                            $novasReservas[] = $reserva;
                        }
                        $reservas = $novasReservas;
                    }

                    $arrVolumesReservas = self::regroupReservaVolumes($reservas, $arrayVolumes, $tipoSeparacao);

                    if ($tipoSeparacao == ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA) {

                        $enderecoAtual = null;
                        $tudoImpresso = false;

                        $utilizaEtiquetaMae = $modeloSeparacaoEn->getUtilizaEtiquetaMae();
                        if (($utilizaEtiquetaMae == "N") || (empty($utilizaEtiquetaMae))) {
                            if (empty($etiquetaMaePadrao)) {
                                $expedicaoEntity = $expedicaoRepo->findOneBy(array('id'=>$idExpedicao));
                                $quebras = array();
                                $etiquetaMaePadrao = $this->getEtiquetaMae(null, $quebras,$expedicaoEntity);
                            }
                            $etiquetaMae = $etiquetaMaePadrao;
                        } else {
                            $etiquetaMae = $this->getEtiquetaMae($pedidoProduto, $quebrasNaoFracionado);
                        }


                        $primeiroVolume = $arrayVolumes[0]->getId();
                        $ultimoVolume = $arrayVolumes[count($arrayVolumes)-1]->getId();
                        while(!$tudoImpresso) {
                            $idVolume = key($arrVolumesReservas);
                            $item = current($arrVolumesReservas);
                            $volumeEntity = $item['volumeEn'];

                            if ($idVolume == $primeiroVolume) {
                                $codGrupo = $codGrupo + 1;
                            }

                            foreach ($item['enderecos'] as $idEndereco => $endereco) {
                                $qtd = $endereco['qtd'];
                                if (empty($endereco['lote'])) {
                                    $lote = ($produtoEntity->getIndControlaLote() == "S") ? Produto\Lote::LND : Produto\Lote::NCL;
                                } else {
                                    $lote = $endereco['lote'];
                                }
                                if ($qtd > 0) {
                                    $depositoEnderecoEn = $endereco['enderecoEn'];

                                    $arrEtiqueta = array(
                                            'statusEntity' => $statusEntity,
                                            'produtoEntity' => $produtoEntity,
                                            'pedidoEntity' => $pedidoEntity,
                                            'quantidade' => 1,
                                            'volumeEntity' => $volumeEntity,
                                            'lote' => $lote,
                                            'embalagemEntity' => null,
                                            'etiquetaMae' => $etiquetaMae,
                                            'depositoEnderecoEn' => $depositoEnderecoEn,
                                            'verificaReentrega' => $verificaReentrega,
                                            'tipoSeparacao' => $endereco['tipoSaida'],
                                            'grupo' => $codGrupo
                                        );

                                    switch ($endereco['tipoSaida']) {
                                        case ReservaEstoqueExpedicao::SAIDA_SEM_CONTROLE_ESTOQUE:
                                            $arrEtqtSemControleEstoque[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_PICKING:
                                            $arrEtqtPicking[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_SEPARACAO_AEREA:
                                            $arrEtqtSeparacaoAerea[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_PULMAO_DOCA:
                                            $arrEtqtPulmaoDoca[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_CROSS_DOCKING:
                                            $arrEtqtCrossDocking[] = $arrEtiqueta;
                                            break;
                                    }

                                    $qtd--;
                                    $arrVolumesReservas[$idVolume]['enderecos'][$idEndereco]['qtd'] = $qtd;
                                    break;
                                } else {
                                    continue;
                                }
                            }

                            $tudoImpresso = true;
                            if ($idVolume == $ultimoVolume) {
                                foreach ($arrVolumesReservas as $codVolume => $el) {
                                    foreach ($el['enderecos'] as $endereco => $elementEndereco) {
                                        if ($elementEndereco['qtd'] > 0) {
                                            $tudoImpresso = false;
                                            break;
                                        }
                                    }
                                    if (!$tudoImpresso) {
                                        reset($arrVolumesReservas);
                                        break;
                                    }
                                }
                            } else {
                                $tudoImpresso = false;
                                next($arrVolumesReservas);
                            }
                        }
                    }
                    else {
                        foreach ($arrVolumesReservas as $elements) {
                            $depositoEnderecoEn = $elements['enderecoEn'];

                            foreach ($elements['volumes'] as $value) {
                                if (empty($value['lote'])) {
                                    $lote = ($produtoEntity->getIndControlaLote() == "S") ? Produto\Lote::LND : Produto\Lote::NCL;
                                } else {
                                    $lote = $value['lote'];
                                }
                                list($strQuebrasConcat, $arrQuebras) = self::getSetupQuebras($quebras, $pedidoProduto);
                                $mapaSeparacao = $this->getMapaSeparacao($arrQuebras, $statusEntity, $expedicaoEntity);
                                $arrMapaPedProd = self::salvaMapaSeparacaoProduto(
                                    $mapaSeparacao,
                                    $produtoEntity,
                                    $value['qtd'],
                                    $value['volumeEn'],
                                    null,
                                    array($pedidoProduto),
                                    $depositoEnderecoEn,
                                    null,
                                    $pedidoEntity,
                                    $arrayRepositorios,
                                    null,
                                    $lote, $arrMapaPedProd);
                            }
                        }
                    }
                }
                else if ($produtoEntity->getTipoComercializacao()->getId() == Produto::TIPO_UNITARIO) {
                    $codProduto = $produtoEntity->getId();
                    $grade = $produtoEntity->getGrade();

                    $qtdEmbalagemPadraoRecebimento = 1;

                    $depositoEnderecoEn = null;
                    $idEndereco = 0;
                    /** @var Produto\Embalagem $menorEmbalagem */
                    $menorEmbalagem = null;
                    /** @var Produto\Embalagem $embFracDefault */
                    $embFracDefault = null;
                    /** @var Produto\Embalagem $embExpDefault */
                    $embExpDefault = null;
                    /** @var Produto\Embalagem $embVenda */
                    $embVenda = null;
                    /** @var Produto\Embalagem[] $embalagensEn */
                    $embalagensEn = null;

                    if ($forcarEmbVenda) {
                        $embVenda = $embalagemRepo->findOneBy(['codProduto' => $codProduto, 'grade' => $grade, 'quantidade' => $pedidoProduto->getFatorEmbalagemVenda(), 'dataInativacao' => null]);
                        if (empty($embVenda))
                            throw new \Exception("O item $codProduto grade $grade no pedido ".$pedidoEntity->getCodExterno().", exige fator de venda de '".$pedidoProduto->getFatorEmbalagemVenda()."', mas não foi encontrada embalagem ativa com esse fator!");

                        $depositoEnderecoEn = $embVenda->getEndereco();
                    } else {
                        $embalagensEn = $produtoEntity->getEmbalagens()->filter(
                            function($item) {
                                return is_null($item->getDataInativacao());
                            }
                        )->toArray();

                        if (empty($embalagensEn)) {
                            throw new WMS_Exception("O produto $codProduto grade $grade não possui embalagens ativas!");
                        }

                        usort($embalagensEn,function ($itemA, $itemB) {
                            return $itemA->getQuantidade() < $itemB->getQuantidade();
                        });

                        /** @var Produto\Embalagem $embExpDefault */
                        $embsFiltered1 = array_filter($embalagensEn, function ($emb){
                            /** @var Produto\Embalagem $emb */
                            return ($emb->isEmbExpDefault() == "S");
                        });
                        $embExpDefault = reset($embsFiltered1);

                        if ($produtoEntity->getIndFracionavel() == 'S') {
                            $embsFiltered2 = array_filter($embalagensEn, function ($emb){
                                /** @var Produto\Embalagem $emb */
                                return ($emb->isEmbFracionavelDefault() == "S");
                            });
                            $embFracDefault = reset($embsFiltered2);
                            $depositoEnderecoEn = $embFracDefault->getEndereco();
                            $menorEmbalagem = $embFracDefault;

                        } else {
                            foreach ($embalagensEn as $embalagem) {
                                $endereco = $embalagem->getEndereco();
                                if (!empty($endereco)) {
                                    $depositoEnderecoEn = $endereco;
                                }
                                if ($embalagem->getIsPadrao() == "S") {
                                    $qtdEmbalagemPadraoRecebimento = $embalagem->getQuantidade();
                                }
                            }
                            $menorEmbalagem = end($embalagensEn);
                        }
                    }

                    foreach( $reservas as $reserva ) {
                        if (empty($reserva['lote'])) {
                            $lote = ($produtoEntity->getIndControlaLote() == "S") ? Produto\Lote::LND : Produto\Lote::NCL;
                        } else {
                            $lote = $reserva['lote'];
                        }

                        $quebraPD = $reserva['quebraPulmaoDoca'];
                        if(!empty($reserva['idEndereco'])) {
                            $idEndereco = $reserva['idEndereco'];
                            $depositoEnderecoEn = $depositoEnderecoRepo->find($idEndereco);
                        }

                        $quantidadeRestantePedido = $reserva['qtd'];

                        while ($quantidadeRestantePedido > 0) {
                            $idDepositoEndereco = null;
                            $embalagemAtual = null;

                            if ($forcarEmbVenda) {
                                $embalagemAtual = $embVenda;
                            } else {
                                if (!empty($embExpDefault)) {
                                    $embalagemAtual = $embExpDefault;
                                    if (!Math::compare($embalagemAtual->getQuantidade(), $quantidadeRestantePedido, "<=")) {
                                        $embalagemAtual = null;
                                    }
                                }
                                if (empty($embalagemAtual)) {
                                    if (!empty($embFracDefault)) {
                                        $embalagemAtual = $embFracDefault;
                                    } elseif ($modeloSeparacaoEn->getUtilizaCaixaMaster() == "S") {
                                        foreach ($embalagensEn as $embalagem) {
                                            if (Math::compare($embalagem->getQuantidade(), $quantidadeRestantePedido, "<=")) {
                                                $embalagemAtual = $embalagem;
                                                break;
                                            }
                                        }
                                    } else {
                                        $embalagemAtual = $menorEmbalagem;
                                        if (!Math::compare($embalagemAtual->getQuantidade(), $quantidadeRestantePedido, "<=")) {
                                            $embalagemAtual = null;
                                        }
                                    }
                                }
                            }

                            if (empty($embalagemAtual)) {
                                $msg = "O produto $codProduto grade $grade não tem embalagem ativa para atender a quantidade restante de $quantidadeRestantePedido item(ns)";
                                throw new WMS_Exception($msg);
                            }

                            $embalado = false;
                            if ($modeloSeparacaoEn->getSeparacaoPC() == 'S') {
                                $regra = $modeloSeparacaoEn->getTipoDefaultEmbalado();
                                if (($regra == ModeloSeparacao::DEFAULT_EMBALADO_TODAS_EMBALAGENS) ||
                                    ($regra == ModeloSeparacao::DEFAULT_EMBALADO_PRODUTO && $embalagemAtual->getEmbalado() == "S") ||
                                    ($regra == ModeloSeparacao::DEFAULT_EMBALADO_FRACIONADOS && $embalagemAtual->getQuantidade() < $qtdEmbalagemPadraoRecebimento)
                                ) {
                                    $embalado = true;
                                }
                            }

                            if ($embalagemAtual->isEmbFracionavelDefault() != "S") {
                                list($qtdSepararEmbalagemAtual, $quantidadeRestantePedido) = Math::getFatorMultiploResto($quantidadeRestantePedido, $embalagemAtual->getQuantidade());
                            } else {
                                $qtdSepararEmbalagemAtual = $quantidadeRestantePedido;
                                $quantidadeRestantePedido = 0;
                            }

                            $getTipoSeparacao = function ($fracionado, $embalado) use ($modeloSeparacaoEn){
                                $type = "getTipoSeparacao";
                                $type .= ($fracionado) ? "Fracionado" : "NaoFracionado";
                                $type .= ($embalado) ? "Embalado" : "";

                                return $modeloSeparacaoEn->$type();
                            };

                            if ($reserva['tipoSaida'] == ReservaEstoqueExpedicao::SAIDA_CROSS_DOCKING) {
                                $quebras = array();
                                $quebras[]['tipoQuebra'] = MapaSeparacaoQuebra::QUEBRA_CROSS_DOCKING;
                                $tipoSeparacao = $getTipoSeparacao(false, $embalado);
                            }
                            elseif (!empty($quebraPD) && $quebraPD != "N" && $reserva['tipoSaida'] == ReservaEstoqueExpedicao::SAIDA_PULMAO_DOCA) {
                                $quebras = array();
                                $quebras[]['tipoQuebra'] = MapaSeparacaoQuebra::QUEBRA_PULMAO_DOCA;
                                $tipoSeparacao = $getTipoSeparacao(false, $embalado);
                            }
                            elseif ($embalagemAtual->getQuantidade() >= $qtdEmbalagemPadraoRecebimento) {
                                $quebras = $quebrasNaoFracionado;
                                $tipoSeparacao = $getTipoSeparacao(false, $embalado);
                            }
                            else {
                                $quebras = $quebrasFracionado;
                                $tipoSeparacao = $getTipoSeparacao(true, $embalado);
                            }

                            if ($tipoSeparacao == ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA) {

                                $utilizaEtiquetaMae = $modeloSeparacaoEn->getUtilizaEtiquetaMae();
                                if (($utilizaEtiquetaMae == "N") || (empty($utilizaEtiquetaMae))) {
                                    if (empty($etiquetaMaePadrao)) {
                                        $expedicaoEntity = $expedicaoRepo->findOneBy(array('id'=>$idExpedicao));
                                        $quebras = array();
                                        $etiquetaMaePadrao = $this->getEtiquetaMae(null, $quebras,$expedicaoEntity);
                                    }
                                    $etiquetaMae = $etiquetaMaePadrao;
                                } else {
                                    $etiquetaMae = $this->getEtiquetaMae($pedidoProduto, $quebras);
                                }

                                if ($embalagemAtual->isEmbFracionavelDefault() != "S") {
                                    $qtdSeparar = $embalagemAtual->getQuantidade();
                                } else {
                                    $qtdSeparar = $qtdSepararEmbalagemAtual;
                                    $qtdSepararEmbalagemAtual = 1;
                                }

                                for ($i = 0; $i < $qtdSepararEmbalagemAtual; $i++) {
                                    $arrEtiqueta = array(
                                        'statusEntity' => $statusEntity,
                                        'produtoEntity' => $produtoEntity,
                                        'pedidoEntity' => $pedidoEntity,
                                        'quantidade' => $qtdSeparar,
                                        'volumeEntity' => null,
                                        'embalagemEntity' => $embalagemAtual,
                                        'etiquetaMae' => $etiquetaMae,
                                        'depositoEnderecoEn' => $depositoEnderecoEn,
                                        'verificaReentrega' => $verificaReentrega,
                                        'tipoSeparacao' => $reserva['tipoSaida'],
                                        'lote' => $lote,
                                        'grupo' => null
                                    );

                                    switch ($reserva['tipoSaida']) {
                                        case ReservaEstoqueExpedicao::SAIDA_SEM_CONTROLE_ESTOQUE:
                                            $arrEtqtSemControleEstoque[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_PICKING:
                                            $arrEtqtPicking[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_SEPARACAO_AEREA:
                                            $arrEtqtSeparacaoAerea[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_PULMAO_DOCA:
                                            $arrEtqtPulmaoDoca[] = $arrEtiqueta;
                                            break;
                                        case ReservaEstoqueExpedicao::SAIDA_CROSS_DOCKING:
                                            $arrEtqtCrossDocking[] = $arrEtiqueta;
                                            break;
                                    }
                                }
                            } else {
                                $cubagem = null;
                                $consolidado = 'N';
                                $fracionavel = 'N';

                                if ($quebraUnidFracionavel && $produtoEntity->getIndFracionavel() == 'S') {
                                    $quebras = [];
                                    $quebras[]['tipoQuebra'] = MapaSeparacaoQuebra::QUEBRA_UNID_FRACIONAVEL;
                                    $fracionavel = 'S';
                                }
                                elseif ($embalado) {
                                    $cubagemProduto = $this->tofloat($embalagemAtual->getCubagem());
                                    if (empty($cubagemProduto)) {
                                        $dadoLogisticoEn = $dadoLogisticoRepo->findOneBy(array('embalagem' => $embalagemAtual->getId()));
                                        if (!empty($dadoLogisticoEn)) {
                                            $numAltura       = $this->tofloat($dadoLogisticoEn->getAltura());
                                            $numLargura      = $this->tofloat($dadoLogisticoEn->getLargura());
                                            $numProfundidade = $this->tofloat($dadoLogisticoEn->getProfundidade());
                                            $cubagemProduto  = $this->tofloat($numAltura * $numLargura * $numProfundidade);
                                        }
                                    }
                                    $cubagemProduto = (!empty($cubagemProduto)) ? $cubagemProduto : $this->tofloat(0.001);

                                    $cubagem = Math::multiplicar( $cubagemProduto, $qtdSepararEmbalagemAtual );

                                    $quebras = $quebrasEmbalado;
                                    $quebras[]['tipoQuebra'] = MapaSeparacaoQuebra::QUEBRA_CARRINHO;
                                    $consolidado = 'S';
                                }

                                $unicIndex = implode("-*-", [$pedidoProduto->getId(), $embalagemAtual->getId(), $idEndereco, $lote]);
                                if (isset($arrMapasEmbPP[$unicIndex])) {
                                    $arrMapasEmbPP[$unicIndex]['qtd'] += $qtdSepararEmbalagemAtual;
                                } else {
                                    list($strQuebrasConcat, $arrQuebras) = self::getSetupQuebras($quebras, $pedidoProduto, $reserva);
                                    $arrMapasEmbPP[$unicIndex] = array(
                                        'forcarEmbVenda' => $forcarEmbVenda,
                                        'qtd' => $qtdSepararEmbalagemAtual,
                                        'consolidado' => $consolidado,
                                        'fracionavel' => $fracionavel,
                                        'strQuebrasConcat' => $strQuebrasConcat,
                                        'quebras' => $arrQuebras,
                                        'expedicaoEn' => $expedicaoEntity,
                                        'cubagem' => $cubagem,
                                        'pedidoProdutoEn' => $pedidoProduto,
                                        'embalagensDisponiveis' => $embalagensEn,
                                        'lote' => $lote,
                                        'embalagemEn' => $embalagemAtual,
                                        'enderecoEn' => $depositoEnderecoEn);
                                }
                            }
                        }
                    }
                } else {
                    $view = \Zend_layout::getMvcInstance()->getView();
                    $link = $view->url(array('controller' => 'relatorio_produtos-expedicao', 'action' => 'sem-dados', 'id' => $idExpedicao));
                    $msg = 'Existem produtos sem definição de volume. Deseja exibir ?';
                    throw new WMS_Exception($msg, $link);
                }


                if (!isset($arrPedidos[$pedidoEntity->getId()])) {
                    $pedidoEntity->setIndEtiquetaMapaGerado("S");
                    $arrPedidos[$pedidoEntity->getId()] = $pedidoEntity;
                }
            }

            foreach ( $arrEtqtSemControleEstoque as $etqt) {
                $arrayEtiquetas[] = $etqt;
            }
            foreach ( $arrEtqtPicking as $etqt) {
                $arrayEtiquetas[] = $etqt;
            }
            foreach ( $arrEtqtSeparacaoAerea as $etqt) {
                $arrayEtiquetas[] = $etqt;
            }
            foreach ( $arrEtqtPulmaoDoca as $etqt) {
                $arrayEtiquetas[] = $etqt;
            }
            foreach ( $arrEtqtCrossDocking as $etqt) {
                $arrayEtiquetas[] = $etqt;
            }

            $arrayGrupos = array();
            foreach ($arrayEtiquetas as $etiqueta) {
                $grupo = $etiqueta['grupo'];
                $etiquetaEn = $this->salvaNovaEtiqueta(
                    $etiqueta['statusEntity'],
                    $etiqueta['produtoEntity'],
                    $etiqueta['pedidoEntity'],
                    $etiqueta['quantidade'],
                    $etiqueta['volumeEntity'],
                    $etiqueta['embalagemEntity'],
                    null,
                    $etiqueta['etiquetaMae'],
                    $etiqueta['depositoEnderecoEn'],
                    $etiqueta['verificaReentrega'],
                    $etiquetaConferenciaRepo,
                    $etiqueta['tipoSeparacao'],
                    $etiqueta['lote']
                );

                if ($grupo != null) {
                    if (!isset($arrayGrupos[$grupo])) {
                        $arrayGrupos[$grupo] = array(
                            'grupo' => $grupo,
                            'primeiraEtiqueta' => $etiquetaEn,
                            'etiquetas' => array()
                        );
                    } else {
                        $arrayGrupos[$grupo]['etiquetas'][] = $etiquetaEn;
                    }
                }
            }

            foreach ($arrayGrupos as $grupo) {
                foreach ($grupo['etiquetas'] as $etiquetaEn) {
                    $etiquetaEn->setCodReferencia($grupo['primeiraEtiqueta']->getId());
                    $this->getEntityManager()->persist($etiquetaEn);
                }
            }

            $arrReagrupado = self::regroupMapaProduto($arrMapasEmbPP);

            foreach($arrReagrupado as $strQuebra => $produtos) {
                foreach ($produtos as $produtoGradeLote => $pedidoProduto) {
                    foreach ($pedidoProduto['enderecos'] as $endereco) {
                        foreach ($endereco as $idElemento => $element) {
                            $mapaSeparacaoEn = self::getMapaSeparacao($element['quebras'], $statusEntity, $pedidoProduto['expedicaoEn']);
                            $dadosConsolidado = [];
                            if ($element['consolidado'] == 'S') {
                                $dadosConsolidado = [
                                    'cubagem' => $element['cubagem'],
                                    'carrinho' => $element['quebras'][MapaSeparacaoQuebra::QUEBRA_CARRINHO]['codQuebra'],
                                    'caixaInicio' => $element['caixaInicio'],
                                    'caixaFim' => $element['caixaFim']
                                ];
                            }
                            $arrMapaPedProd = self::salvaMapaSeparacaoProduto($mapaSeparacaoEn,
                                $element['produtoEn'], $element['qtd'], null,
                                $element['embalagemEn'], $element['arrPedProd'],
                                $element['enderecoEn'], $dadosConsolidado,
                                $element['pedidoEn'], $arrayRepositorios,
                                $element['consolidado'], $element['lote'], $arrMapaPedProd);
                        }
                    }
                }
            }

            foreach($arrPedidos as $pedido) {
                $this->_em->persist($pedido);
            }

            foreach($arrMapaPedProd as $mapaPedProd) {
                $this->_em->persist($mapaPedProd);
            }

            $this->_em->flush();

            if ($modeloSeparacaoEn->getAgrupContEtiquetas() == 'S' && $modeloSeparacaoEn->getUsaCaixaPadrao() == 'S') {
                /** @var CaixaEmbalado $caixaEn */
                $caixaEn = $this->getEntityManager()->getRepository('wms:Expedicao\CaixaEmbalado')->findOneBy(['isAtiva' => true, 'isDefault' => true]);
                if (empty($caixaEn))
                    throw new \Exception("O modelo de separação está configurado para sequenciamento único dos volumes<br/>com base na caixa de embalagem padrão, para isso é obrigatório o cadastro de uma caixa de embalado padrão e que esteja ativa!");

                if ($modeloSeparacaoEn->getTipoAgroupSeqEtiquetas() === ModeloSeparacao::TIPO_AGROUP_VOLS_EXPEDICAO) {
                    /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
                    $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');

                    $arrElements = $mapaSeparacaoProdutoRepo->getMaximosConsolidadoByCliente($idExpedicao);
                    $minVolsExp = CaixaEmbalado::calculaExpedicao($caixaEn, $arrElements);

                    $counterExp = 0;
                    foreach ($minVolsExp as $idCliente => $minVol) {
                        $counterExp += $minVol;
                    }

                    $countEtiquetas = count($this->_em->getRepository("wms:Expedicao\VEtiquetaSeparacao")->findBy(['codExpedicao' => $idExpedicao]));

                    $totalEtiquetas = $counterExp + $countEtiquetas;

                    $expedicaoEntity->setCountVolumes($totalEtiquetas);

                    $this->_em->persist($expedicaoEntity);
                    $this->_em->flush($expedicaoEntity);
                }
            }

            $parametroConsistencia = $this->getSystemParameterValue('CONSISTENCIA_SEGURANCA');
            if ($parametroConsistencia == 'S') {
                $resultadoConsistencia = $mapaSeparacaoRepo->verificaConsistenciaSeguranca($idExpedicao);
                if (count($resultadoConsistencia) > 0) {
                    $produto = $resultadoConsistencia[0]['COD_PRODUTO'];
                    $qtdMapa = $resultadoConsistencia[0]['QTD_MAPA'];
                    $qtdEtiqueta = $resultadoConsistencia[0]['QTD_ETIQUETA'];
                    $qtdPedido = $resultadoConsistencia[0]['QTD_PEDIDO'];
                    $msg = "Existe problemas com a geração dos mapas, entre em contato com o suporte! - Produto: $produto  Qtd.Pedido: $qtdPedido Qtd.Gerado: $qtdMapa (Mapa) + $qtdEtiqueta (Etiquetas)";
                    throw new WMS_Exception($msg);
                }

                $resultadoConsistencia = $pedidoRepo->getReservasSemPedidosByExpedicao($idExpedicao);
                if (count($resultadoConsistencia) > 0) {
                    $pedidoInterno = $resultadoConsistencia[0]['COD_PEDIDO'];
                    $idReserva = $resultadoConsistencia[0]['COD_RESERVA_ESTOQUE'];
                    $msg = "Existem reservas de estoque sem pedidos no WMS - Reserva:$idReserva Pedido (Interno): $pedidoInterno";
                    throw new WMS_Exception($msg);
                }
            }
        } catch (WMS_Exception $WMS_Exception){
            throw $WMS_Exception;
        }catch (\Exception $e) {
            throw $e;
        }
    }

    private function regroupReservaVolumes($reservas, $volumes, $tipoSeparacao)
    {
        $arrReservaRegroup = array();

        if ($tipoSeparacao == ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA) {
            /** @var Produto\Volume $volume */
            foreach ($volumes as $volume) {

                if ($volume->getDataInativacao() != null)
                    continue;

                $arrEnderecos = array();

                foreach ($reservas as $reserva) {
                    if ($reserva['codProdutoVolume'] == $volume->getId()) {
                        if (!empty($reserva['idEndereco'])) {
                            $enderecoEn = $this->_em->find("wms:Deposito\Endereco", $reserva['idEndereco']);
                        } else {
                            $enderecoEn = $volume->getEndereco();
                        }
                        
                        if ($enderecoEn != null) {
                            $arrEnderecos[$enderecoEn->getId()] = array(
                                'qtd' => $reserva['qtd'],
                                'enderecoEn' => $enderecoEn,
                                'tipoSaida' => $reserva['tipoSaida'],
                                'lote' => $reserva['lote']
                            );
                        } else {
                            $arrEnderecos[] = array(
                                'qtd' => $reserva['qtd'],
                                'enderecoEn' => $enderecoEn,
                                'tipoSaida' => $reserva['tipoSaida'],
                                'lote' => $reserva['lote']
                            );
                        }
                    }
                }

                $arrReservaRegroup[$volume->getId()] = array(
                    'volumeEn' => $volume,
                    'enderecos' => $arrEnderecos
                );
            }
        } else {
            foreach ($reservas as $reserva) {
                $volumeEn = null;
                $enderecoEn = null;
                foreach ($volumes as $volume) {

                    if ($volume->getDataInativacao() != null)
                        continue;

                    if ($reserva['codProdutoVolume'] == $volume->getId()) {
                        if (!empty($reserva['idEndereco'])) {
                            $enderecoEn = $this->_em->find("wms:Deposito\Endereco", $reserva['idEndereco']);
                        } else {
                            $enderecoEn = $volume->getEndereco();
                        }
                        $volumeEn = $volume;
                        break;
                    }
                }
                $arrReservaRegroup[$reserva['idEndereco']]['enderecoEn'] = $enderecoEn;
                $arrReservaRegroup[$reserva['idEndereco']]['volumes'][$reserva['codProdutoVolume']] = array(
                    'volumeEn' => $volumeEn,
                    'qtd' => $reserva['qtd'],
                    'tipoSaida' => $reserva['tipoSaida'],
                    'quebraPD' => $reserva['quebraPulmaoDoca'],
                    'lote' => $reserva['lote']
                );
            }
        }

        return $arrReservaRegroup;
    }

    /**
     * @param $arrItens
     * @return array
     * @throws \Exception
     */
    private function regroupMapaProduto($arrItens)
    {
        $arrConsolidado = $arrItensCaixas = $newArray = $arrayTemp = array();
        $cubagemCaixa = (float) str_replace(',', '.', $this->getSystemParameterValue('CUBAGEM_CAIXA_CARRINHO'));
        if ($cubagemCaixa == 0) throw new \Exception("A cubagem da CAIXA do carrinho de separação não pode ser 0.");
        $maxCaixasCarrinho = (int)$this->getSystemParameterValue('IND_QTD_CAIXA_PC');
        if ($maxCaixasCarrinho < 1) throw new \Exception("O carrinho de separação precisa ter ao menos uma caixa.");

        // Passa por todos os possíveis registros de mapaProduto e soma as quantidades por mapa->endereco->produto
        foreach ($arrItens as $element) {

            $embalagens = $element['embalagensDisponiveis'];
            $qtdMapa = $element['qtd'];

            /** @var PedidoProduto $pedidoProdutoEn */
            $pedidoProdutoEn = $element['pedidoProdutoEn'];
            /** @var Produto\Embalagem $embalagemEn */
            $embalagemEn = $element['embalagemEn'];
            /** @var Produto $produtoEn */
            $produtoEn = $pedidoProdutoEn->getProduto();
            /** @var Endereco $enderecoEn */
            $enderecoEn = $element['enderecoEn'];

            $expedicaoEn = $element['expedicaoEn'];

            $quebras = $element['quebras'];
            $strQuebrasConcat = $element['strQuebrasConcat'];

            $enderecoId = (!empty($enderecoEn)) ? $enderecoEn->getId() : null;
            $produtoGradeLote = $produtoEn->getId().'#!#'.$produtoEn->getGrade().'#!#'.$element['lote'];
            $idPedProd = $pedidoProdutoEn->getId();
            $idCliente = $pedidoProdutoEn->getPedido()->getPessoa()->getId();

            $qtd = Math::multiplicar($qtdMapa, $embalagemEn->getQuantidade());

            if ($element['consolidado'] == 'S') {

                if (isset($arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()])) {
                    $qtdAtual = $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()]['qtd'];
                    $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()]['qtd'] = Math::adicionar($qtdAtual, $qtdMapa);
                    if (isset($arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd])) {
                        $qtdPedProdAtual = $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd]['qtd'];
                        $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd]['qtd'] = Math::adicionar($qtdPedProdAtual, $qtd);
                    } else {
                        $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd] = ['entity' => $pedidoProdutoEn, 'qtd' => $qtd];
                    }
                } else {
                    $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['expedicaoEn'] = $expedicaoEn;
                    $arrConsolidado[$strQuebrasConcat][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemEn->getId()] = array(
                        'qtd' => $qtdMapa,
                        'lote' => $element['lote'],
                        'consolidado' => $element['consolidado'],
                        'cubagem' => $element['cubagem'],
                        'arrPedProd' => [$idPedProd => ['entity' => $pedidoProdutoEn, 'qtd' => $qtd]],
                        'pedidoEn' => $pedidoProdutoEn->getPedido(),
                        'produtoEn' => $produtoEn,
                        'quebras' => $quebras,
                        'embalagemEn' => $embalagemEn,
                        'enderecoEn' => $enderecoEn);
                }
                continue;
            }

            if ($element['fracionavel'] == 'S') {
                if (isset($newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()])) {
                    $qtdAtual = $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()]['qtd'];
                    $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()]['qtd'] = Math::adicionar($qtdAtual, $qtd);
                    if (isset($newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd])) {
                        $qtdPedProdAtual = $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd]['qtd'];
                        $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd]['qtd'] = Math::adicionar($qtdPedProdAtual, $qtd);
                    } else {
                        $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()]['arrPedProd'][$idPedProd] = ['entity' => $pedidoProdutoEn, 'qtd' => $qtd];
                    }
                } else {
                    $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['expedicaoEn'] = $element['expedicaoEn'];
                    $newArray[$strQuebrasConcat]["$idCliente-!-$produtoGradeLote"]['enderecos'][$enderecoId][$embalagemEn->getId()] = array(
                        'qtd' => $qtdMapa,
                        'lote' => $element['lote'],
                        'consolidado' => "N",
                        'cubagem' => null,
                        'arrPedProd' => [$idPedProd => ['entity' => $pedidoProdutoEn, 'qtd' => $qtd]],
                        'embalagemEn' => $embalagemEn,
                        'produtoEn' => $produtoEn,
                        'pedidoEn' => null,
                        'quebras' => $element['quebras'],
                        'enderecoEn' => $element['enderecoEn']);
                }
                continue;
            }

            $idPresetEmb = ($element['forcarEmbVenda']) ? $embalagemEn->getId() : 0;

            if (isset($arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb])){
                $qtdAtual = $arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb]['qtd'];
                $arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb]['qtd'] = Math::adicionar($qtdAtual, $qtd);
                if (isset ($arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb]['arrPedProd'][$idPedProd])) {
                    $qtdPedProdAtual = $arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb]['arrPedProd'][$idPedProd]['qtd'];
                    $arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb]['arrPedProd'][$idPedProd]['qtd'] = Math::adicionar($qtdPedProdAtual, $qtd);
                } else {
                    $arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb]['arrPedProd'][$idPedProd] = ['entity' => $pedidoProdutoEn, 'qtd' => $qtd];
                }
            } else {
                $arr = [
                    'qtd' => $qtd,
                    'lote' => $element['lote'],
                    'embalagensDisponiveis' => $embalagens,
                    'arrPedProd' => [$idPedProd => ['entity' => $pedidoProdutoEn, 'qtd' => $qtd]],
                    'quebras' => $quebras,
                    'embalagemPreset' => $embalagemEn,
                    'expedicaoEn' => $expedicaoEn,
                    'enderecoEn' => $enderecoEn,
                    'produtoEn' => $produtoEn,
                    'forcarEmbVenda' => $element['forcarEmbVenda']
                ];

                $arrayTemp[$strQuebrasConcat][$enderecoId][$produtoGradeLote][$idPresetEmb] = $arr;
            }
        }

        $totalCaixas = 0;
        // IDENTIFICA A QUANTIDADE NECESSÁRIA DE CAIXAS PARA CADA CLIENTE DE MAPA CONSOLIDADO
        foreach ($arrConsolidado as $strQuebra => $clientes) {
            foreach ($clientes as $idCliente => $dadosCliente) {
                $idCaixa = 0;
                foreach($dadosCliente['itens'] as $produtoGradeLote => $item) {
                    foreach ($item['enderecos'] as $idEndereco => $embs) {
                        foreach ($embs as $idEmb => $emb) {
                            $cubagemRestante = $emb['cubagem'];
                            while ($cubagemRestante > 0) {
                                $caixa = [];

                                // VERIFICA SE TEM UMA CAIXA QUE NÃO ESTEJA CHEIA
                                if (isset($arrConsolidado[$strQuebra][$idCliente]['caixas'])) {
                                    foreach ($arrConsolidado[$strQuebra][$idCliente]['caixas'] as $key => $cx) {
                                        if (Math::compare($cx['cubagemDisponivel'], 0, '>')) {
                                            $caixa = $cx;
                                            $idCaixa = $key;
                                        }
                                    }
                                }

                                // SE NÃO TIVER CAIXA ABERTA CRIA UMA NOVA
                                if (empty($caixa)) {
                                    $idCaixa++;
                                    $caixa = ['cubagemDisponivel' => $cubagemCaixa];
                                }

                                // VERIFICA SE O ITEM CABE POR COMPLETO NA MESMA CAIXA OU SE SERÁ DIVIDIO ENTRE A ATUAL E UMA NOVA
                                if (Math::compare($cubagemRestante, $caixa['cubagemDisponivel'], '<=')) {
                                    $caixa['cubagemDisponivel'] = Math::subtrair($caixa['cubagemDisponivel'], $cubagemRestante);
                                    $caixa['itens'][] = "$produtoGradeLote*+*$idEndereco*+*$idEmb";
                                    if (!isset($arrConsolidado[$strQuebra][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaInicio'])) {
                                        $arrConsolidado[$strQuebra][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaInicio'] = $idCaixa;
                                    }
                                    $arrConsolidado[$strQuebra][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaFim'] = $idCaixa;
                                    $cubagemRestante = 0;
                                } else {
                                    $cubagemRestante = Math::subtrair($cubagemRestante, $caixa['cubagemDisponivel']);
                                    $caixa['cubagemDisponivel'] = 0;
                                    $caixa['itens'][] = "$produtoGradeLote*+*$idEndereco*+*$idEmb";
                                    if (!isset($arrConsolidado[$strQuebra][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaInicio'])) {
                                        $arrConsolidado[$strQuebra][$idCliente]['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaInicio'] = $idCaixa;
                                    }
                                }

                                // REGISTRA A CAIXA NO DEVIDO CLIENTE
                                $arrConsolidado[$strQuebra][$idCliente]['caixas'][$idCaixa] = $caixa;
                            }
                        }
                    }
                }
            }
        }

        // ORDENA OS CLIENTES DO ARRAY CONSOLIDADO DO MAIOR PARA O MENOR EM RELAÇÃO AO TOTAL DE CAIXAS NECESSÁRIAS
        foreach($arrConsolidado as $quebras => $clientes) {
            uasort($clientes, function ($a, $b) {
                return count($a['caixas']) < count($b['caixas']);
            });
            $arrConsolidado[$quebras] = $clientes;
        }

        $arrCarrinhos = [];
        $lastCarByQuebra = [];
        // AGRUPAMENTO POR QUEBRA PELA QUANTIDADE MAXIMA DE CAIXAS POR CARRINHO DE ACORDO COM AS QUEBRAS PRÉ-DEFINIDAS
        foreach ($arrConsolidado as $strQuebras => $clientes) {
            foreach ($clientes as $idCliente => $dadosCliente) {
                $caixasRestantes = count($dadosCliente['caixas']);
                if ($caixasRestantes > $maxCaixasCarrinho)
                    throw new \Exception("As $caixasRestantes caixas necessárias para o cliente de ID $idCliente excede a capacidade de $maxCaixasCarrinho caixas suportada pelo carrinho! Entre em contato com o suporte.");
                $carrinho = [];
                $numCarrinho = 0;

                // VERIFICA SE EXISTE ALGUM CARRINHO LIVRE QUE COMPORTE A QTD DE CAIXAS RESTANTES
                if (isset($arrCarrinhos[$strQuebras])) {
                    foreach ($arrCarrinhos[$strQuebras] as $idCarrinho => $car) {
                        if ($caixasRestantes <= $car['caixasLivres']) {
                            $carrinho = $car;
                            $numCarrinho = $idCarrinho;
                            break;
                        }
                    }
                }

                // SE NÃO TIVER CARRINHO CRIA UM NOVO
                if (empty($carrinho)) {
                    if (isset($lastCarByQuebra[$strQuebras])) {
                        $numCarrinho = $lastCarByQuebra[$strQuebras];
                    }
                    $numCarrinho++;
                    $lastCarByQuebra[$strQuebras] = $numCarrinho;
                    $carrinho = ['clientes' => [], 'caixasLivres' => $maxCaixasCarrinho];
                }

                // VERIFICA SE A CAIXA ATUAL COMPORTA A QUANTIDADE DE CAIXAS NECESSÁRIAS DO CLIENTE ATUAL
                if ($caixasRestantes <= $carrinho['caixasLivres']) {
                    $proximaCaixaLivre = $maxCaixasCarrinho - $carrinho['caixasLivres'];
                    $arrCheck = [];

                    // REIDENTIFICA OS PRODUTOS NAS CAIXAS DE ACORDO COM O ALOCAMENTO NO CARRINHO
                    foreach ($dadosCliente['caixas'] as $idCaixa => $caixa) {
                        foreach ($caixa['itens'] as $item) {
                            if (!in_array($item, $arrCheck)) {
                                $arrCheck[] = $item;
                                list($produtoGradeLote, $idEndereco, $idEmb) = explode("*+*", $item);
                                $arrValues = $dadosCliente['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb];
                                $intervalo = $arrValues['caixaFim'] - $arrValues['caixaInicio'];
                                $dadosCliente['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaInicio'] = $proximaCaixaLivre + $idCaixa;
                                $dadosCliente['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['caixaFim'] = $proximaCaixaLivre + $idCaixa + $intervalo;
                            }
                        }
                    }

                    // REDEFINE A QUANTIDADE DE CAIXAS LIVRES DO CARRINHO
                    $carrinho['caixasLivres'] -= $caixasRestantes;

                    // REGISTRA O NÚMERO DO CARRINHO COMO CÓDIGO DE QUEBRA DO MAPA
                    foreach ($dadosCliente['itens'] as $produtoGradeLote => $dados) {
                        foreach ($dados['enderecos'] as $idEndereco => $embArr)
                            foreach ($embArr as $idEmb => $emb)
                                $dadosCliente['itens'][$produtoGradeLote]['enderecos'][$idEndereco][$idEmb]['quebras'][MapaSeparacaoQuebra::QUEBRA_CARRINHO]['codQuebra'] = $numCarrinho;
                    }

                    // SALVA AS ALTERAÇÕES NA MATRIZ TEMPORÁRIA
                    $carrinho['clientes'][$idCliente] = $dadosCliente;
                    $arrCarrinhos[$strQuebras][$numCarrinho] = $carrinho;
                }
            }
        }

        // PASSA O RESULTADO DO AGRUPAMENTO DOS CARRINHOS PARA A MATRIZ PRINCIPAL
        foreach ($arrCarrinhos as $strQuebra => $carrinhos) {
            foreach ($carrinhos as $idCarrinho => $infoCarrinho) {
                foreach ($infoCarrinho['clientes'] as $idCliente => $infoCliente) {
                    foreach ($infoCliente['itens'] as $produtoGradeLote => $dadosPedProd) {
                        $newArray[$strQuebra]["$idCliente-!-$produtoGradeLote"] = $dadosPedProd;
                    }
                }
            }
        }

        foreach ($arrayTemp as $strQuebrasConcat => $itens) {
            foreach ($itens as $endereco) {
                foreach ($endereco as $produtoGradeLote => $embs) {
                    foreach ($embs as $idEmbalagem => $produto) {
                        $qtdTemp = $produto['qtd'];
                        /** @var Produto $produtoEn */
                        $produtoEn = $produto['produtoEn'];

                        if (!$produto['forcarEmbVenda']) {
                            $embsFiltered1 = array_filter($produto['embalagensDisponiveis'], function ($emb) {
                                /** @var Produto\Embalagem $emb */
                                return ($emb->isEmbExpDefault() == "S");
                            });
                            $embExpDefault = reset($embsFiltered1);

                            $embsFiltered2 = array_filter($produto['embalagensDisponiveis'], function ($emb) {
                                /** @var Produto\Embalagem $emb */
                                return ($emb->isEmbFracionavelDefault() == "S");
                            });
                            $embFracDefault = reset($embsFiltered2);
                        }

                        while ($qtdTemp !== 0) {
                            $embalagemAtual = null;
                            if (!$produto['forcarEmbVenda']) {
                                if (!empty($embExpDefault)) {
                                    $embalagemAtual = $embExpDefault;
                                    if (!Math::compare($embalagemAtual->getQuantidade(), $qtdTemp, "<=")) {
                                        $embalagemAtual = null;
                                    }
                                }
                                if (empty($embalagemAtual)) {
                                    if ($produto['produtoEn']->getIndFracionavel() == 'S') {
                                        $embalagemAtual = $embFracDefault;
                                    } else {
                                        /** @var Produto\Embalagem $embalagemEn */
                                        foreach ($produto['embalagensDisponiveis'] as $embalagemEn) {
                                            if (Math::compare($embalagemEn->getQuantidade(), $qtdTemp, '<=')) {
                                                $embalagemAtual = $embalagemEn;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $embalagemAtual = $produto['embalagemPreset'];
                            }

                            if (is_null($embalagemAtual)) {
                                $strLote = (!in_array($produto['lote'], [Produto\Lote::LND, Produto\Lote::NCL])) ? " lote: $produto[lote]" : "";
                                throw new \Exception("Erro ao otimizar o produto " . $produtoEn->getId() . "-" . $produtoEn->getGrade() . "$strLote<br /> Qtd embalagem " . $embalagemEn->getQuantidade() . " - qtd à separar $qtdTemp");
                            }

                            if ($embalagemAtual->isEmbFracionavelDefault() != "S") {
                                list($qtdEmbs, $qtdTemp) = Math::getFatorMultiploResto($qtdTemp, $embalagemAtual->getQuantidade());
                            } else {
                                $qtdEmbs = $qtdTemp;
                                $qtdTemp = 0;
                            }

                            $newArrPedProd = [];
                            $qtdAbater = Math::multiplicar($qtdEmbs, $embalagemAtual->getQuantidade());
                            while ($qtdAbater > 0) {
                                foreach ($produto['arrPedProd'] as $idPedProd => $pedProd) {
                                    if (Math::compare($pedProd['qtd'], $qtdAbater, "<=")) {
                                        if (!isset($newArrPedProd[$idPedProd])) {
                                            $newArrPedProd[$idPedProd] = $pedProd;
                                        } else {
                                            $newArrPedProd[$idPedProd]['qtd'] = Math::adicionar($newArrPedProd[$idPedProd]['qtd'], $pedProd['qtd']);
                                        }
                                        unset($produto['arrPedProd'][$idPedProd]);
                                        $qtdAbater = Math::subtrair($qtdAbater, $pedProd['qtd']);
                                    } else {
                                        if (!isset($newArrPedProd[$idPedProd])) {
                                            $newArrPedProd[$idPedProd] = $pedProd;
                                            $newArrPedProd[$idPedProd]['qtd'] = $qtdAbater;
                                        } else {
                                            $newArrPedProd[$idPedProd]['qtd'] = Math::adicionar($newArrPedProd[$idPedProd]['qtd'], $qtdAbater);
                                        }
                                        $produto['arrPedProd'][$idPedProd]['qtd'] = Math::subtrair($pedProd['qtd'], $qtdAbater);
                                        $qtdAbater = 0;
                                    }

                                    if (empty($qtdAbater)) break;
                                }
                            }

                            $enderecoId = (!empty($produto['enderecoEn'])) ? $produto['enderecoEn']->getId() : null;
                            $newArray[$strQuebrasConcat][$produtoGradeLote]['expedicaoEn'] = $produto['expedicaoEn'];
                            $newArray[$strQuebrasConcat][$produtoGradeLote]['enderecos'][$enderecoId][$embalagemAtual->getId()] = array(
                                'qtd' => $qtdEmbs,
                                'lote' => $produto['lote'],
                                'consolidado' => "N",
                                'cubagem' => null,
                                'arrPedProd' => $newArrPedProd,
                                'embalagemEn' => $embalagemAtual,
                                'produtoEn' => $produto['produtoEn'],
                                'pedidoEn' => null,
                                'quebras' => $produto['quebras'],
                                'enderecoEn' => $produto['enderecoEn']);
                        }

                    }
                }
            }
        }

        return $newArray;
    }

    /**
     * @param $quebras array
     * @param $pedidoProdutoEn PedidoProduto
     * @param $reserva array
     * @throws \Exception
     * @return array
     */
    private function getSetupQuebras($quebras, $pedidoProdutoEn, $reserva = [])
    {

        $arrQuebras = [];
        $codQuebra = 0;
        $dscQuebra = "";

        foreach ($quebras as $item) {
            $quebra = $item['tipoQuebra'];
            if ($quebra == null) continue;

            //MAPA DE REENTREGA
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_REENTREGA) {
                $dscQuebra = "MAPA DE REENTREGAS";
                $codQuebra = "";
            }

            //UTILIZA CARRINHO
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_CARRINHO) {
                $dscQuebra = "MAPA DE SEPARAÇÃO CONSOLIDADA";
                $codQuebra = 0;
            }

            //UNIDADE FRACIONÁVEL (METRO, QUILO, LITRO)
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_UNID_FRACIONAVEL) {
                $dscQuebra = "MAPA DE SEPARAÇÃO DE UNIDADES FRACIONÁVEIS";
                $codQuebra = 0;
            }

            //CLIENTE
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_CLIENTE)  {
                $cliente = $pedidoProdutoEn->getPedido()->getPessoa();
                $nomCliente = $cliente->getPessoa()->getNome();
                $codQuebra = $cliente->getCodClienteExterno();
                $dscQuebra = "CLIENTE: $codQuebra - $nomCliente";
            }

            //RUA
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_RUA) {
                $codQuebra = 0;
                $endereco = null;
                $dscQuebra = "RUA: (SEM ENDEREÇO DE PICKING)";
                $embalagens = $pedidoProdutoEn->getProduto()->getEmbalagens();
                $volumes = $pedidoProdutoEn->getProduto()->getVolumes();
                if (count($embalagens) >0) $endereco = $embalagens[0]->getEndereco();
                if (count($volumes) >0) $endereco = $volumes[0]->getEndereco();
                if (!empty($endereco)) {
                    $codQuebra = $endereco->getRua();
                    $dscQuebra = "RUA: $codQuebra";
                }
            }

            //LINHA DE SEPARAÇÃO
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO) {
                $codQuebra = 0;
                $nomLinha = "(SEM LINHA DE SEPARACAO)";
                if ($pedidoProdutoEn->getProduto()->getLinhaSeparacao() != null) {
                    $codQuebra = $pedidoProdutoEn->getProduto()->getLinhaSeparacao()->getId();
                    $nomLinha = $pedidoProdutoEn->getProduto()->getLinhaSeparacao()->getDescricao();
                }
                $dscQuebra = "LINHA: $codQuebra - $nomLinha";
            }

            //PRAÇA
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_PRACA) {
                $clienteRepo = $this->getEntityManager()->getRepository("wms:Pessoa\Papel\Cliente");
                $codQuebra = $clienteRepo->getCodPracaByClienteId($pedidoProdutoEn->getPedido()->getPessoa()->getCodClienteExterno());
                if ($codQuebra == 0){
                    $nomPraca = "(SEM PRAÇA DEFINIDA)";
                } else {
                    $pracaEn = $this->getEntityManager()->getRepository("wms:MapaSeparacao\Praca")->find($codQuebra);
                    $nomPraca = $pracaEn->getNomePraca();
                }
                $dscQuebra = "PRACA: $codQuebra - $nomPraca";
            }

            //ROTA
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_ROTA) {

                $codQuebra = 0;
                $clienteRepo = $this->getEntityManager()->getRepository("wms:Pessoa\Papel\Cliente");
                $clienteEn = $clienteRepo->findOneBy(array('codClienteExterno' => $pedidoProdutoEn->getPedido()->getPessoa()->getCodClienteExterno()));
                $rota = $clienteEn->getRota();
                if ($rota != null) $codQuebra = $rota->getId();


                if ($codQuebra == 0){
                    $nomRota = "(SEM ROTA DEFINIDA)";
                } else {
                    $rotaEn = $this->getEntityManager()->getRepository('wms:MapaSeparacao\Rota')->find($codQuebra);
                    $nomRota = $rotaEn->getNomeRota();
                }
                $dscQuebra = "ROTA: $codQuebra - $nomRota";
            }

            //PULMAO-DOCA
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_PULMAO_DOCA) {
                $dscPD = '';
                switch ($reserva['quebraPulmaoDoca']) {
                    case ModeloSeparacao::QUEBRA_PULMAO_DOCA_EXPEDICAO:
                        $dscPD = "EXPEDIÇÃO: '$reserva[codCriterioPD]'";
                        break;
                    case ModeloSeparacao::QUEBRA_PULMAO_DOCA_CARGA:
                        /** @var Carga $cargaEn */
                        $cargaEn = $this->getEntityManager()->find('wms:Expedicao\Carga', $reserva['codCriterioPD']);
                        $dscPD = "CARGA: '" . $cargaEn->getCodCargaExterno() . "'";
                        break;
                    case ModeloSeparacao::QUEBRA_PULMAO_DOCA_ROTA:
                        $clienteRepo = $this->getEntityManager()->getRepository("wms:Pessoa\Papel\Cliente");
                        $clienteEn = $clienteRepo->findOneBy(array('codClienteExterno' => $pedidoProdutoEn->getPedido()->getPessoa()->getCodClienteExterno()));
                        $rota = $clienteEn->getRota();
                        if ($rota != null) $codQuebra = $rota->getId();


                        if ($codQuebra == 0){
                            $nomRota = "(SEM ROTA DEFINIDA)";
                        } else {
                            $rotaEn = $this->getEntityManager()->getRepository('wms:MapaSeparacao\Rota')->find($codQuebra);
                            $nomRota = $rotaEn->getNomeRota();
                        }
                        $dscPD = "ROTA: $codQuebra - $nomRota";
                        break;
                    case ModeloSeparacao::QUEBRA_PULMAO_DOCA_PRACA:
                        $clienteRepo = $this->getEntityManager()->getRepository("wms:Pessoa\Papel\Cliente");
                        $codQuebra = $clienteRepo->getCodPracaByClienteId($pedidoProdutoEn->getPedido()->getPessoa()->getCodClienteExterno());
                        if ($codQuebra == 0){
                            $nomPraca = "(SEM PRAÇA DEFINIDA)";
                        } else {
                            $pracaEn = $this->getEntityManager()->getRepository("wms:MapaSeparacao\Praca")->find($codQuebra);
                            $nomPraca = $pracaEn->getNomePraca();
                        }
                        $dscPD = "PRACA: $codQuebra - $nomPraca";
                        break;
                    case ModeloSeparacao::QUEBRA_PULMAO_DOCA_CLIENTE:
                        $cliente = $pedidoProdutoEn->getPedido()->getPessoa();
                        $nomCliente = $cliente->getPessoa()->getNome();
                        $codQuebra = $cliente->getCodClienteExterno();
                        $dscPD = "CLIENTE: $codQuebra - $nomCliente";
                        break;
                }
                $dscQuebra = "PULMÃO-DOCA $dscPD";
                $codQuebra = 2;
            }

            //CROSS-DOCKING
            elseif ($quebra == MapaSeparacaoQuebra::QUEBRA_CROSS_DOCKING) {
                $dscQuebra = "MAPA DE CROSS-DOCKING";
                $codQuebra = 0;
            }

            $arrQuebras[$quebra] = [
                'codQuebra' => $codQuebra,
                'dscQuebra' => $dscQuebra
            ];
        }

        $quebrasConcat = [];
        foreach ($arrQuebras as $tipoQuebra => $quebra) {
            $quebrasConcat[] = "$tipoQuebra:$quebra[codQuebra]";
        }
        if (!empty($quebrasConcat)) {
            $strQuebrasConcat = implode("_", $quebrasConcat);
        } else {
            $strQuebrasConcat = "SEM_QUEBRAS";
        }
        return array($strQuebrasConcat, $arrQuebras);
    }

    //pega o codigo de picking do produto ou caso o produto nao tenha picking pega o FIFO da reserva de saida (pulmao)
    public function getDepositoEnderecoProdutoSeparacao($produtoEntity, $idExpedicao, $idVolume = 0)
    {
        $produtoId = $produtoEntity->getId();
        $grade = $produtoEntity->getGrade();

        $sql = "SELECT RE.COD_DEPOSITO_ENDERECO AS ID_ENDERECO, (NVL(SUM(REP.QTD_RESERVADA),0) + NVL(SUM(ES.QTD_PRODUTO), 0) + NVL(SUM(MS.QTD_EMBALAGEM), 0))* - 1 QUANTIDADE
                FROM RESERVA_ESTOQUE RE
                INNER JOIN RESERVA_ESTOQUE_EXPEDICAO REE ON REE.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                LEFT JOIN
                  (SELECT SUM(ES.QTD_PRODUTO) QTD_PRODUTO, ES.COD_DEPOSITO_ENDERECO
                  FROM ETIQUETA_SEPARACAO ES
                  INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                  INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                  WHERE C.COD_EXPEDICAO = $idExpedicao
                    AND ES.COD_PRODUTO = '$produtoId'
                    AND ES.DSC_GRADE = '$grade'
                  GROUP BY ES.COD_DEPOSITO_ENDERECO) ES ON ES.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                LEFT JOIN (SELECT SUM(MSC.QTD_EMBALAGEM) QTD_EMBALAGEM, MSP.COD_DEPOSITO_ENDERECO
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  WHERE E.COD_EXPEDICAO = $idExpedicao
                    AND MSP.COD_PRODUTO = '$produtoId'
                    AND MSP.DSC_GRADE = '$grade'
                  GROUP BY MSP.COD_DEPOSITO_ENDERECO) MS ON MS.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                WHERE REE.COD_EXPEDICAO = $idExpedicao
                AND REP.COD_PRODUTO = '$produtoId' AND REP.DSC_GRADE = '$grade'
                AND NVL(REP.COD_PRODUTO_VOLUME,0) = '$idVolume'
                AND RE.IND_ATENDIDA = 'N'
                GROUP BY RE.COD_DEPOSITO_ENDERECO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getEtiquetaMae($pedidoProduto, $quebras, $expedicaoEntity = null){
        if ($expedicaoEntity == null) {
            $expedicaoEntity = $pedidoProduto->getPedido()->getCarga()->getExpedicao();
        }

        $codExpedicao    = $expedicaoEntity->getId();
        $qtdQuebras  = 0;
        $SQL_Quebras = "";
        $codCliente = "";
        $nomCliente = "";
        $codPraca = "";
        $nomPraca = "";
        $numRua = "";
        $dscRua = "";
        $codLinhaSeparacao = "";
        $nomLinha = "";

        foreach ($quebras as $quebra) {
            //CLIENTE
            $quebra = $quebra['tipoQuebra'];
            if ($quebra == MapaSeparacaoQuebra::QUEBRA_CLIENTE)  {
                $codCliente = $pedidoProduto->getPedido()->getPessoa()->getCodClienteExterno();
                $nomCliente = $pedidoProduto->getPedido()->getPessoa()->getPessoa()->getNome();
                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras . "(Q.IND_TIPO_QUEBRA = 'C' and Q.COD_QUEBRA = '" . $codCliente."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //RUA
            if ($quebra == MapaSeparacaoQuebra::QUEBRA_RUA) {
                $numRua = 0;
                $embalagens = $pedidoProduto->getProduto()->getEmbalagens();
                if (count($embalagens) >0) $endereco = $embalagens[0]->getEndereco();
                $volumes = $pedidoProduto->getProduto()->getVolumes();
                if (count($volumes) >0) $endereco = $volumes[0]->getEndereco();
                if (isset($endereco)) {
                    $numRua = $endereco->getRua();
                    $dscRua = $numRua;
                } else {
                    $dscRua = "SEM ENDEREÇO DE PICKING";
                }

                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras. "(Q.IND_TIPO_QUEBRA = 'R' and Q.COD_QUEBRA = '".$numRua."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //LINHA DE SEPARAÇÃO
            if ($quebra == MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO) {
                $linhaSeparacao = $pedidoProduto->getProduto()->getLinhaSeparacao()->getId();
                if (empty($linhaSeparacao)) {
                    $codProduto = $pedidoProduto->getProduto()->getId();
                    $dscGrade = $pedidoProduto->getProduto()->getGrade();
                    throw new \Exception("O produto $codProduto - $dscGrade não tem uma linha de separação definida");
                }
                $codLinhaSeparacao = $pedidoProduto->getProduto()->getLinhaSeparacao()->getId();
                $nomLinha = $pedidoProduto->getProduto()->getLinhaSeparacao()->getDescricao();
                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras ."(Q.IND_TIPO_QUEBRA = 'L' and Q.COD_QUEBRA = '".$codLinhaSeparacao."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //PRAÇA
            if ($quebra == MapaSeparacaoQuebra::QUEBRA_PRACA) {
                $clienteRepo = $this->getEntityManager()->getRepository("wms:Pessoa\Papel\Cliente");
                $codPraca = $clienteRepo->getCodPracaByClienteId($pedidoProduto->getPedido()->getPessoa()->getCodClienteExterno());
                if ($codPraca == 0){
                    $nomPraca = "Sem Praça Definida";
                } else {
                    $pracaEn = $this->getEntityManager()->getRepository("wms:MapaSeparacao\Praca")->find($codPraca);
                    $nomPraca = $pracaEn->getNomePraca();
                }
                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras ."(Q.IND_TIPO_QUEBRA = 'P' and Q.COD_QUEBRA = '".$codPraca."')";
                $qtdQuebras = $qtdQuebras + 1;
            }
        }

        if ($qtdQuebras >0) {
            $SQL_Quebras = " AND (".$SQL_Quebras.")";
        }

        $SQL = " SELECT E.COD_ETIQUETA_MAE, QTD_QUEBRA.QTD_QUEBRAS
                   FROM ETIQUETA_MAE E
                   LEFT JOIN (SELECT E.COD_ETIQUETA_MAE, COUNT(Q.COD_QUEBRA) as QTD_QUEBRAS
                                FROM ETIQUETA_MAE E
                                LEFT JOIN ETIQUETA_MAE_QUEBRA Q ON E.COD_ETIQUETA_MAE = Q.COD_ETIQUETA_MAE
                               GROUP BY E.COD_ETIQUETA_MAE) QTD_QUEBRA ON QTD_QUEBRA.COD_ETIQUETA_MAE = E.COD_ETIQUETA_MAE
                   LEFT JOIN ETIQUETA_MAE_QUEBRA Q ON Q.COD_ETIQUETA_MAE = E.COD_ETIQUETA_MAE
                  WHERE E.COD_EXPEDICAO = $codExpedicao AND QTD_QUEBRA.QTD_QUEBRAS = $qtdQuebras
                        $SQL_Quebras
                  GROUP BY E.COD_ETIQUETA_MAE,QTD_QUEBRA.QTD_QUEBRAS" ;

        if ($qtdQuebras >0) {
            $SQL = $SQL . " HAVING COUNT(*) = QTD_QUEBRA.QTD_QUEBRAS";
        }

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if(count($result) >0) {
            $etiquetaMae = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaMae")->find($result[0]['COD_ETIQUETA_MAE']);
        } else {
            $etiquetaMae = new EtiquetaMae();
            $etiquetaMae->setCodExpedicao($codExpedicao);
            $etiquetaMae->setExpedicao($expedicaoEntity);
            $etiquetaMae->setDscQuebra("");
            $this->getEntityManager()->persist($etiquetaMae);
            $etiquetaMae->setId("11".$etiquetaMae->getId());
            $this->getEntityManager()->persist($etiquetaMae);
            $this->getEntityManager()->flush();
            $novoId = $etiquetaMae->getId();
            $this->getEntityManager()->clear($etiquetaMae);
            $etiquetaMae = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaMae")->find($novoId);
            $dscQuebra = "";
            $codQuebra = 0;
            foreach ($quebras as $quebra) {
                $quebra = $quebra['tipoQuebra'];
                if ($dscQuebra != "") {
                    $dscQuebra = $dscQuebra . ", ";
                }

                if ($quebra == MapaSeparacaoQuebra::QUEBRA_CLIENTE)  {
                    $codQuebra = $codCliente;
                    $dscQuebra = $dscQuebra . "CLIENTE: " . $codCliente . " - " .$nomCliente;
                }
                if ($quebra == MapaSeparacaoQuebra::QUEBRA_RUA) {
                    $dscQuebra = $dscQuebra . "RUA: " . $dscRua;
                    $codQuebra = $numRua;
                }
                if ($quebra == MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO) {
                    $dscQuebra = $dscQuebra . "LINHA: " . $codLinhaSeparacao . " - " . $nomLinha;
                    $codQuebra = $codLinhaSeparacao;
                }
                if ($quebra == MapaSeparacaoQuebra::QUEBRA_PRACA) {
                    $dscQuebra = $dscQuebra . "PRACA: " . $codPraca . " - " . $nomPraca;
                    $codQuebra = $codPraca;
                }
                $etqQuebra = new EtiquetaMaeQuebra();
                $etqQuebra->setEtiquetaMae($etiquetaMae);
                $etqQuebra->setIndTipoQuebra($quebra);
                $etqQuebra->setCodQuebra($codQuebra);
                $this->getEntityManager()->persist($etqQuebra);

            }
            $etiquetaMae->setDscQuebra(trim($dscQuebra));
            $this->getEntityManager()->persist($etiquetaMae);
            $this->getEntityManager()->flush();
        }
        return $etiquetaMae;
    }

    private function iniciaMapaSeparacao($codExpedicao,$codStatus){


        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:expedicao\MapaSeparacao');
        $this->mapas = array();

        $SQL = "
            SELECT *
              FROM MAPA_SEPARACAO_QUEBRA MSQ
             INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSQ.COD_MAPA_SEPARACAO
             WHERE MS.COD_EXPEDICAO = $codExpedicao
               AND MS.COD_STATUS = $codStatus 
        ";

        $result =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $arrayMapas = array();
        foreach ($result as $mapa) {
            $idMapa = $mapa['COD_MAPA_SEPARACAO'];
            $tipoQuebra = $mapa['IND_TIPO_QUEBRA'];
            $idQuebra = $mapa['COD_QUEBRA'];
            $arrayMapas[$idMapa][] = array(
                $tipoQuebra => $idQuebra
            );
        }

        $quebraReentrega = MapaSeparacaoQuebra::QUEBRA_REENTREGA;
        $quebraCarrinho = MapaSeparacaoQuebra::QUEBRA_CARRINHO;
        $quebraFracionavel = MapaSeparacaoQuebra::QUEBRA_UNID_FRACIONAVEL;
        $quebraCliente = MapaSeparacaoQuebra::QUEBRA_CLIENTE;
        $quebraRua = MapaSeparacaoQuebra::QUEBRA_RUA;
        $quebraLinha = MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO;
        $quebraPraca = MapaSeparacaoQuebra::QUEBRA_PRACA;
        $quebraPD = MapaSeparacaoQuebra::QUEBRA_PULMAO_DOCA;
        $quebraRota = MapaSeparacaoQuebra::QUEBRA_ROTA;

        foreach ($arrayMapas as $idMapa => $mapa) {

            $idReentrega = "N";
            $idCarrinho = "N";
            $idUnidFracionavel = "N";
            $idCliente = 0;
            $idRua = 0;
            $idLinhaSeparacao = 0;
            $idPraca = 0;
            $idPulmaoDoca = "N";
            $idRota = 0;

            foreach ($mapa as $tipoQuebra => $idQuebra) {
                if ($tipoQuebra == $quebraReentrega) {
                    //MAPA DE REENTREGA
                    $idReentrega = $quebraReentrega;
                } else if ($tipoQuebra == $quebraCarrinho) {
                    //UTILIZA CARRINHO
                    $idCarrinho = $quebraCarrinho;
                } else if ($tipoQuebra == $quebraFracionavel) {
                    //UTILIZA CARRINHO
                    $idUnidFracionavel = $quebraFracionavel;
                } else if ($tipoQuebra == $quebraCliente)  {
                    //CLIENTE
                    $idCliente = $idQuebra;
                } else if ($tipoQuebra == $quebraRua) {
                    //RUA
                    $idRua = $idQuebra;
                } else if ($tipoQuebra == $quebraLinha) {
                    //LINHA DE SEPARAÇÃO
                    $idLinhaSeparacao = $idQuebra;
                } else if ($tipoQuebra == $quebraPraca) {
                    //PRAÇA
                    $idPraca = $idQuebra;
                } else if ($tipoQuebra == $quebraPD) {
                    //PULMAO-DOCA
                    $idPulmaoDoca = $quebraPD;
                } else if ($tipoQuebra == $quebraRota) {
                    //PULMAO-DOCA
                    $idRota = $quebraRota;
                }
            }

            $this->mapas[$idReentrega][$idCarrinho][$idUnidFracionavel][$idCliente][$idRua][$idLinhaSeparacao][$idPraca][$idRota][$idPulmaoDoca] = $mapaSeparacaoRepo->find($idMapa);
        }

    }

    public function getMapaSeparacao($quebras, $siglaEntity, $expedicaoEntity){

        $idReentrega = "N";
        $idCarrinho = "N";
        $idFracionavel = "N";
        $idCliente = 0;
        $idRua = 0;
        $idLinhaSeparacao = 0;
        $idPraca = 0;
        $idRota = 0;
        $idPulmaoDoca = "N";

        $arrDscQuebra = [];
        foreach ($quebras as $tipo => $quebra) {

            $arrDscQuebra[] = $quebra['dscQuebra'];

            //MAPA DE REENTREGA
            if ($tipo == MapaSeparacaoQuebra::QUEBRA_REENTREGA) {
                $idReentrega = $quebra['codQuebra'];
            }

            //UTILIZA CARRINHO
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_CARRINHO) {
                $idCarrinho = $quebra['codQuebra'];
            }

            //PRODUTOS FRACIONÁVEIS
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_UNID_FRACIONAVEL) {
                $idFracionavel = $quebra['codQuebra'];
            }

            //CLIENTE
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_CLIENTE)  {
                $idCliente = $quebra['codQuebra'];
            }

            //RUA
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_RUA) {
                $idRua = $quebra['codQuebra'];
            }

            //LINHA DE SEPARAÇÃO
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO) {
                $idLinhaSeparacao = $quebra['codQuebra'];
            }

            //PRAÇA
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_PRACA) {
                $idPraca = $quebra['codQuebra'];
            }

            //PULMAO-DOCA
            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_PULMAO_DOCA) {
                $idPulmaoDoca = $quebra['codQuebra'];
            }

            elseif ($tipo == MapaSeparacaoQuebra::QUEBRA_ROTA) {
                $idRota = $quebra['codQuebra'];
            }
        }

        if (isset($this->mapas[$idReentrega][$idCarrinho][$idFracionavel][$idCliente][$idRua][$idLinhaSeparacao][$idPraca][$idRota][$idPulmaoDoca])) {
            $mapaSeparacao = $this->mapas[$idReentrega][$idCarrinho][$idFracionavel][$idCliente][$idRua][$idLinhaSeparacao][$idPraca][$idRota][$idPulmaoDoca];
        } else {

            $selectId = "SELECT SQ_MAPA_SEPARACAO_01.NEXTVAL FROM DUAL";
            $newIdMapa = $this->_em->getConnection()->query($selectId)->fetchAll(\PDO::FETCH_ASSOC);

            $mapaSeparacao = new MapaSeparacao("12" . $newIdMapa[0]['NEXTVAL']);
            $mapaSeparacao->setExpedicao($expedicaoEntity);
            $mapaSeparacao->setStatus($siglaEntity);
            $mapaSeparacao->setCodStatus($siglaEntity->getId());
            $mapaSeparacao->setDataCriacao(new \DateTime());

            $dsQuebraConcat = implode(", ", $arrDscQuebra);
            $mapaSeparacao->setDscQuebra($dsQuebraConcat);

            $this->getEntityManager()->persist($mapaSeparacao);

            foreach ($quebras as $tipo => $quebra) {
                $mapaQuebra = new MapaSeparacaoQuebra();
                $mapaQuebra->setMapaSeparacao($mapaSeparacao);
                $mapaQuebra->setTipoQuebra($tipo);
                $mapaQuebra->setCodQuebra($quebra['codQuebra']);
                $this->getEntityManager()->persist($mapaQuebra);
            }
            $this->getEntityManager()->persist($mapaSeparacao);

            $this->mapas[$idReentrega][$idCarrinho][$idFracionavel][$idCliente][$idRua][$idLinhaSeparacao][$idPraca][$idRota][$idPulmaoDoca] = $mapaSeparacao;
        }

        return $mapaSeparacao;
    }

    /**
     * @param $statusEntity
     * @param $produtoEntity
     * @param $pedidoEntity
     * @param $quantidade
     * @param $volumeEntity
     * @param $embalagemEntity
     * @param $referencia
     * @param $etiquetaMae
     * @param $depositoEndereco
     * @param $verificaReconferencia
     * @param $etiquetaConferenciaRepo EtiquetaConferenciaRepository
     * @return EtiquetaSeparacao
     */
    public function salvaNovaEtiqueta($statusEntity, $produtoEntity, $pedidoEntity, $quantidade, $volumeEntity,$embalagemEntity, $referencia, $etiquetaMae, $depositoEndereco, $verificaReconferencia, $etiquetaConferenciaRepo, $tipoSeparacao, $lote = Produto\Lote::LND){

        $arrayEtiqueta['produtoVolume']        = $volumeEntity;
        $arrayEtiqueta['produtoEmbalagem']     = $embalagemEntity;
        $arrayEtiqueta['produto']              = $produtoEntity;
        $arrayEtiqueta['grade']                = $produtoEntity;
        $arrayEtiqueta['pedido']               = $pedidoEntity;
        $arrayEtiqueta['qtdProduto']           = $quantidade;
        $arrayEtiqueta['codReferencia']        = $referencia;
        $arrayEtiqueta['etiquetaMae']          = $etiquetaMae;
        $arrayEtiqueta['codDepositoEndereco']  = $depositoEndereco;
        $arrayEtiqueta['tipoSaida']            = $tipoSeparacao;
        $arrayEtiqueta['lote']                 = (!in_array($lote, [Produto\Lote::NCL, Produto\Lote::LND])) ? $lote : null;

        if ($embalagemEntity == null) {
            $arrayEtiqueta['qtdEmbalagem'] = 1;
        } else {
            $arrayEtiqueta['qtdEmbalagem'] = $embalagemEntity->getQuantidade();
        }

        $etiqueta = $this->save($arrayEtiqueta,$statusEntity);

        if ($verificaReconferencia=='S'){
            $arrayEtiqueta['codEtiquetaSeparacao']=$etiqueta->getId();
            $arrayEtiqueta['expedicao']= $pedidoEntity->getCarga()->getExpedicao();
            $etiquetaConferenciaRepo->save($arrayEtiqueta,$statusEntity) ;
        }

        return $etiqueta;
    }

    public function salvaMapaSeparacaoProduto ($mapaSeparacaoEntity,$produtoEntity,$quantidadePedido,$volumeEntity,$embalagemEntity,$arrPedidoProduto,$depositoEndereco,$dadosConsolidado = null,$pedidoEntity = null, $arrays = null, $consolidado = 'N', $lote = Produto\Lote::LND, $arrMapaPedProd = []) {

        if ($arrays == null) {
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaProdutoRepo */
            $mapaProdutoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        }    else {
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaProdutoRepo */
            $mapaProdutoRepo = $arrays['mapaSeparacaoProduto'];
        }

        $mapaProduto = null;
        $quantidadeEmbalagem = 1;
        if ($volumeEntity != null) {
            $mapaProduto = $mapaProdutoRepo->findOneBy(array("mapaSeparacao"=>$mapaSeparacaoEntity,'produtoVolume'=>$volumeEntity));
        }
        if ($embalagemEntity != null) {
            $quantidadeEmbalagem = $embalagemEntity->getQuantidade();
            $mapaProduto = null;
            $mapaProdutos = $mapaProdutoRepo->findBy(array("mapaSeparacao"=>$mapaSeparacaoEntity, 'produtoEmbalagem'=>$embalagemEntity, 'depositoEndereco' => $depositoEndereco, 'lote' => $lote));
            if (!empty($mapaProdutos)) {
                if ($consolidado == 'S') {
                    if (!empty($pedidoEntity)) {
                        $pessoaIdPedido = $pedidoEntity->getPessoa()->getId();
                        foreach ($mapaProdutos as $item) {
                            $pessoaId = $item->getPedidoProduto()->getPedido()->getPessoa()->getId();
                            if ($pessoaIdPedido == $pessoaId) {
                                $mapaProduto = $item;
                                break;
                            }
                        }
                    }
                } else {
                    $mapaProduto = $mapaProdutos[0];
                }
            }
        }

        /** @var PedidoProduto $pedidoProduto */
        foreach ($arrPedidoProduto as $item) {
            $index = $mapaSeparacaoEntity->getId()."-".$item['entity']->getId();

            /** @var MapaSeparacaoPedido $mapaPedidoEn */
            $mapaPedidoEn = null;

            if (isset($arrMapaPedProd[$index])) {
                /** @var MapaSeparacaoPedido $mapaPedidoEn */
                $mapaPedidoEn = $arrMapaPedProd[$index];
            }
            if (empty($mapaPedidoEn)) {
                $mapaPedidoEn = new MapaSeparacaoPedido();
                $mapaPedidoEn->setCodPedidoProduto($item['entity']->getId());
                $mapaPedidoEn->setMapaSeparacao($mapaSeparacaoEntity);
                $mapaPedidoEn->setPedidoProduto($item['entity']);
                $mapaPedidoEn->setQtd($item['qtd']);
                $mapaPedidoEn->setQtdCortada(0);
            } else {
                $mapaPedidoEn->setQtd(Math::adicionar($mapaPedidoEn->getQtd(), $item['qtd']));
            }
            $arrMapaPedProd[$index] = $mapaPedidoEn;
        }

        if ($mapaProduto == null) {
            $mapaProduto = new MapaSeparacaoProduto();
            $mapaProduto->setCodProduto($produtoEntity->getId());
            $mapaProduto->setDscGrade($produtoEntity->getGrade());
            $mapaProduto->setMapaSeparacao($mapaSeparacaoEntity);
            $mapaProduto->setProduto($produtoEntity);
            $mapaProduto->setProdutoEmbalagem($embalagemEntity);
            $mapaProduto->setProdutoVolume($volumeEntity);
            $mapaProduto->setQtdSeparar($quantidadePedido);
            $mapaProduto->setQtdEmbalagem($quantidadeEmbalagem);
            $mapaProduto->setLote((!in_array($lote, [Produto\Lote::NCL, Produto\Lote::LND])) ? $lote : null);
            if (!empty($arrPedidoProduto)) {
                $pedidoproduto = reset($arrPedidoProduto);
                $pedidoproduto = reset($pedidoproduto);
                $mapaProduto->setCodPedidoProduto($pedidoproduto->getId());
                $mapaProduto->setPedidoProduto($pedidoproduto);
            }

            $mapaProduto->setQtdCortado(0);
            $mapaProduto->setIndConferido('N');
            $mapaProduto->setDepositoEndereco($depositoEndereco);
            if ($consolidado == 'S') {
                $mapaProduto->setCubagem(number_format(floatval(str_replace(',','',$dadosConsolidado['cubagem'])),6,".",''));
                $mapaProduto->setNumCarrinho($dadosConsolidado['carrinho']);
                $mapaProduto->setNumCaixaInicio($dadosConsolidado['caixaInicio']);
                $mapaProduto->setNumCaixaFim($dadosConsolidado['caixaFim']);
            }
        } else {
            $mapaProduto->setQtdSeparar($mapaProduto->getQtdSeparar() + $quantidadePedido);
        }

        $this->_em->persist($mapaProduto);

        return $arrMapaPedProd;
    }

    /**
     * @param $idExpedicao
     */
    public function finalizaEtiquetasSemConferencia($idExpedicao, $central)
    {

        $expedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao $expedicao */
        $expedicao = $expedicaoRepo->find($idExpedicao);

        if ($expedicao->getStatus()->getId() == $expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
            $novoStatus = EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
            $this->finalizaEtiquetaByStatus($idExpedicao, EtiquetaSeparacao::STATUS_CONFERIDO , $novoStatus, $central);
            $this->finalizaEtiquetaByStatus($idExpedicao, EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO , $novoStatus , $central);
        } else {
            $novoStatus = EtiquetaSeparacao::STATUS_CONFERIDO;
        }
        $this->finalizaEtiquetaByStatus($idExpedicao, EtiquetaSeparacao::STATUS_ETIQUETA_GERADA , $novoStatus, $central);
        $this->_em->flush();

        $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();
        if ($verificaReconferencia=='S'){
            $idStatus=$expedicao->getStatus()->getId();
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $EtiquetaConfRepo */
            $EtiquetaConfRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaConferencia');

            if (($idStatus==Expedicao::STATUS_PRIMEIRA_CONFERENCIA) || ($idStatus==Expedicao::STATUS_EM_SEPARACAO)){
                $novoStatus = Expedicao::STATUS_PRIMEIRA_CONFERENCIA;
                $etiquetas = $this->getEtiquetasByExpedicao($idExpedicao, EtiquetaSeparacao::STATUS_CONFERIDO, $central);
                foreach($etiquetas as $etiqueta) {
                    $etiquetaEntity = $EtiquetaConfRepo->findOneBy(array('codEtiquetaSeparacao'=>$etiqueta['codBarras']));
                    $this->alteraStatus($etiquetaEntity, $novoStatus);
                }

            }
            if ($idStatus==Expedicao::STATUS_SEGUNDA_CONFERENCIA){
                $novoStatus = Expedicao::STATUS_SEGUNDA_CONFERENCIA;
                $etiquetas = $this->getEtiquetasByExpedicao($idExpedicao, EtiquetaSeparacao::STATUS_CONFERIDO, $central);
                foreach($etiquetas as $etiqueta) {
                    $etiquetaEntity = $EtiquetaConfRepo->findOneBy(array('codEtiquetaSeparacao'=>$etiqueta['codBarras']));
                    if ($etiquetaEntity->getStatus()->getId() == Expedicao::STATUS_PRIMEIRA_CONFERENCIA) {
                        $this->alteraStatus($etiquetaEntity, $novoStatus);
                    }
                }

            }
        }

        $this->_em->flush();
    }

    private function finalizaEtiquetaByStatus ($idExpedicao, $statusBuscado, $novoStatus , $central) {
        $etiquetas = $this->getEtiquetasByExpedicao($idExpedicao, $statusBuscado, $central);
        $etiquetaRepository = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        foreach($etiquetas as $etiqueta) {
            $etiquetaEntity = $etiquetaRepository->find($etiqueta['codBarras']);
            $this->alteraStatus($etiquetaEntity, $novoStatus);
            $this->incrementaQtdAtentidaOuCortada($etiqueta['codBarras'], 'atendida');
        }
    }

    public function getEtiquetasByFaixa($codBarrasInicial,$codBarrasFinal, $apontamento = false) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("es")
            ->from("wms:Expedicao\EtiquetaSeparacao","es")
            ->where("es.id >= $codBarrasInicial AND es.id <= $codBarrasFinal");

        if ($apontamento)
            $dql->andWhere("es.status <> " . EtiquetaSeparacao::STATUS_CORTADO);

        return $dql->getQuery()->getResult();
    }

    /**
     * @param $etiquetaEntity
     * @param $status
     */
    public function alteraStatus($etiquetaEntity, $status)
    {
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);
        $etiquetaEntity->setStatus($statusEntity);
        $this->_em->persist($etiquetaEntity);
    }

    /**
     * @param $senhaDigitada
     * @return bool
     */
    public function checkAutorizacao($senhaDigitada)
    {
        $senhaAutorizacao = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 3, 'constante' => 'SENHA_AUTORIZAR_DIVERGENCIA'));
        $senhaAutorizacao = $senhaAutorizacao->getValor();
        return $senhaDigitada == $senhaAutorizacao;
    }

    private function cortaEtiquetaReentrega($etiquetaEntity){
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoReentregaRepository $EtiquetaReentregaRepo */
        $EtiquetaReentregaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacaoReentrega');

        $etiquetaReentregaEn = $EtiquetaReentregaRepo->findOneBy(array('codEtiquetaSeparacao'=>$etiquetaEntity->getId()));
        if ($etiquetaReentregaEn == null) return false;

        $statusCortadoEntity = $this->getEntityManager()->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_CORTADO);
        $etiquetaReentregaEn->setCodStatus(EtiquetaSeparacao::STATUS_CORTADO);
        $etiquetaReentregaEn->setStatus($statusCortadoEntity);
        $this->getEntityManager()->persist($etiquetaReentregaEn);

        if ($etiquetaEntity->getCodReferencia() != null) {
            $etiquetasRelacionadasEn = $this->findBy(array('codReferencia'=>$etiquetaEntity->getCodReferencia()));
            $etiquetaPrincipal = $this->findBy(array('id'=>$etiquetaEntity->getCodReferencia()));
            $etiquetasRelacionadasEn = array_merge($etiquetasRelacionadasEn, $etiquetaPrincipal);
        } else {
            $etiquetasRelacionadasEn = $this->findBy(array('codReferencia'=>$etiquetaEntity->getId()));
        }

        if ($etiquetasRelacionadasEn != null) {
            $statusPendenteCorteEntity = $this->getEntityManager()->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_PENDENTE_CORTE);

            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiqueta */
            foreach ($etiquetasRelacionadasEn as $etiqueta) {
                $etiquetaReentregaRelacionadaEn = $EtiquetaReentregaRepo->findOneBy(array('codEtiquetaSeparacao'=>$etiqueta->getId()));
                if ($etiquetaReentregaRelacionadaEn != null) {
                    if ($etiquetaReentregaRelacionadaEn->getCodStatus() != EtiquetaSeparacao::STATUS_CORTADO) {
                        $etiquetaReentregaRelacionadaEn->setCodStatus(EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                        $etiquetaReentregaRelacionadaEn->setStatus($statusPendenteCorteEntity);
                        $this->getEntityManager()->persist($etiquetaReentregaRelacionadaEn);
                    }
                }
            }
        }

        $this->getEntityManager()->flush();
        return true;

    }

    /**
     * @param $etiquetaEntity
     */
    public function cortar($etiquetaEntity, $motivoEn = null)
    {
        if ($etiquetaEntity->getStatus()->getId() == EtiquetaSeparacao::STATUS_CORTADO) {
            throw new \Exception("Etiqueta " . $etiquetaEntity->getId() . " ja se encontra cortada");
        }

        if ($this->cortaEtiquetaReentrega($etiquetaEntity)) {
            return true;
        }

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo   = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoque');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepository */
        $expedicaoRepository = $this->_em->getRepository('wms:Expedicao');

        if ($etiquetaEntity->getCodReferencia() != null) {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetasRelacionadasEn */
            $etiquetasRelacionadasEn = $EtiquetaRepo->findBy(array('codReferencia'=>$etiquetaEntity->getCodReferencia()));
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetasRelacionadasEn */
            $etiquetaPrincipal = $EtiquetaRepo->findBy(array('id'=>$etiquetaEntity->getCodReferencia()));
            $etiquetasRelacionadasEn = array_merge($etiquetasRelacionadasEn, $etiquetaPrincipal);
        } else {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetasRelacionadasEn */
            $etiquetasRelacionadasEn = $EtiquetaRepo->findBy(array('codReferencia'=>$etiquetaEntity->getId()));
        }

        if ($etiquetasRelacionadasEn != null) {

            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiqueta */
            foreach ($etiquetasRelacionadasEn as $etiqueta) {
                if ($etiqueta->getStatus()->getId() != EtiquetaSeparacao::STATUS_CORTADO) {
                    $this->alteraStatus($etiqueta,EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                }
            }
        }

        if ((is_null($etiquetaEntity->getCodReferencia()) && !is_null($etiquetaEntity->getProdutoVolume())) || $etiquetaEntity->getProdutoEmbalagem()) {
            if ($motivoEn != null) {
                $idPedido = $etiquetaEntity->getPedido()->getId();
                $codProduto = $etiquetaEntity->getCodProduto();
                $grade = $etiquetaEntity->getGrade();

                $pedidoProdutoRepo   = $this->_em->getRepository('wms:Expedicao\PedidoProduto');
                $pedidoProdutoEn = $pedidoProdutoRepo->findOneBy(array(
                    'codPedido' => $idPedido,
                    'codProduto' => $codProduto,
                    'grade' => $grade
                ));

                $pedidoProdutoEn->setMotivoCorte($motivoEn);
                $pedidoProdutoEn->setCodMotivoCorte($motivoEn->getId());
                $this->getEntityManager()->persist($pedidoProdutoEn);
            }
            $EtiquetaRepo->incrementaQtdAtentidaOuCortada($etiquetaEntity->getId(), 'cortada');
        }

        $this->alteraStatus($etiquetaEntity,EtiquetaSeparacao::STATUS_CORTADO);
        $this->_em->flush();

        $codProduto = $etiquetaEntity->getCodProduto();
        $grade = $etiquetaEntity->getDscGrade();
        $idExpedicao = $etiquetaEntity->getPedido()->getCarga()->getExpedicao()->getId();

        $idEmbalagem = null;
        $idVolume = null;
        if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
            $idEmbalagem = $etiquetaEntity->getProdutoEmbalagem()->getId();
        } else {
            $idVolume = $etiquetaEntity->getProdutoVolume()->getId();
        }

        $produtos = array();
        $produto = array();
        $produto['codProdutoEmbalagem'] = $idEmbalagem;
        $produto['codProdutoVolume'] = $idVolume;
        $produto['codProduto'] = $codProduto;
        $produto['grade'] = $grade;
        $produto['qtd'] = 1;
        $produtos[] = $produto;

        $reservaEstoque = $reservaEstoqueRepo->findReservaEstoque($etiquetaEntity->getDepositoEndereco(),$produtos,"S","E", array('expedicao' => $idExpedicao, 'pedido'=> $etiquetaEntity->getPedido()->getId()));
        $maiorQtd = null;
        if ($reservaEstoque != NULL) {
            $produtosReserva = $reservaEstoque->getProdutos();
            foreach ($produtosReserva as $produtoReserva) {
                $encontrouProduto = false;
                foreach ($produtos as $produto) {
                    if ($produtoReserva->getCodProdutoEmbalagem() == NULL) {
                        if ($produto['codProdutoVolume'] == $produtoReserva->getCodProdutoVolume()) {
                            $encontrouProduto = true;
                        }
                    } else {
                        $encontrouProduto = true;
                    }
                }

                if ($encontrouProduto ==true) {
                    $produtoReserva->setQtd($produtoReserva->getQtd()+$etiquetaEntity->getQtdProduto());
                    $this->_em->persist($produtoReserva);
                }
            }
            $this->_em->flush();
            $reservaZerada = true;
            foreach ($produtosReserva as $produtoReserva) {
                if ($produtoReserva->getQtd() <0) $reservaZerada = false;
            }

            if ($reservaZerada == true) {
                $reservaEstoqueRepo->cancelaReservaEstoque($etiquetaEntity->getDepositoEndereco(),$produtos,"S","E", array('expedicao' => $idExpedicao, 'pedido'=> $etiquetaEntity->getPedido()->getId()));
            }
            $this->_em->flush();

        }

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepository  */
        $ExpedicaoRepository = $this->_em->getRepository('wms:Expedicao');
        $pedidosNaoCancelados = $ExpedicaoRepository->countPedidosNaoCancelados($idExpedicao);

        if ($pedidosNaoCancelados == 0) {

            $qtdCorte     = $this->getEtiquetasByStatus(EtiquetaSeparacao::STATUS_CORTADO,$idExpedicao);
            $qtdEtiquetas = $this->getEtiquetasByStatus(null,$idExpedicao);

            $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CORTADO;
            $reentregasCortadas = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao, $status);
            $reentregasTotal = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao, null);


            if (($qtdCorte == $qtdEtiquetas) AND (count($reentregasCortadas) == count($reentregasTotal))) {
                $ExpedicaoEn = $ExpedicaoRepository->find($idExpedicao);
                $ExpedicaoRepository->alteraStatus($ExpedicaoEn, Expedicao::STATUS_CANCELADO);
                $this->_em->flush();
            }
        }

        if ($this->getSystemParameterValue('TIPO_INTEGRACAO_CORTE') == 'I') {
            $resultAcao = $expedicaoRepository->integraCortesERP($pedidoProdutoEn, $codProduto, $grade, $etiquetaEntity->getQtdProduto(), $motivoEn->getDscMotivo());
            if ($resultAcao == false)
                return 'Corte Não Efetuado no ERP! Verifique o log de erro';
        }

        return true;
    }

    /**
     * @param $idCargaExterno
     * @param $idTipoCarga
     * @return array
     */
    public function getEtiquetasByCargaExterno($idCargaExterno, $idTipoCarga, $statusEtiqueta = null)
    {
        try{

        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $SQL = " SELECT C.COD_CARGA_EXTERNO as idCarga,
                        TC.DSC_SIGLA as tipoCarga,
                        TPE.COD_EXTERNO as tipoPedido,
                        P.COD_EXTERNO as codPedido,
                        ES.COD_ETIQUETA_SEPARACAO AS codEtiqueta,
                        ES.COD_PRODUTO as codProduto,
                        ES.DSC_GRADE as grade,
                        NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as dscVolume,
                        ES.dth_conferencia as dthConferencia,
                        ES.COD_STATUS as codStatus, 
                        SE.DSC_SIGLA as status,
                        ES.DSC_REIMPRESSAO as reimpressao,
                        NVL(PE.COD_BARRAS, PV.COD_BARRAS) as codBarrasProduto
                   FROM ETIQUETA_SEPARACAO ES
                   LEFT JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                   LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                   LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                   LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ES.COD_PRODUTO_VOLUME
                   LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
                   LEFT JOIN SIGLA TC ON TC.COD_SIGLA = C.COD_TIPO_CARGA
                   LEFT JOIN TIPO_PEDIDO_EXPEDICAO TPE ON TPE.COD_TIPO_PEDIDO_EXPEDICAO = P.COD_TIPO_PEDIDO
                   LEFT JOIN SIGLA SE ON SE.COD_SIGLA = ES.COD_STATUS
                  WHERE C.COD_CARGA_EXTERNO = $idCargaExterno AND C.COD_TIPO_CARGA = $idTipoCarga ";

        if (is_array($statusEtiqueta)) {
            $status = implode(',',$statusEtiqueta);
            $SQL = $SQL & " AND ES.COD_STATUS IN ($status) ";
        }else if ($statusEtiqueta) {
            $SQL = $SQL & " AND ES.COD_STATUS = $statusEtiqueta ";
        }

        $result =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $etqArray = array();
        foreach ($result as $row) {
            $idEtiquetaSeparacao = $row['CODETIQUETA'];
            $etqSeparacaoEn = $this->find($idEtiquetaSeparacao);
            $embalagemEn = $etqSeparacaoEn->getProdutoEmbalagem();

            $codBarrasArray = array();
            if ($embalagemEn == null) {
                $codBarrasArray[] = $row['CODBARRASPRODUTO'];
            } else {
                $embalagensEn = $embalagemRepo->findBy(array(
                    'codProduto'=>$embalagemEn->getCodProduto(),
                    'grade'=>$embalagemEn->getGrade(),
                    'quantidade'=>$embalagemEn->getQuantidade()));

                foreach ($embalagensEn as $emb){
                    $codBarrasArray[] = $emb->getCodigoBarras();
                }
            }

            $value = array(
                'idCarga'=>$row['IDCARGA'],
                'tipoCarga'=>$row['TIPOCARGA'],
                'tipoPedido'=>$row['TIPOPEDIDO'],
                'codPedido'=>$row['CODPEDIDO'],
                'codEtiqueta'=>$row['CODETIQUETA'],
                'codProduto'=>$row['CODPRODUTO'],
                'grade'=>$row['GRADE'],
                'dscVolume'=>$row['DSCVOLUME'],
                'dthConferencia'=>$row['DTHCONFERENCIA'],
                'codStatus'=>$row['CODSTATUS'],
                'status'=>$row['STATUS'],
                'reimpressao'=>$row['REIMPRESSAO'],
                'codBarrasProduto'=>$codBarrasArray
            );

            $etqArray[] = $value;

        }

        return $etqArray;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }


    }

    public function getEtiquetasByExpedicaoAndVolumePatrimonio($idExpedicao, $volumePatrimonio)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('wms:Expedicao\EtiquetaSeparacao','e')
            ->innerJoin('e.pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->where('c.expedicao = :idExpedicao')
            ->andwhere('e.volumePatrimonio = :volumePatrimonio')
            ->setParameter('idExpedicao', $idExpedicao)
            ->setParameter('volumePatrimonio', $volumePatrimonio);

        return $dql->getQuery()->getResult();

    }

    public function buscarEtiqueta($parametros)
    {
        ini_set('memory_limit', '-1');
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('es.id, es.codProduto, es.reimpressao, es.codStatus, es.dscGrade, s.sigla, e.id as idExpedicao,
             c.codCargaExterno as tipoCarga, prod.id as produto, prod.descricao, 
             CASE WHEN pe.descricao IS NULL THEN pv.descricao ELSE pe.descricao END as embalagem')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->leftJoin('es.pedido', 'p')
            ->leftJoin('p.itinerario', 'i')
            ->leftJoin('es.produto', 'prod')
            ->leftJoin('p.carga', 'c')
            ->leftJoin('c.expedicao', 'e')
            ->leftJoin('es.status', 's')
            ->leftJoin('es.produtoEmbalagem', 'pe')
            ->leftJoin('es.produtoVolume', 'pv')
            ->leftJoin('p.pessoa', 'cli')
            ->orderBy("es.id" , "DESC")
            ->distinct(true);

        if (!empty($parametros['etiqueta'])) {
            $source
                ->setParameter('idEtiqueta', $parametros['etiqueta'])
                ->andWhere('es.id = :idEtiqueta');
        }

        if (!empty($parametros['codCliente'])) {
            $source
                ->setParameter('codCliente', $parametros['codCliente'])
                ->andWhere('cli.id = :codCliente');
        }

        if (!empty($parametros['codCarga'])) {
            $source
                ->setParameter('codCarga', $parametros['codCarga'])
                ->andWhere('c.codCargaExterno = :codCarga');
        }

        if (!empty($parametros['codProduto'])) {
            $source
                ->setParameter('codProduto', $parametros['codProduto'])
                ->andWhere('es.codProduto = :codProduto');
        }

        if (isset($parametros['reimpresso'])){
            if ($parametros['reimpresso'] != "") {
                if ($parametros['reimpresso'] == 'S') {
                    $source->andWhere("es.reimpressao is not null");
                } else {
                    $source->andWhere("es.reimpressao is null");
                }
            }
        }

        if (!empty($parametros['pedido'])) {
            $source
                ->setParameter('codPedido', $parametros['pedido'])
                ->andWhere('p.codExterno = :codPedido');
        }

        if (!empty($parametros['situacao'])) {
            $source
                ->setParameter('situacao', $parametros['situacao'])
                ->andWhere('es.status = :situacao');
        }

        if (!empty($parametros['codExpedicao'])) {
            $source
                ->setParameter('idExpedicao', $parametros['codExpedicao'])
                ->andWhere('e.id = :idExpedicao');
        }

        if (!empty($parametros['grade'])) {
            $source
                ->setParameter('grade', $parametros['grade'])
                ->andWhere('es.dscGrade = :grade');
        }

        if (!empty($parametros['centralEstoque'])) {
            $source
                ->setParameter('centralEstoque', $parametros['centralEstoque'])
                ->andWhere('p.centralEntrega = :centralEstoque');
        }

        if (!empty($parametros['centralTransbordo'])) {
            $source
                ->setParameter('centralTransbordo', $parametros['centralTransbordo'])
                ->andWhere('p.pontoTransbordo = :centralTransbordo');
        }

        if (!empty($parametros['itinerario'])) {
            $source
                ->setParameter('itinerario', $parametros['itinerario'])
                ->andWhere('i.id = :itinerario');
        }

        if (!empty($parametros['dataInicial1'])) {
            $dataInicial1 = str_replace('/', '-', $parametros['dataInicial1']);
            $dataI1 = new \DateTime($dataInicial1);
            $dataI1->setTime(0,0);
            $source
                ->setParameter('dataInicial1', $dataI1->format('Y-m-d H:i:s'))
                ->andWhere('e.dataInicio >= :dataInicial1');
        }

        if (!empty($parametros['dataInicial2'])) {
            $dataInicial2 = str_replace('/', '-', $parametros['dataInicial2']);
            $dataI2 = new \DateTime($dataInicial2);
            $dataI2->setTime(23,59);
            $source
                ->setParameter('dataInicial2', $dataI2->format('Y-m-d H:i:s'))
                ->andWhere('e.dataInicio <= :dataInicial2');
        }

        if (!empty($parametros['dataFinal1'])) {
            $dataFinal1 = str_replace("/", "-", $parametros['dataFinal1']);
            $dataF1 = new \DateTime($dataFinal1);
            $dataF1->setTime(0,0);
            $source
                ->setParameter('dataFinal1', $dataF1->format('Y-m-d H:i:s'))
                ->andWhere('e.dataFinalizacao >= :dataFinal1');
        }

        if (!empty($parametros['dataFinal2'])) {
            $dataFinal2 = str_replace("/", "-", $parametros['dataFinal2']);
            $dataF2 = new \DateTime($dataFinal2);
            $dataF2->setTime(23,59);
            $source
                ->setParameter('dataFinal2', $dataF2->format('Y-m-d H:i:s'))
                ->andWhere('e.dataFinalizacao <= :dataFinal2');
        }

        return $source->getQuery()->getResult();
    }

    public function getDadosEtiquetaByEtiquetaId($idEtiqueta)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('es.id,
                      es.codProduto,
                      p.id as pedido,
                      es.codOS,
                      p.centralEntrega,
                      p.pontoTransbordo,
                      es.reimpressao,
                      es.codStatus,
                      es.dscGrade,
                      s.sigla,
                      e.id as idExpedicao,
                      e.dataInicio,
                      c.codCargaExterno as tipoCarga,
                      prod.id as produto,
                      prod.descricao,
                      CASE WHEN pe.descricao IS NULL THEN pv.descricao ELSE pe.descricao END as embalagem,
                      i.descricao as itinerario,
                      pess.nome as clienteNome,
                      es.dataConferencia,
                      es.dataConferenciaTransbordo,
                      es.codOSTransbordo,
                      cli.codClienteExterno,
                      usuarioPessoa.login,
                      usuarioTransbordo.login as loginTransbordo,
                      siglaEpx.sigla as siglaEpxedicao')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin('es.pedido', 'p')
            ->innerJoin('p.itinerario', 'i')
            ->innerJoin('p.pessoa', 'cli')
            ->innerJoin('cli.pessoa', 'pess')
            ->leftJoin('wms:OrdemServico', 'os', 'WITH', 'es.codOS = os.id')
            ->leftJoin('wms:OrdemServico', 'osT', 'WITH', 'es.codOSTransbordo = osT.id')
            ->leftJoin('wms:Usuario', 'usuarioPessoa', 'WITH', 'os.pessoa = usuarioPessoa.pessoa')
            ->leftJoin('wms:Usuario', 'usuarioTransbordo', 'WITH', 'osT.pessoa = usuarioTransbordo.pessoa')
            ->leftJoin('es.produto', 'prod')
            ->leftJoin('p.carga', 'c')
            ->leftJoin('c.expedicao', 'e')
            ->leftJoin('es.status', 's')
            ->leftJoin('wms:Util\Sigla', 'siglaEpx', 'WITH', 'e.status = siglaEpx.id')
            ->leftJoin('es.produtoEmbalagem', 'pe')
            ->leftJoin('es.produtoVolume','pv')
            ->where('es.id = :idEtiqueta')
            ->setParameter('idEtiqueta', $idEtiqueta)
            ->distinct(true);

        return $source->getQuery()->getResult();
    }

    public function incrementaQtdAtentidaOuCortada($idEtiqueta, $tipo)
    {
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
        $pedidoProdutoRepo = $this->_em->getRepository('wms:Expedicao\PedidoProduto');
        $etiquetaEntity     = $this->findOneBy(array('id' => $idEtiqueta));
        $qtdProdutoEtiqueta    = $etiquetaEntity->getQtdProduto();
        $codPedido          = $etiquetaEntity->getPedido()->getId();
        $codProduto = $etiquetaEntity->getProduto()->getId();
        $grade = $etiquetaEntity->getProduto()->getGrade();
        $pedidoProdutoEntity = $pedidoProdutoRepo->findOneBy(array('codPedido' => $codPedido,'codProduto'=>$codProduto, 'grade'=>$grade));

        if ($tipo == 'atendida') {
            $qtdProdutoAtendida  = $pedidoProdutoEntity->getQtdAtendida();

            $somaFinal = $qtdProdutoEtiqueta + $qtdProdutoAtendida;

            $pedidoProdutoEntity->setQtdAtendida($somaFinal);
            $this->_em->persist($pedidoProdutoEntity);
            $this->_em->flush($pedidoProdutoEntity);
        } else {
            $qtdProdutoCortada  = $pedidoProdutoEntity->getQtdCortada();

            $somaFinal = $qtdProdutoEtiqueta + $qtdProdutoCortada;

            $pedidoProdutoEntity->setQtdCortada($somaFinal);
            $this->_em->persist($pedidoProdutoEntity);
            $this->_em->flush($pedidoProdutoEntity);
        }
    }

    public function getEtiquetasReentrega($idExpedicao, $codStatus = null, $central = null, $idEtiquetas = null) {
        $SQL = "
        SELECT ES.COD_ETIQUETA_SEPARACAO as ETIQUETA,
               ESR.COD_ES_REENTREGA,
               PROD.COD_PRODUTO,
               PROD.DSC_GRADE,
               PROD.DSC_PRODUTO PRODUTO,
               NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME,
               PES.NOM_PESSOA as CLIENTE,
               P.COD_EXTERNO as PEDIDO,
               C.COD_CARGA_EXTERNO AS CARGA,
               CA.COD_CARGA_EXTERNO AS CARGA_ANTIGA
         FROM REENTREGA R
         LEFT JOIN CARGA C ON C.COD_CARGA = R.COD_CARGA
        INNER JOIN ETIQUETA_SEPARACAO ES ON ES.COD_REENTREGA = R.COD_REENTREGA
         LEFT JOIN ETIQUETA_SEPARACAO_REENTREGA ESR ON ESR.COD_ETIQUETA_SEPARACAO = ES.COD_ETIQUETA_SEPARACAO AND ESR.COD_REENTREGA = R.COD_REENTREGA
         LEFT JOIN PEDIDO P ON ES.COD_PEDIDO = P.COD_PEDIDO
         LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
         LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ES.COD_PRODUTO_VOLUME
         LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = ES.COD_PRODUTO AND PROD.DSC_GRADE = ES.DSC_GRADE
         LEFT JOIN PESSOA PES ON P.COD_PESSOA = PES.COD_PESSOA

         INNER JOIN NOTA_FISCAL_SAIDA NFS ON R.COD_NOTA_FISCAL_SAIDA = NFS.COD_NOTA_FISCAL_SAIDA
         INNER JOIN NOTA_FISCAL_SAIDA_PEDIDO NFSP ON NFSP.COD_NOTA_FISCAL_SAIDA = NFS.COD_NOTA_FISCAL_SAIDA
         INNER JOIN PEDIDO PED ON NFSP.COD_PEDIDO = PED.COD_PEDIDO
         INNER JOIN CARGA CA ON PED.COD_CARGA = CA.COD_CARGA

         WHERE 1 = 1
           AND C.COD_EXPEDICAO = $idExpedicao
        ";

        if ($codStatus != null) {
            $SQL = $SQL . " AND ESR.COD_STATUS = $codStatus";
        }
        if ($idEtiquetas != null) {
            if (is_array($idEtiquetas)) {
                $idEtiquetas = implode(",",$idEtiquetas);
            }
            $SQL = $SQL . " AND ES.COD_ETIQUETA_SEPARACAO IN ($idEtiquetas) ";
        }

        if ($central != null) {
            $SQL = $SQL . " AND P.PONTO_TRANSBORDO = $central";
        }
        $SQL .= " GROUP BY ES.COD_ETIQUETA_SEPARACAO,
                   PROD.COD_PRODUTO,
                   PROD.DSC_PRODUTO,
                   PROD.DSC_GRADE,
                   PE.DSC_EMBALAGEM, PV.DSC_VOLUME,
                   PES.NOM_PESSOA,
                   P.COD_EXTERNO,
                   C.COD_CARGA_EXTERNO,
                   ESR.COD_ES_REENTREGA,
                   CA.COD_CARGA_EXTERNO";

        $SQL = $SQL . " ORDER BY ES.COD_ETIQUETA_SEPARACAO";
        $result =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEtiquetaPendenteImpressao($idExpedicao, $codStatus = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('em.id, em.dscQuebra')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin('wms:Expedicao\EtiquetaMae', 'em', 'WITH', 'em.id = es.etiquetaMae')
            ->where("em.codExpedicao = $idExpedicao")
            ->andWhere("es.codStatus = $codStatus")
            ->groupBy('em.id, em.dscQuebra');

        return $sql->getQuery()->getResult();
    }

    public function getProdutoByEtiqueta($codEndereco, $expedicao){
        $tipoSaida = ReservaEstoqueExpedicao::SAIDA_PULMAO_DOCA;
        $SQL = "SELECT DE.DSC_DEPOSITO_ENDERECO, ES.COD_ETIQUETA_SEPARACAO,P.DSC_PRODUTO,P.DSC_GRADE,ES.QTD_PRODUTO,P.COD_PRODUTO 
                FROM ETIQUETA_SEPARACAO ES 
                INNER JOIN PRODUTO P ON (P.COD_PRODUTO = ES.COD_PRODUTO AND P.DSC_GRADE = ES.DSC_GRADE)
                INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = ES.COD_PEDIDO
                INNER JOIN CARGA C ON C.COD_CARGA = PED.COD_CARGA
                INNER JOIN DEPOSITO_ENDERECO DE ON ES.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                WHERE ES.COD_DEPOSITO_ENDERECO = $codEndereco AND C.COD_EXPEDICAO = $expedicao AND ES.TIPO_SAIDA = $tipoSaida";
        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
