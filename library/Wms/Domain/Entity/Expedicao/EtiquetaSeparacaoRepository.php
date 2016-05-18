<?php
namespace Wms\Domain\Entity\Expedicao;

use Core\Grid\Exception;
use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\NotaFiscal;

class EtiquetaSeparacaoRepository extends EntityRepository
{

    /**
     * @param $idExpedicao
     * @return int
     */
    public function getCountEtiquetasByExpedicao ($idExpedicao)
    {
        $produtos = $this->getEntityManager()->createQueryBuilder()
            ->select("p.id, p.grade, SUM(pp.quantidade) quantidade")
            ->from("wms:Expedicao\PedidoProduto", "pp")
            ->innerJoin("pp.produto", "p")
            ->innerJoin("pp.pedido", "ped")
            ->innerJoin("ped.carga", "c")
            ->leftJoin("p.volumes", "v")
            ->where("c.expedicao = " . $idExpedicao)
            ->groupBy("p.id, p.grade")->getQuery()->getResult();

        $qtdTotal = 0;
        foreach ($produtos as $produto) {
            $qtdTotal = $qtdTotal + $produto['quantidade'];
        }

        return $qtdTotal;
    }

    /**
     * @param $idPedido
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
            ->andWhere('es.codStatus = :Status')
            ->setParameter('idExpedicao', $expedicaoEn->getId())
            ->setParameter('Status', $status);

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
                      c.placaCarga, ped.pontoTransbordo, c.codCargaExterno, c.sequencia,
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
            ->groupBy('c.placaCarga, c.codCargaExterno, c.sequencia')
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
                      es.pontoTransbordo,
                      CASE WHEN es.codStatus = 522 THEN 'PENDENTE DE IMPRESSÃO'
                           WHEN es.codStatus = 523 THEN 'PENDENTE DE CONFERENCIA'
                           ELSE 'Consulte o admnistrador do sistema'
                      END as pendencia,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      etq.dataConferencia")
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
            ->leftJoin('etq.produto','p')
            ->leftJoin('etq.produtoEmbalagem','emb')
            ->leftJoin('etq.produtoVolume','vol')
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
            ->orderBy('es.codCargaExterno, es.codBarras, p.descricao, es.codProduto, es.grade');

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
    
    public function getEtiquetasByExpedicao($idExpedicao = null, $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $pontoTransbordo = null, $idEtiquetas = null)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('etq.id, es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, etq.codEtiquetaMae,
                    IDENTITY(etq.produtoEmbalagem) as codProdutoEmbalagem, etq.qtdProduto, p.id pedido, de.descricao endereco, c.sequencia
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq' , 'WITH', 'etq.id = es.codBarras')
            ->leftjoin('etq.codDepositoEndereco', 'de')
            ->distinct(true);

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

        return $dql->getQuery()->getResult();

    }

    public function getEtiquetasReimpressaoByFaixa($codigoInicial, $codigoFinal)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(' es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->leftJoin('wms:Expedicao\EtiquetaSeparacao','etq','WITH','etq.id = es.codBarras')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->andWhere('etq.id >= '.$codigoInicial)
            ->andWhere('etq.id <= '.$codigoFinal)
            ->andWhere('etq.reimpressao IS NULL')
            ->orderBy("es.codBarras","DESC");
        ;

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
            ->select(' es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.codStatus, s.sigla status, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.placaCarga, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, es.codEstoque, es.pontoTransbordo,
                    emb.embalado,
                    exp.id as reentregaExpedicao,
                    r.id as codReentrega,
                    CASE WHEN emb.descricao    IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                    CASE WHEN emb.CBInterno    IS NULL THEN vol.CBInterno ELSE emb.CBInterno END as CBInterno,
                    CASE WHEN emb.codigoBarras IS NULL THEN vol.codigoBarras ELSE emb2.codigoBarras END as codBarrasProduto
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Util\Sigla', 's', 'WITH', 'es.codStatus = s.id')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
            ->leftJoin('etq.reentrega','r')
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
            ->select(' es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, es.codBarrasProduto, c.sequencia, p.id pedido,
					IDENTITY(etq.produtoEmbalagem) as codProdutoEmbalagem, etq.qtdProduto
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
			->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq' , 'WITH', 'etq.id = es.codBarras')
            ->where('es.codBarras = :id')
            ->setParameter('id', $id);

        return $dql->getQuery()->getSingleResult();

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

    /**
     * @param array $dadosEtiqueta
     * @param int $status
     * @return int
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
            $etiquetaMae=$EtiquetaMaeRepo->find($dadosEtiqueta['codEtiquetaMae']);
            $enEtiquetaSeparacao->setEtiquetaMae($etiquetaMae);
        }


        \Zend\Stdlib\Configurator::configure($enEtiquetaSeparacao, $dadosEtiqueta);

        $this->_em->persist($enEtiquetaSeparacao);
        $enEtiquetaSeparacao->setId("10".$enEtiquetaSeparacao->getId());
        $this->_em->persist($enEtiquetaSeparacao);
        return $enEtiquetaSeparacao->getId();
    }


    public function geraEtiquetaReentrega ($etiquetaSeparacanEn, $reentregaEn) {
        $statusReentrega = $this->_em->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA);

        $etiquetaSeparacanEn->setReentrega($reentregaEn);
        $etiquetaSeparacanEn->setCodReentrega($reentregaEn);
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

    public function defineEtiquetaReentrega ($pedidos ,$codProduto, $grade, $qtdReentregar, $numReentrega) {
        $etiquetaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacao");
        $reentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Reentrega");
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

    public function geraMapaReentrega($produtoEntity, $quantidade, $expedicaoEntity){

        if ($quantidade <= 0) return;

        $modeloSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao");
        $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
        $quebras = array(0=>array('tipoQuebra'=>'RE'));
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO);
        $codProduto = $produtoEntity->getId();
        $grade = $produtoEntity->getGrade();
        $modeloSeparacaoEn = $modeloSeparacaoRepo->find($idModeloSeparacao);


        if ($produtoEntity->getVolumes()->count() > 0) {
            $arrayVolumes = $produtoEntity->getVolumes()->toArray();

            usort($arrayVolumes, function ($a,$b){
                return $a->getCodigoSequencial() < $b->getCodigoSequencial();
            });

            foreach ($arrayVolumes as $volumeEntity) {
                $mapaSeparacao = $this->getMapaSeparacao(null,$quebras, $statusEntity, $expedicaoEntity);
                $this->salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,$quantidade,$volumeEntity,null,null, null);
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
                        if ($embalagem->getQuantidade() <= $quantidadeAtender) {
                            $embalagemAtual = $embalagem;
                            break;
                        }
                    }
                    if ($embalagemAtual == null) {
                        $mensagem = 'Não existe embalagem para Atender o PRODUTO '.$codProduto.' GRADE '.$grade.' com a quantidade restante de '.$quantidadeAtender.' produtos';
                        throw new \Exception($mensagem);
                    }
                } else {
                    $embalagemAtual = $menorEmbalagem;
                }

                $quantidadeRestantePedido = $quantidadeRestantePedido - $embalagemAtual->getQuantidade();

                $mapaSeparacao = $this->getMapaSeparacao(null,$quebras,$statusEntity, $expedicaoEntity);
                $this->salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,1,null,$embalagemAtual, null, null);
            }

        }

    }

    public function gerarMapaEtiquetaReentrega($idExpedicao){
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
        $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
        $reentregaRepo   = $this->getEntityManager()->getRepository("wms:Expedicao\Reentrega");
        $nfProdutoRepo   = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaidaPedido");
        $nfSaidaRepo     = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");
        $expedicaoRepo   = $this->getEntityManager()->getRepository("wms:Expedicao");
        $produtoRepo     = $this->getEntityManager()->getRepository("wms:Produto");

        $produtos = $reentregaRepo->getItemNotasByExpedicao($idExpedicao);
        $expedicaoEn = $expedicaoRepo->find($idExpedicao);

        foreach ($produtos as $produto) {
            $numNF = $produto['COD_NOTA_FISCAL_SAIDA'];
            $qtdReentregue = $produto['QUANTIDADE'];
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            $numReentrega = $produto['COD_REENTREGA'];

            $produtoEn = $produtoRepo->findOneBy(array('id' =>$codProduto,'grade'=> $grade));
            $pedidos = $nfProdutoRepo->findBy(array('codNotaFiscalSaida'=>$numNF));

            $qtdMapa = $this->defineEtiquetaReentrega($pedidos,$codProduto,$grade,$qtdReentregue, $numReentrega);
            $this->geraMapaReentrega($produtoEn, $qtdMapa, $expedicaoEn, $numReentrega);

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

    /**
     * @param array $pedidosProdutos
     * @param int $status
     * @return int
     */
    public function gerarMapaEtiqueta($idExpedicao, array $pedidosProdutos, $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $idModeloSeparacao)
    {
        $this->getEntityManager()->beginTransaction();
        $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        $filialRepository = $this->getEntityManager()->getRepository('wms:Filial');

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao");

        $etiquetaConferenciaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaConferencia");
        $verificaReentrega = $this->getSystemParameterValue('RECONFERENCIA_EXPEDICAO');

        try {
            if ( empty($status) ){
                $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO;
            }
            $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);

            /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            $modeloSeparacaoEn = $modeloSeparacaoRepo->find($idModeloSeparacao);
            $quebrasFracionado = $modeloSeparacaoRepo->getQuebraFracionado($idModeloSeparacao);
            $quebrasNaoFracionado = $modeloSeparacaoRepo->getQuebraNaoFracionado($idModeloSeparacao);

            foreach($pedidosProdutos as $key => $pedidoProduto) {
                $expedicaoEntity = $pedidoProduto->getPedido()->getCarga()->getExpedicao();

                /** @var \Wms\Domain\Entity\Produto $produtoEntity */
                $pedidoEntity   = $pedidoProduto->getPedido();
                $produtoEntity  = $pedidoProduto->getProduto();
                $quantidade     = $pedidoProduto->getQuantidade() - $pedidoProduto->getQtdCortada();

                $pedidoEntity->setIndEtiquetaMapaGerado("S");
                $this->getEntityManager()->persist($pedidoEntity);

                if ($quantidade <= 0) {
                    continue;
                }

                if ($produtoEntity->getVolumes()->count() > 0) {
                    $arrayVolumes = $produtoEntity->getVolumes()->toArray();

                    usort($arrayVolumes, function ($a,$b){
                        return $a->getCodigoSequencial() < $b->getCodigoSequencial();
                    });

                    if ($modeloSeparacaoEn->getTipoSeparacaoNaoFracionado() == "E") {
                        for($i=0;$i<$quantidade;$i++) {
                            $codReferencia = null;
                            foreach ($arrayVolumes as $volumeEntity) {

                                if (!is_null($volumeEntity->getDataInativacao()))
                                    continue;

                                if ($modeloSeparacaoEn->getUtilizaEtiquetaMae() == "N") $quebrasNaoFracionado = array();
                                $etiquetaMae = $this->getEtiquetaMae($pedidoProduto,$quebrasNaoFracionado);

                                $endereco = $volumeEntity->getEndereco();
                                if (isset($endereco) && !empty($endereco)) {
                                    $depositoEnderecoEn = $volumeEntity->getEndereco();
                                } else {
                                    $filial = $filialRepository->findOneBy(array('codExterno'=> $pedidoProduto->getPedido()->getCentralEntrega()));
                                    if ($filial == null) {
                                        throw new Exception ("Filial " . $pedidoProduto->getPedido()->getCentralEntrega() . " não encontrada");
                                    }
                                    if ($filial->getIndUtilizaRessuprimento() == "S") {
                                        $enderecosPulmao = $this->getDepositoEnderecoProdutoSeparacao($produtoEntity, $idExpedicao, $volumeEntity->getId());
                                        $idDepositoEndereco = null;
                                        foreach ($enderecosPulmao as $enderecoPulmao) {
                                            $idDepositoEndereco = $enderecoPulmao['COD_DEPOSITO_ENDERECO'];
                                        }
                                        if ($idDepositoEndereco != null) {
                                            $depositoEnderecoEn = $depositoEnderecoRepo->find($idDepositoEndereco);
                                        }
                                    }
                                }

                                $idEtiqueta = $this->salvaNovaEtiqueta($statusEntity,$produtoEntity,$pedidoEntity,1,$volumeEntity,null,$codReferencia,$etiquetaMae,$depositoEnderecoEn, $verificaReentrega,$etiquetaConferenciaRepo);
                                if ($codReferencia == null) {
                                    $codReferencia = $idEtiqueta;
                                }
                            }
                        }
                    } else {
                        foreach ($arrayVolumes as $volumeEntity) {

                            if (!is_null($volumeEntity->getDataInativacao()))
                                continue;

                            $endereco = $volumeEntity->getEndereco();
                            if (isset($endereco) && !empty($endereco)) {
                                $depositoEnderecoEn = $volumeEntity->getEndereco();
                            } else {
                                $filial = $filialRepository->findOneBy(array('codExterno'=> $pedidoProduto->getPedido()->getCentralEntrega()));
                                if ($filial == null) {
                                    throw new Exception ("Filial " . $pedidoProduto->getPedido()->getCentralEntrega() . " não encontrada");
                                }
                                if ($filial->getIndUtilizaRessuprimento() == "S") {
                                    $enderecosPulmao = $this->getDepositoEnderecoProdutoSeparacao($produtoEntity, $idExpedicao, $volumeEntity->getId());
                                    $idDepositoEndereco = null;
                                    foreach ($enderecosPulmao as $enderecoPulmao) {
                                        $idDepositoEndereco = $enderecoPulmao['COD_DEPOSITO_ENDERECO'];
                                    }
                                    if ($idDepositoEndereco != null) {
                                        $depositoEnderecoEn = $depositoEnderecoRepo->find($idDepositoEndereco);
                                    }
                                }
                            }

                            $mapaSeparacao = $this->getMapaSeparacao($pedidoProduto,$quebrasNaoFracionado, $statusEntity, $expedicaoEntity);
                            $this->salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,$quantidade,$volumeEntity,null,$pedidoProduto,$depositoEnderecoEn);
                        }
                    }
                }
                else if ($produtoEntity->getEmbalagens()->count() > 0) {
                    $codProduto = $pedidoProduto->getProduto()->getId();
                    $grade = $pedidoProduto->getProduto()->getGrade();
                    $embalagensEn = $this->getEntityManager()->getRepository('wms:Produto\Embalagem')->findBy(array('codProduto'=>$codProduto,'grade'=>$grade),array('quantidade'=>'DESC'));

                    $quantidadeRestantePedido = $quantidade;

                    $qtdEmbalagemPadraoRecebimento = 1;
                    foreach ($embalagensEn as $embalagem) {
                        $endereco = $embalagem->getEndereco();
                        if ($endereco != null){
                            $depositoEnderecoEn = $endereco;
                        } else {
                            $filial = $filialRepository->findOneBy(array('codExterno'=> $pedidoProduto->getPedido()->getCentralEntrega()));
                            if ($filial == null) {
                                throw new Exception ("Filial " . $pedidoProduto->getPedido()->getCentralEntrega() . " não encontrada");
                            }
                            if ($filial->getIndUtilizaRessuprimento() == "S") {
                                $enderecosPulmao = $this->getDepositoEnderecoProdutoSeparacao($produtoEntity, $idExpedicao);
                            }
                        }
                        if ($embalagem->getIsPadrao() == "S") {
                            $qtdEmbalagemPadraoRecebimento = $embalagem->getQuantidade();
                            break;
                        }
                    }
                    $menorEmbalagem = $embalagensEn[count($embalagensEn) -1];

                    while ($quantidadeRestantePedido > 0) {
                        $embalagemAtual = null;
                        $quantidadeAtender = $quantidadeRestantePedido;

                        if (isset($enderecosPulmao) && !empty($enderecosPulmao)) {
                            foreach ($enderecosPulmao as $key => $enderecoPulmao) {
                                if ($enderecoPulmao['quantidade'] > 0) {
                                    $quantidadeAtender = $enderecoPulmao['quantidade'];
                                    break;
                                }
                            }
                        }

                        if ($modeloSeparacaoEn->getUtilizaCaixaMaster() == 'S') {
                            foreach ($embalagensEn as $embalagem) {
                                if ($embalagem->getQuantidade() <= $quantidadeAtender) {
                                    $embalagemAtual = $embalagem;
                                    break;
                                }
                            }
                            if ($embalagemAtual == null) {
                                $mensagem = 'Não existe embalagem para Atender o PRODUTO '.$codProduto.' GRADE '.$grade.' com a quantidade restante de '.$quantidadeAtender.' produtos';
                                throw new \Exception($mensagem);
                            }
                        } else {
                            $embalagemAtual = $menorEmbalagem;
                        }

                        $quantidadeRestantePedido = $quantidadeRestantePedido - $embalagemAtual->getQuantidade();

                        if (isset($enderecosPulmao) && !empty($enderecosPulmao)) {
                            $enderecoPulmao['QUANTIDADE'] = $enderecoPulmao['QUANTIDADE'] - $embalagemAtual->getQuantidade();
                            $idDepositoEndereco = $enderecoPulmao['COD_DEPOSITO_ENDERECO'];
                            $depositoEnderecoEn = $depositoEnderecoRepo->find($idDepositoEndereco);
                        }

                        if (!is_null($embalagemAtual->getDataInativacao()))
                            continue;

                        if ($embalagemAtual->getQuantidade() >= $qtdEmbalagemPadraoRecebimento) {
                            if ($modeloSeparacaoEn->getTipoSeparacaoNaoFracionado() == "E") {
                                if ($modeloSeparacaoEn->getUtilizaEtiquetaMae() == "N") {
                                    $quebrasNaoFracionado = array();
                                }
                                $etiquetaMae = $this->getEtiquetaMae($pedidoProduto,$quebrasNaoFracionado);
                                $this->salvaNovaEtiqueta($statusEntity,$produtoEntity,$pedidoEntity,$embalagemAtual->getQuantidade(),null,$embalagemAtual,null,$etiquetaMae,$depositoEnderecoEn, $verificaReentrega, $etiquetaConferenciaRepo);
                            }   else {
                                $mapaSeparacao = $this->getMapaSeparacao($pedidoProduto,$quebrasNaoFracionado, $statusEntity, $expedicaoEntity);
                                $this->salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,1,null,$embalagemAtual,$pedidoProduto,$depositoEnderecoEn);
                            }
                        } else {
                            if ($modeloSeparacaoEn->getTipoSeparacaoFracionado() == "E") {
                                if ($modeloSeparacaoEn->getUtilizaEtiquetaMae() == "N") $quebrasFracionado = array();
                                $etiquetaMae = $this->getEtiquetaMae($pedidoProduto,$quebrasFracionado);
                                $this->salvaNovaEtiqueta($statusEntity,$produtoEntity,$pedidoEntity,$embalagemAtual->getQuantidade(),null,$embalagemAtual,null, $etiquetaMae,$depositoEnderecoEn, $verificaReentrega, $etiquetaConferenciaRepo);
                            }   else {
                                $mapaSeparacao = $this->getMapaSeparacao($pedidoProduto,$quebrasFracionado,$statusEntity, $expedicaoEntity);
                                $this->salvaMapaSeparacaoProduto($mapaSeparacao,$produtoEntity,1,null,$embalagemAtual, $pedidoProduto,$depositoEnderecoEn);
                            }
                        }
                    }
                }
                else {
                    $view = \Zend_layout::getMvcInstance()->getView();
                    $link = '<a href="' . $view->url(array('controller' => 'relatorio_produtos-expedicao', 'action' => 'sem-dados', 'id' => $idExpedicao)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos sem Dados Logísticos</a>';
                    $mensagem = 'Existem produtos sem definição de volume. Clique para exibir ' . $link;
                    throw new \Exception($mensagem);
                }
            }

