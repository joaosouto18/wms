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
    public function init($idOS,$transbordo = false)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $osRepo */
        $osRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
        $result = $osRepo->getConferenciaByOs($idOS,$transbordo);

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
                    'index' =>  'dataConferenciaTransbordo',
                    'render' => 'DataTime'
                ))
                ->setShowExport(false);
                ;

        return $this;
    }

}

