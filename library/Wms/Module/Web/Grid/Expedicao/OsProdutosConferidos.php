<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class OsProdutosConferidos extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($values = array())
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $this->setSource(new \Core\Grid\Source\ArraySource($values))
                ->setId('expedicao-produtos-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', "Produtos Conferidos")
                ->addColumn(array(
                    'label' => 'Etiqueta',
                    'index' => 'codBarras',
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
                ->addColumn(array(
                    'label' => 'Conferente',
                    'index' => 'conferente',
                ))
                ->addColumn(array(
                    'label' => 'Estoque',
                    'index' => 'codEstoque',
                ))
                ->addColumn(array(
                    'label' => 'Carga',
                    'index' => 'codCargaExterno',
                ))
                ->addColumn(array(
                    'label' => 'Volume Patrimonio',
                    'index' => 'volumePatrimonio',
                ))
                ->setShowExport(false)
                ;

        return $this;
    }

}

