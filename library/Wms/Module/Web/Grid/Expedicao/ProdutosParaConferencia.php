<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class ProdutosParaConferencia extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init($idOS,$transbordo = false, $tipoConferencia)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $osRepo */
        $osRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
        $this->setAttrib('title','Produtos Conferencia');
        if($tipoConferencia == 'SEPARACAO'){
            $this->setAttrib('title','Produtos Separação');
            $result = $osRepo->getSeparacaoByOs($idOS);
            $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-separacao-grid')
                ->setAttrib('caption', 'Produtos Separados')
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'DSC_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Mapa Separação',
                    'index' => 'COD_MAPA_SEPARACAO',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Embalagem',
                    'index' => 'QTD_EMBALAGEM',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Separada',
                    'index' => 'QTD_SEPARADA',
                ))
                ->addColumn(array (
                    'label' => 'Data Separação',
                    'index' =>  'DTH_SEPARACAO'
                ))
                ->setShowExport(false);
        }elseif ($tipoConferencia != null) {
            $result = $osRepo->getConferenciaByOs($idOS, $transbordo, $tipoConferencia);

            $this->setSource(new \Core\Grid\Source\Doctrine($result))
                ->setId('expedicao-' . $tipoConferencia . '-grid')
                ->setAttrib('caption', 'Produtos conferidos - ' . $tipoConferencia)
                ->addColumn(array(
                    'label' => 'Cod. Barras',
                    'index' => 'id'
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'codProduto',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'produto',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
                ))
                ->addColumn(array(
                    'label' => 'Volume',
                    'index' => 'embalagem',
                ))
                ->addColumn(array (
                    'label' => 'Data ' . $tipoConferencia,
                    'index' =>  'dataConferencia',
                    'render' => 'DataTime'
                ))
                ->addColumn(array (
                    'label' => 'Data Conferencia Transbordo',
                    'index' =>  'dataConferenciaTransbordo',
                    'render' => 'DataTime'
                ))
                ->setShowExport(true);
            ;
        } else {
            $result = $osRepo->getConferenciaByOs($idOS, $transbordo, $tipoConferencia);

            $this->setSource(new \Core\Grid\Source\Doctrine($result))
                ->setId('expedicao-conferencia-grid')
                ->setAttrib('caption', 'Produtos conferidos')
                ->addColumn(array(
                    'label' => 'Cod. Barras',
                    'index' => 'id'
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'codProduto',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'produto',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
                ))
                ->addColumn(array(
                    'label' => 'Volume',
                    'index' => 'embalagem',
                ))
                ->addColumn(array (
                    'label' => 'Data Conferencia',
                    'index' =>  'dataConferencia',
                    'render' => 'DataTime'
                ))
                ->addColumn(array (
                    'label' => 'Data Conferencia Transbordo',
                    'index' =>  'dataConferenciaTransbordo',
                    'render' => 'DataTime'
                ))
                ->setShowExport(true);
            ;
        }

        return $this;
    }

}