            $this->_em->flush();
            $this->_em->clear();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    //pega o codigo de picking do produto ou caso o produto nao tenha picking pega o FIFO da reserva de saida (pulmao)
    public function getDepositoEnderecoProdutoSeparacao($produtoEntity, $idExpedicao, $idVolume = 0)
    {
        $produtoId = $produtoEntity->getId();
        $grade = $produtoEntity->getGrade();

        $sql = "SELECT RE.COD_DEPOSITO_ENDERECO, NVL(SUM(REP.QTD_RESERVADA),0) + NVL(SUM(ES.QTD_PRODUTO), 0) + NVL(SUM(MS.QTD_EMBALAGEM), 0) quantidade
                FROM RESERVA_ESTOQUE RE
                INNER JOIN RESERVA_ESTOQUE_EXPEDICAO REE ON REE.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                LEFT JOIN
                  (SELECT SUM(ES.QTD_PRODUTO) QTD_PRODUTO, ES.COD_DEPOSITO_ENDERECO
                  FROM ETIQUETA_SEPARACAO ES
                  INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                  INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                  WHERE C.COD_EXPEDICAO = $idExpedicao
                  GROUP BY ES.COD_DEPOSITO_ENDERECO) ES ON ES.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                LEFT JOIN (SELECT SUM(MSC.QTD_EMBALAGEM) QTD_EMBALAGEM, MSP.COD_DEPOSITO_ENDERECO
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  WHERE E.COD_EXPEDICAO = $idExpedicao
                  GROUP BY MSP.COD_DEPOSITO_ENDERECO) MS ON MS.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                WHERE REE.COD_EXPEDICAO = $idExpedicao
                AND REP.COD_PRODUTO = '$produtoId' AND REP.DSC_GRADE = '$grade'
                AND NVL(REP.COD_PRODUTO_VOLUME,0) = '$idVolume'
                AND RE.IND_ATENDIDA = 'N'
                GROUP BY RE.COD_DEPOSITO_ENDERECO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getEtiquetaMae($pedidoProduto, $quebras){
        $expedicaoEntity = $pedidoProduto->getPedido()->getCarga()->getExpedicao();
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
            if ($quebra == "C")  {
                $codCliente = $pedidoProduto->getPedido()->getPessoa()->getCodClienteExterno();
                $nomCliente = $pedidoProduto->getPedido()->getPessoa()->getPessoa()->getNome();
                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras . "(Q.IND_TIPO_QUEBRA = 'C' and Q.COD_QUEBRA = '" . $codCliente."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //RUA
            if ($quebra == "R") {
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
            if ($quebra == "L") {
                $codLinhaSeparacao = $pedidoProduto->getProduto()->getLinhaSeparacao()->getId();
                $nomLinha = $pedidoProduto->getProduto()->getLinhaSeparacao()->getDescricao();
                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras ."(Q.IND_TIPO_QUEBRA = 'L' and Q.COD_QUEBRA = '".$codLinhaSeparacao."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //PRAÇA
            if ($quebra == "P") {
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

                if ($quebra == "C")  {
                    $codQuebra = $codCliente;
                    $dscQuebra = $dscQuebra . "CLIENTE: " . $codCliente . " - " .$nomCliente;
                }
                if ($quebra == "R") {
                    $dscQuebra = $dscQuebra . "RUA: " . $dscRua;
                    $codQuebra = $numRua;
                }
                if ($quebra == "L") {
                    $dscQuebra = $dscQuebra . "LINHA: " . $codLinhaSeparacao . " - " . $nomLinha;
                    $codQuebra = $codLinhaSeparacao;
                }
                if ($quebra == "P") {
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

    public function getMapaSeparacao($pedidoProduto, $quebras, $siglaEntity, $expedicaoEntity){
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
        $codStatus = $siglaEntity->getId();

        foreach ($quebras as $quebra) {
            $quebra = $quebra['tipoQuebra'];

            //MAPA DE REENTREGA
            if ($quebras == "RE") {
                $SQL_Quebras = $SQL_Quebras . "(Q.IND_TIPO_QUEBRA = 'RE')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //CLIENTE
            if ($quebra == "C")  {
                $codCliente = $pedidoProduto->getPedido()->getPessoa()->getCodClienteExterno();
                $nomCliente = $pedidoProduto->getPedido()->getPessoa()->getPessoa()->getNome();
                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras . "(Q.IND_TIPO_QUEBRA = 'C' and Q.COD_QUEBRA = '" . $codCliente."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //RUA
            if ($quebra == "R") {
                $numRua = 0;
                $embalagens = $pedidoProduto->getProduto()->getEmbalagens();
                $volumes = $pedidoProduto->getProduto()->getVolumes();
                if (count($embalagens) >0) $endereco = $embalagens[0]->getEndereco();
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
            if ($quebra == "L") {
                $codLinhaSeparacao = $pedidoProduto->getProduto()->getLinhaSeparacao()->getId();
                $nomLinha = $pedidoProduto->getProduto()->getLinhaSeparacao()->getDescricao();

                if ($qtdQuebras != 0) {
                    $SQL_Quebras = $SQL_Quebras . " OR ";
                }
                $SQL_Quebras = $SQL_Quebras ."(Q.IND_TIPO_QUEBRA = 'L' and Q.COD_QUEBRA = '".$codLinhaSeparacao."')";
                $qtdQuebras = $qtdQuebras + 1;
            }

            //PRAÇA
            if ($quebra == "P") {
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

        $SQL = " SELECT E.COD_MAPA_SEPARACAO, QTD_QUEBRA.QTD_QUEBRAS
                   FROM MAPA_SEPARACAO E
                   LEFT JOIN (SELECT E.COD_MAPA_SEPARACAO, COUNT(Q.COD_QUEBRA) as QTD_QUEBRAS
                                FROM MAPA_SEPARACAO E
                                LEFT JOIN MAPA_SEPARACAO_QUEBRA Q ON E.COD_MAPA_SEPARACAO = Q.COD_MAPA_SEPARACAO
                               GROUP BY E.COD_MAPA_SEPARACAO) QTD_QUEBRA ON QTD_QUEBRA.COD_MAPA_SEPARACAO = E.COD_MAPA_SEPARACAO
                   LEFT JOIN MAPA_SEPARACAO_QUEBRA Q ON Q.COD_MAPA_SEPARACAO = E.COD_MAPA_SEPARACAO
                  WHERE E.COD_EXPEDICAO = $codExpedicao
                        AND QTD_QUEBRA.QTD_QUEBRAS = $qtdQuebras
                        AND E.COD_STATUS = $codStatus
                        $SQL_Quebras
                  GROUP BY E.COD_MAPA_SEPARACAO,QTD_QUEBRA.QTD_QUEBRAS" ;

        if ($qtdQuebras >0) {
            $SQL = $SQL . " HAVING COUNT(*) = QTD_QUEBRA.QTD_QUEBRAS";
        }

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if(count($result) > 0) {
            $mapaSeparacao = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao")->find($result[0]['COD_MAPA_SEPARACAO']);
        } else {
            $mapaSeparacao = new MapaSeparacao();
            $mapaSeparacao->setExpedicao($expedicaoEntity);
            $mapaSeparacao->setStatus($siglaEntity);
            $mapaSeparacao->setCodStatus($codStatus);
            $mapaSeparacao->setDataCriacao(new \DateTime());
            $mapaSeparacao->setDscQuebra("");
                $this->getEntityManager()->persist($mapaSeparacao);
                $mapaSeparacao->setId("12".$mapaSeparacao->getId());
                $this->getEntityManager()->persist($mapaSeparacao);
                $this->getEntityManager()->flush();
                $novoId = $mapaSeparacao->getId();
                $this->getEntityManager()->clear($mapaSeparacao);
                $mapaSeparacao = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao")->find($novoId);
            $dscQuebra = "";
            foreach ($quebras as $quebra) {
                $quebra = $quebra['tipoQuebra'];
                $codQuebra = 0;
                if ($dscQuebra != "") {
                    $dscQuebra = $dscQuebra . ", ";
                }

                if ($quebra == "RE") {
                    $dscQuebra = $dscQuebra . "MAPA DE REENTREGAS";
                    $codQuebra = "";
                }

                if ($quebra == "C")  {
                    $dscQuebra = $dscQuebra . "CLIENTE: " . $codCliente . " - " .$nomCliente;
                    $codQuebra = $codCliente;
                }
                if ($quebra == "R") {
                    $dscQuebra = $dscQuebra . "RUA: " . $dscRua;
                    $codQuebra = $numRua;
                }
                if ($quebra == "L") {
                    $dscQuebra = $dscQuebra . "LINHA: " . $codLinhaSeparacao . " - " . $nomLinha;
                    $codQuebra = $codLinhaSeparacao;
                }
                if ($quebra == "P") {
                    $dscQuebra = $dscQuebra . "PRACA: " . $codPraca . " - " . $nomPraca;
                    $codQuebra = $codPraca;
                }
                $mapaQuebra = new MapaSeparacaoQuebra();
                $mapaQuebra->setMapaSeparacao($mapaSeparacao);
                $mapaQuebra->setTipoQuebra($quebra);
                $mapaQuebra->setCodQuebra($codQuebra);
                $this->getEntityManager()->persist($mapaQuebra);
            }
            $mapaSeparacao->setDscQuebra(trim($dscQuebra));
            $this->getEntityManager()->persist($mapaSeparacao);
            $this->getEntityManager()->flush();
        }
        return $mapaSeparacao;
    }

    public function salvaNovaEtiqueta($statusEntity, $produtoEntity, $pedidoEntity, $quantidade, $volumeEntity,$embalagemEntity, $referencia, $etiquetaMae, $depositoEndereco, $verificaReconferencia, $etiquetaConferenciaRepo){

        $arrayEtiqueta['produtoVolume']        = $volumeEntity;
        $arrayEtiqueta['produtoEmbalagem']     = $embalagemEntity;
        $arrayEtiqueta['produto']              = $produtoEntity;
        $arrayEtiqueta['grade']                = $produtoEntity;
        $arrayEtiqueta['pedido']               = $pedidoEntity;
        $arrayEtiqueta['qtdProduto']           = $quantidade;
        $arrayEtiqueta['codReferencia']        = $referencia;
        $arrayEtiqueta['etiquetaMae']          = $etiquetaMae;
        $arrayEtiqueta['codDepositoEndereco']  = $depositoEndereco;

        $codEtiqueta = $this->save($arrayEtiqueta,$statusEntity);

        if ($verificaReconferencia=='S'){
            $arrayEtiqueta['codEtiquetaSeparacao']=$codEtiqueta;
            $arrayEtiqueta['expedicao']= $pedidoEntity->getCarga()->getExpedicao();
            $etiquetaConferenciaRepo->save($arrayEtiqueta,$statusEntity) ;
        }

        return $codEtiqueta;
    }

    public function salvaMapaSeparacaoProduto ($mapaSeparacaoEntity, $produtoEntity, $quantidadePedido, $volumeEntity,$embalagemEntity,$pedidoProduto,$depositoEndereco) {
        $mapaProdutoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $quantidadeEmbalagem = 1;
        if ($volumeEntity != null) {
            $mapaProduto = $mapaProdutoRepo->findOneBy(array("mapaSeparacao"=>$mapaSeparacaoEntity,'produtoVolume'=>$volumeEntity));
        }
        if ($embalagemEntity != null) {
            $quantidadeEmbalagem = $embalagemEntity->getQuantidade();
            $mapaProduto = $mapaProdutoRepo->findOneBy(array("mapaSeparacao"=>$mapaSeparacaoEntity,'produtoEmbalagem'=>$embalagemEntity));
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
            $mapaProduto->setCodPedidoProduto($pedidoProduto->getId());
            $mapaProduto->setQtdCortado(0);
            $mapaProduto->setIndConferido('N');
            $mapaProduto->setCodDepositoEndereco($depositoEndereco);
        } else {
            $mapaProduto->setQtdSeparar($mapaProduto->getQtdSeparar() + $quantidadePedido);
        }

        $this->_em->persist($mapaProduto);
        $this->_em->flush($mapaProduto);
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

    public function getEtiquetasByFaixa($codBarrasInicial,$codBarrasFinal) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("es")
            ->from("wms:Expedicao\EtiquetaSeparacao","es")
            ->where("es.id >= $codBarrasInicial AND es.id <= $codBarrasFinal");
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
    public function cortar($etiquetaEntity, $corteTodosVolumes = false)
    {

        if ($this->cortaEtiquetaReentrega($etiquetaEntity)) {
            return true;
        }

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo   = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoque');

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
                if ($etiqueta->getCodStatus() != EtiquetaSeparacao::STATUS_CORTADO) {
                    if ($corteTodosVolumes == true) {
                        $this->alteraStatus($etiqueta,EtiquetaSeparacao::STATUS_CORTADO);
                    } else {
                        $this->alteraStatus($etiqueta,EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                    }
                }
            }
        }

        $EtiquetaRepo->incrementaQtdAtentidaOuCortada($etiquetaEntity->getId(), 'cortada');
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

        $reservaEstoque = $reservaEstoqueRepo->findReservaEstoque(NULL,$produtos,"S","E",$idExpedicao);
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
                    $produtoReserva->setQtd($produtoReserva->getQtd()+1);
                    $this->_em->persist($produtoReserva);
                }
            }
            $this->_em->flush();
            $reservaZerada = true;
            foreach ($produtosReserva as $produtoReserva) {
                if ($produtoReserva->getQtd() <0) $reservaZerada = false;
            }

            if ($reservaZerada == true) {
                $reservaEstoqueRepo->cancelaReservaEstoque(null,$produtos,"S","E",$idExpedicao);
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


        return true;
    }

    /**
     * @param $idCargaExterno
     * @param $idTipoCarga
     * @return array
     */
    public function getEtiquetasByCargaExterno($idCargaExterno, $idTipoCarga, $statusEtiqueta = null)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(' c.codCargaExterno as idCarga, tc.sigla as tipoCarga, tp.sigla as tipoPedido, es.codEntrega as codPedido, es.codBarras as codEtiqueta, es.codProduto, es.grade,
                   es.tipoComercializacao as dscVolume, es.dthConferencia, es.codStatus, s.sigla as status, es.reimpressao
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Util\Sigla', 'tc', 'WITH', 'es.codTipoCarga = tc.id')
            ->innerJoin('wms:Util\Sigla', 'tp', 'WITH', 'es.codTipoPedido = tp.id')
            ->innerJoin('wms:Util\Sigla', 's', 'WITH', 'es.codStatus = s.id')
            ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'es.codCarga = c.id')
            ->where('c.codCargaExterno = :idCarga')
            ->andWhere('es.codTipoCarga = :codTipoCarga')
            ->setParameter('idCarga', $idCargaExterno)
            ->setParameter('codTipoCarga', $idTipoCarga);

        if (is_array($statusEtiqueta)) {
            $status = implode(',',$statusEtiqueta);
            $dql->andWhere("es.codStatus in ($status)");
        }else if ($statusEtiqueta) {
            $dql->andWhere("es.codStatus = :statusEtiqueta")
                ->setParameter('statusEtiqueta', $statusEtiqueta);
        }

        return $dql->getQuery()->getResult();
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
             c.codCargaExterno as tipoCarga, prod.id as produto, prod.descricao, pe.descricao as embalagem')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->leftJoin('es.pedido', 'p')
            ->leftJoin('p.itinerario', 'i')
            ->leftJoin('es.produto', 'prod')
            ->leftJoin('p.carga', 'c')
            ->leftJoin('c.expedicao', 'e')
            ->leftJoin('es.status', 's')
            ->leftJoin('es.produtoEmbalagem', 'pe')
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
                ->andWhere('es.pedido = :codPedido');
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
            ->select('es.id, es.codProduto, p.id as pedido, es.codOS, p.centralEntrega, p.pontoTransbordo, es.reimpressao,
            es.codStatus, es.dscGrade, s.sigla, e.id as idExpedicao, e.dataInicio, c.codCargaExterno as tipoCarga,
            prod.id as produto, prod.descricao, pe.descricao as embalagem, i.descricao as itinerario, pess.nome as clienteNome,
            es.dataConferencia, es.dataConferenciaTransbordo, es.codOSTransbordo, cli.codClienteExterno, usuarioPessoa.login,
            siglaEpx.sigla as siglaEpxedicao')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin('es.pedido', 'p')
            ->innerJoin('p.itinerario', 'i')
            ->innerJoin('p.pessoa', 'cli')
            ->innerJoin('cli.pessoa', 'pess')
            ->leftJoin('wms:OrdemServico', 'os', 'WITH', 'es.codOS = os.id')
            ->leftJoin('wms:Usuario', 'usuarioPessoa', 'WITH', 'os.pessoa = usuarioPessoa.pessoa')
            ->leftJoin('es.produto', 'prod')
            ->leftJoin('p.carga', 'c')
            ->leftJoin('c.expedicao', 'e')
            ->leftJoin('es.status', 's')
            ->leftJoin('wms:Util\Sigla', 'siglaEpx', 'WITH', 'e.status = siglaEpx.id')
            ->leftJoin('es.produtoEmbalagem', 'pe')
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

    public function getEtiquetasReentrega($idExpedicao, $codStatus = null, $central = null) {
        $SQL = "
        SELECT ES.COD_ETIQUETA_SEPARACAO as ETIQUETA,
               PROD.COD_PRODUTO,
               PROD.DSC_PRODUTO PRODUTO,
               NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME,
               PES.NOM_PESSOA as CLIENTE,
               P.COD_PEDIDO as PEDIDO,
               C.COD_CARGA_EXTERNO AS CARGA,
               CA.COD_CARGA_EXTERNO AS CARGA_ANTIGA
         FROM REENTREGA R
         LEFT JOIN CARGA C ON C.COD_CARGA = R.COD_CARGA
        INNER JOIN ETIQUETA_SEPARACAO ES ON ES.COD_REENTREGA = R.COD_REENTREGA
         LEFT JOIN ETIQUETA_SEPARACAO_REENTREGA ESR ON ESR.COD_ETIQUETA_SEPARACAO = ES.COD_ETIQUETA_SEPARACAO
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

        if ($central != null) {
            $SQL = $SQL . " AND P.PONTO_TRANSBORDO = $central";
        }
        $SQL .= " GROUP BY ES.COD_ETIQUETA_SEPARACAO,
                   PROD.COD_PRODUTO,
                   PROD.DSC_PRODUTO,
                   PE.DSC_EMBALAGEM, PV.DSC_VOLUME,
                   PES.NOM_PESSOA,
                   P.COD_PEDIDO,
                   C.COD_CARGA_EXTERNO,
                   CA.COD_CARGA_EXTERNO";

        $SQL = $SQL . " ORDER BY ES.COD_ETIQUETA_SEPARACAO";
        $result =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

}