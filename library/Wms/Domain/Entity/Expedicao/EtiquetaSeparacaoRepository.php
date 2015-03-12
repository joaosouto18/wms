<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

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
            ->Where('es.codExpedicao = :idExpedicao')
            ->setParameter('idExpedicao', $expedicaoEn->getId());

        if ($status != NULL) {
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
                      c.placaCarga, ped.pontoTransbordo, c.codCargaExterno, c.sequencia')
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin('es.pedido', 'ped')
            ->innerJoin('ped.carga', 'c')
            ->innerJoin('c.expedicao', 'exp')
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
            ->andWhere('es.codStatus = :Status')
            ->setParameter('idExpedicao', $idExpedicao)
            ->setParameter('Status', $status);
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

    public function getPendenciasByExpedicaoAndStatus($idExpedicao,$status, $tipoResult = "Array", $placaCarga = NULL, $transbordo = NULL, $embalado = NULL, $carga = NULL) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("es.codBarras,
                      es.cliente,
                      es.codProduto,
                      es.produto,
                      es.codCargaExterno,
                      es.grade,
                      es.codEstoque,
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
            ->orderBy('es.codCargaExterno, p.descricao, es.codProduto, es.grade');

        if ($tipoResult == "Array") {
            $result = $dql->getQuery()->getResult();
        } else {
            $result = $dql;
        }
        return $result;
    }

    public function getEtiquetasByExpedicao($idExpedicao, $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $pontoTransbordo = null)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select(' es.codEntrega, es.codBarras, es.codCarga, es.linhaEntrega, es.itinerario, es.cliente, es.codProduto, es.produto,
                    es.grade, es.fornecedor, es.tipoComercializacao, es.endereco, es.linhaSeparacao, es.codEstoque, es.codExpedicao,
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, c.sequencia
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
            ->where('es.codExpedicao = :idExpedicao')
            ->distinct(true)
            ->setParameter('idExpedicao', $idExpedicao);

        if ($status) {
            $dql->andWhere('es.codStatus = :Status')
                ->setParameter('Status', $status);
        }

        if ($pontoTransbordo) {
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
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, c.sequencia ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->leftJoin('wms:Expedicao\EtiquetaSeparacao','etq','WITH','etq.id = es.codBarras')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'p.id = es.codEntrega')
            ->innerJoin('wms:Expedicao\Carga', 'c' , 'WITH', 'c.id = es.codCarga')
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
                    CASE WHEN emb.descricao    IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                    CASE WHEN emb.CBInterno    IS NULL THEN vol.CBInterno ELSE emb.CBInterno END as CBInterno,
                    CASE WHEN emb.codigoBarras IS NULL THEN vol.codigoBarras ELSE emb2.codigoBarras END as codBarrasProduto
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Util\Sigla', 's', 'WITH', 'es.codStatus = s.id')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
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
                    es.placaExpedicao, es.codClienteExterno, es.tipoCarga, es.codCargaExterno, es.tipoPedido, es.codBarrasProduto
                ')
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
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

        \Zend\Stdlib\Configurator::configure($enEtiquetaSeparacao, $dadosEtiqueta);

        $this->_em->persist($enEtiquetaSeparacao);

        return $enEtiquetaSeparacao->getId();
    }

    /**
     * @param array $pedidosProdutos
     * @param int $status
     * @return int
     */
    public function gerarEtiquetas(array $pedidosProdutos, $status = EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $depositosPermitidos = null)
    {
        $statusEntity           = $this->_em->getReference('wms:Util\Sigla', $status);
        $prodSemdados = 0;

        foreach($pedidosProdutos as $pedidoProduto) {
            /** @var \Wms\Domain\Entity\Produto $produtoEntity */
            $pedidoEntity   = $pedidoProduto->getPedido();
            $produtoEntity  = $pedidoProduto->getProduto();
            $quantidade     = $pedidoProduto->getQuantidade();

            if ($produtoEntity->getVolumes()->count() > 0) {
                $arrayVolumes = $produtoEntity->getVolumes()->toArray();

                usort($arrayVolumes, function ($a,$b){
                    if ($a->getCodigoSequencial() > $b->getCodigoSequencial()) {
                        return -1;
                    }
                });

                for($i=0;$i<$quantidade;$i++) {
                    $codReferencia = null;
                    foreach ($arrayVolumes as $volumeEntity) {
                        $arrayEtiqueta['produtoVolume']     = $volumeEntity;
                        $arrayEtiqueta['produtoEmbalagem']  = null;
                        $arrayEtiqueta['produto']           = $produtoEntity;
                        $arrayEtiqueta['grade']             = $produtoEntity;
                        $arrayEtiqueta['pedido']            = $pedidoEntity;

                        if ($codReferencia != null) {
                            $arrayEtiqueta['codReferencia'] = $codReferencia;
                            $this->save($arrayEtiqueta,$statusEntity);
                        } else {
                            $codReferencia = $this->save($arrayEtiqueta,$statusEntity);
                        }

                        unset($arrayEtiqueta);
                    }
                }
            }
            else if ($produtoEntity->getEmbalagens()->count() > 0) {
                $arrayEmbalagens = $produtoEntity->getEmbalagens()->toArray();
                $padraoEmbalagem = null;

                $embalagem = null;
                foreach($arrayEmbalagens as $embalagemEn) {
                    if (isset($embalagem)) {
                        if ($embalagemEn->getQuantidade() < $embalagem->getQuantidade()) {
                            $embalagem = $embalagemEn;
                        }
                    } else {
                        $embalagem = $embalagemEn;
                    }
                }

                for($i=0;$i<$quantidade;$i++) {
                    $arrayEtiqueta['produtoVolume']     = null;
                    $arrayEtiqueta['produtoEmbalagem']  = $embalagem;
                    $arrayEtiqueta['produto']           = $produtoEntity;
                    $arrayEtiqueta['grade']             = $produtoEntity;
                    $arrayEtiqueta['pedido']            = $pedidoEntity;
                    $this->save($arrayEtiqueta,$statusEntity);
                    unset($arrayEtiqueta);
                }
            }
            else {
                $prodSemdados++;
            }

        }
        $this->_em->flush();
        $this->_em->clear();
        return $prodSemdados;
    }

    /**
     * @param $idExpedicao
     */
    public function finalizaEtiquetasSemConferencia($idExpedicao, $central)
    {

        $expedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao $expedicao */
        $expedicao = $expedicaoRepo->find($idExpedicao);

        if ($expedicao->getStatus() == $expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
            $novoStatus = EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
            $this->finalizaEtiquetaByStatus($idExpedicao, EtiquetaSeparacao::STATUS_CONFERIDO , $novoStatus, $central);
            $this->finalizaEtiquetaByStatus($idExpedicao, EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO , $novoStatus , $central);
        } else {
            $novoStatus = EtiquetaSeparacao::STATUS_CONFERIDO;
        }

        $this->finalizaEtiquetaByStatus($idExpedicao, EtiquetaSeparacao::STATUS_ETIQUETA_GERADA , $novoStatus, $central);
        $this->_em->flush();
    }

    private function finalizaEtiquetaByStatus ($idExpedicao, $statusBuscado, $novoStatus , $central) {
        $etiquetas = $this->getEtiquetasByExpedicao($idExpedicao, $statusBuscado, $central);
        $etiquetaRepository = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        foreach($etiquetas as $etiqueta) {
            $etiquetaEntity = $etiquetaRepository->find($etiqueta['codBarras']);
            $this->alteraStatus($etiquetaEntity, $novoStatus);
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

    /**
     * @param $etiquetaEntity
     */
    public function cortar($etiquetaEntity)
    {
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
                    $this->alteraStatus($etiqueta,EtiquetaSeparacao::STATUS_PENDENTE_CORTE);
                }
            }
        }

        $this->alteraStatus($etiquetaEntity,EtiquetaSeparacao::STATUS_CORTADO);
        $this->_em->flush();

        $codProduto = $etiquetaEntity->getCodProduto();
        $grade = $etiquetaEntity->getDscGrade();
        $idExpedicao = $etiquetaEntity->getPedido()->getCarga()->getExpedicao()->getId();
        $reservaEstoque = $reservaEstoqueRepo->findReservaEstoque(NULL,$codProduto,$grade,1,"S","E",$idExpedicao);
        if ($reservaEstoque != NULL) {
            if ($reservaEstoque->getQtd() == 1) {
                $reservaEstoqueRepo->cancelaReservaEstoque(null,$codProduto,$grade,0,"S","E",$idExpedicao);
            } else {
                $reservaEstoque->setQtd($reservaEstoque->getQtd()-1);
                $this->_em->persist($reservaEstoque);
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
            ->setMaxResults(5000)
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

        if ($parametros['reimpresso'] != "") {
            if ($parametros['reimpresso'] == 'S') {
                $source->andWhere("es.reimpressao is not null");
            } else {
                $source->andWhere("es.reimpressao is null");
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

        if (!empty($parametros['dataInicio']) && !empty($parametros['dataFim'])) {
            $dataInicial1 = str_replace("/", "-", $parametros['dataInicio']);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $parametros['dataFim']);
            $dataI2 = new \DateTime($dataInicial2);

            $source
                ->setParameter('dataInicio', $dataI1->format('Y-m-d'))
                ->setParameter('dataFim', $dataI2->format('Y-m-d'))
                ->andWhere('e.dataInicio BETWEEN :dataInicio AND :dataFim');
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

}