<?php

namespace Wms\Module\Web\Grid\Expedicao;


use Core\Grid\Pager;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

class ProdutosMapa extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idMapa, $idExpedicao)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaRepo */
        $mapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        /** @var Expedicao $expedicao */
        $expedicao = $this->getEntityManager()->find("wms:Expedicao", $idExpedicao);
        $array = $mapaRepo->getResumoConferenciaMapaProduto($idMapa);
        $this->setShowExport(false);
        $this->setShowPager(true);
        $pager = new Pager(count($array), 1, 200);
        $this->setpager($pager);

        $this->setSource(new \Core\Grid\Source\ArraySource($array))
            ->setId('expedicao-mapas-grid')
            ->setAttrib('class', 'grid-expedicao-pendencias')
            ->setAttrib('caption', 'Produtos')
            ->addColumn(array(
                'label' => 'Cod.Produto',
                'index' => 'COD_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'DSC_GRADE',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'DSC_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Qtd.Separar',
                'index' => 'QTD_SEPARAR',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Conferido',
                'index' => 'QTD_CONFERIDA',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Separado',
                'index' => 'QTD_SEPARADA',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Cortado',
                'index' => 'QTD_CORTADO',
            ))
            ->addColumn(array(
                'label' => 'Conferido',
                'index' => 'CONFERIDO',
            ))
            ->addAction(array(
                'label' => 'Visualizar Conferencia',
                'moduleName' => 'expedicao',
                'controllerName' => 'mapa',
                'actionName' => 'conferencia',
                'cssClass' => 'inside-modal',
                'pkIndex' => array('COD_PRODUTO','DSC_GRADE','NUM_CONFERENCIA')
            ));

        if ($expedicao->getCodStatus() == Expedicao::STATUS_EM_CONFERENCIA) {

            $this->addAction(array(
                'label' => 'Reiniciar Contagem',
                'moduleName' => 'expedicao',
                'controllerName' => 'mapa',
                'actionName' => 'desfazer-conferencia-ajax',
                'pkIndex' => array('COD_PRODUTO','DSC_GRADE')
            ));
        }


        return $this;
    }

}

