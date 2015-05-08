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
class Pendencias extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idExpedicao,$status = '522,523', $placaCarga = NULL, $transbordo = NULL, $caption = "Etiquetas pendentes de conferência",$embalado = Null, $carga = null)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao, $status,"DQL",$placaCarga, $transbordo,$embalado, $carga);

        $this->setSource(new \Core\Grid\Source\Doctrine($result))
                ->setId('expedicao-pendencias-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', $caption)
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
                    'label' => 'Cliente',
                    'index' => 'cliente',
                ))
                ->addColumn(array(
                    'label' => 'Estoque',
                    'index' => 'codEstoque',
                ))
                ->addColumn(array(
                    'label' => 'Transbordo',
                    'index' => 'pontoTransbordo',
                ))
                ->addColumn(array(
                    'label' => 'Carga',
                    'index' => 'codCargaExterno',
                ))
                ->setShowExport(false)
                ;

        return $this;
    }

}

