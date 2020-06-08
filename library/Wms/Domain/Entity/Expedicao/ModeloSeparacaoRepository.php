<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class ModeloSeparacaoRepository extends EntityRepository
{

    public function save(ModeloSeparacao $entity, $params) {

        $entity->setDescricao($params['descricao']);
        $entity->setUtilizaCaixaMaster($params['utilizaCaixaMaster']);
        $entity->setUtilizaQuebraColetor($params['utilizaQuebraColetor']);
        $entity->setUtilizaEtiquetaMae($params['utilizaEtiquetaMae']);
        $entity->setUsaSequenciaRotaPraca($params['usaSequenciaRotaPraca']);
        $entity->setUtilizaVolumePatrimonio($params['utilizaVolumePatrimonio']);
        $entity->setAgrupContEtiquetas($params['agrupContEtiquetas']);
        $entity->setTipoAgroupSeqEtiquetas($params['tipoAgroupSeqEtiquetas']);
        $entity->setUsaCaixaPadrao($params['usaCaixaPadrao']);
        $entity->setCriarVolsFinalCheckout($params['criarVolsFinalCheckout']);
        $entity->setImprimeEtiquetaVolume($params['imprimeEtiquetaPatrimonio']);
        $entity->setQuebraPulmaDoca($params['quebraPulmaDoca']);
        $entity->setQuebraUnidFracionavel($params['quebraUnidFracionavel']);
        $entity->setForcarEmbVenda($params['forcarEmbVenda']);
        $entity->setProdutoInventario($params['produtoInventario']);
        $entity->setTipoQuebraVolume($params['tipoQuebraVolume']);
        $entity->setSeparacaoPC($params['separacaoPc']);
        $entity->setTipoConfCarregamento($params['tipoConfCarregamento']);
        $entity->setTipoDefaultEmbalado($params['tipoDefaultEmbalado']);
        $entity->setTipoConferenciaEmbalado($params['tipoConferenciaEmbalado']);
        $entity->setTipoConferenciaNaoEmbalado($params['tipoConferenciaNaoEmbalado']);
        $entity->setTipoSeparacaoNaoFracionado($params['tipoSeparacaoNaoFracionado']);
        $entity->setTipoSeparacaoFracionado($params['tipoSeparacaoFracionado']);
        $entity->setTipoSeparacaoNaoFracionadoEmbalado($params['tipoSeparacaoNaoFracionadoEmbalado']);
        $entity->setTipoSeparacaoFracionadoEmbalado($params['tipoSeparacaoFracionadoEmbalado']);
        $this->_em->persist($entity);

        $entityModeloSeparacaoTipoQuebraFracionado = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacaoTipoQuebraFracionado")->findBy(array('modeloSeparacao' => $entity));

        /** @var Expedicao\ModeloSeparacaoTipoQuebraFracionado $tipoFracionado */
        foreach ($entityModeloSeparacaoTipoQuebraFracionado as $tipoFracionado) {
            $posFracionado = array_search($tipoFracionado->getTipoQuebra(), $params['quebraFracionados']);
            if ($posFracionado === false || $posFracionado === null ) {
                $this->_em->remove($tipoFracionado);
            } else {
                unset($params['quebraFracionados'][$posFracionado]);
            }
        }

        $entityModeloSeparacaoTipoQuebraNaoFracionado = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado")->findBy(array('modeloSeparacao' => $entity));

        /** @var Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado $tipoNaoFracionado */
        foreach ($entityModeloSeparacaoTipoQuebraNaoFracionado as $tipoNaoFracionado) {
            $posNaoFracionado = array_search($tipoNaoFracionado->getTipoQuebra(), $params['quebraNaoFracionados']);
            if ($posNaoFracionado === false || $posNaoFracionado === null ) {
                $this->_em->remove($tipoNaoFracionado);
            } else {
                unset($params['quebraNaoFracionados'][$posNaoFracionado]);
            }
        }

        $entityModeloSeparacaoTipoQuebraEmbalado = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacaoTipoQuebraEmbalado")->findBy(array('modeloSeparacao' => $entity));

        /** @var Expedicao\ModeloSeparacaoTipoQuebraEmbalado $tipoEmbalado */
        foreach ($entityModeloSeparacaoTipoQuebraEmbalado as $tipoEmbalado) {
            $posEmbalado = array_search($tipoEmbalado->getTipoQuebra(), $params['quebraEmbalados']);
            if ($posEmbalado === false || $posEmbalado === null ) {
                $this->_em->remove($tipoEmbalado);
            } else {
                unset($params['quebraEmbalados'][$posEmbalado]);
            }
        }

        foreach ($params['quebraFracionados'] as $quebraFracionado) {
            $quebraFracionadoEn = new Expedicao\ModeloSeparacaoTipoQuebraFracionado();
            $quebraFracionadoEn->setModeloSeparacao($entity);
            $quebraFracionadoEn->setTipoQuebra($quebraFracionado);
            $this->_em->persist($quebraFracionadoEn);
        }

        foreach ($params['quebraNaoFracionados'] as $quebraNaoFracionado) {
            $quebraNaoFracionadoEn = new Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado();
            $quebraNaoFracionadoEn->setModeloSeparacao($entity);
            $quebraNaoFracionadoEn->setTipoQuebra($quebraNaoFracionado);
            $this->_em->persist($quebraNaoFracionadoEn);
        }

        if (isset($params['quebraEmbalados'])) {
            foreach ($params['quebraEmbalados'] as $quebraEmbalado) {
                $quebraEmbaladoEn = new Expedicao\ModeloSeparacaoTipoQuebraEmbalado();
                $quebraEmbaladoEn->setModeloSeparacao($entity);
                $quebraEmbaladoEn->setTipoQuebra($quebraEmbalado);
                $this->_em->persist($quebraEmbaladoEn);
            }
        }

        $this->_em->flush();

        return $entity;
    }

    public function getModelos() {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->orderBy("m.id");

        return $source->getQuery()->getArrayResult();
    }

    public function getQuebras($modelos) {
        $resultado=array();
        $cont=0;
        foreach ($modelos as $chvModelo=>$vlrModelo){
             $source = $this->getEntityManager()->createQueryBuilder()
            ->select('tqFr.id,tqFr.tipoQuebra')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->innerJoin('m.tiposQuebraFracionado', 'tqFr')
            ->innerJoin('m.tiposQuebraNaoFracionado', 'tqNFr')
             ->where('m.id='.$vlrModelo['id'])
            ->orderBy("m.id");
            $resultado[$cont]['frac']=$source->getQuery()->getArrayResult();

            $source = $this->getEntityManager()->createQueryBuilder()
                ->select('tqNFr.id, tqNFr.tipoQuebra')
                ->from('wms:Expedicao\ModeloSeparacao', 'm')
                ->innerJoin('m.tiposQuebraFracionado', 'tqFr')
                ->innerJoin('m.tiposQuebraNaoFracionado', 'tqNFr')
                ->where('m.id='.$vlrModelo['id'])
                ->orderBy("m.id");

            $resultado[$cont]['nfrac']=$source->getQuery()->getArrayResult();
            $cont++;
        }

        return $resultado;
        // print_r($source->getQuery()->getArrayResult());die();
    }

    public function getQuebraFracionado($idModelo){
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('tqFr.tipoQuebra')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->innerJoin('m.tiposQuebraFracionado', 'tqFr')
            ->where('m.id='.$idModelo)
            ->orderBy("m.id");
        $resultado = $source->getQuery()->getArrayResult();
        return $resultado;
    }

    public function getQuebraNaoFracionado($idModelo){
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('tqFr.tipoQuebra')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->innerJoin('m.tiposQuebraNaoFracionado', 'tqFr')
            ->where('m.id='.$idModelo)
            ->orderBy("m.id");
        $resultado = $source->getQuery()->getArrayResult();
        return $resultado;
    }

    public function getQuebraEmbalado($idModelo){
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('tqEmb.tipoQuebra')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->innerJoin('m.tiposQuebraEmbalado', 'tqEmb')
            ->where('m.id='.$idModelo)
            ->orderBy("m.id");
        $resultado = $source->getQuery()->getArrayResult();
        return $resultado;
    }

    public function setModeloSeparacaoExpedicao($expedicaoEntity,$modeloSeparacaoEntity)
    {
        $expedicaoEntity->setModeloSeparacao($modeloSeparacaoEntity);
        $this->getEntityManager()->persist($expedicaoEntity);

        return true;
    }

    /**
     * @param $idExpedicao
     * @return ModeloSeparacao
     * @throws \Doctrine\ORM\ORMException
     */
    public function getModeloSeparacao($idExpedicao)
    {
        /** @var ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = null;
        $expedicaoEntity = $this->getEntityManager()->getReference('wms:Expedicao',$idExpedicao);
        if (!is_null($expedicaoEntity->getModeloSeparacao())) {
            $modeloSeparacaoEn = $expedicaoEntity->getModeloSeparacao();
        } else {
            $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
            $modeloSeparacaoEn = $this->find($idModeloSeparacao);
        }
        return $modeloSeparacaoEn;
    }

}

