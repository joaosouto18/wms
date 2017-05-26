<?php

namespace Wms\Module\Web\Grid\Expedicao;

use Core\Grid\Pager;
use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class ProdutosMapa extends Grid {

    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idMapa, $idExpedicao) {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaRepo */
        $mapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $array = $mapaRepo->getResumoConferenciaMapaProduto($idMapa);
        $vetDuplicado = array();
        $qtdCortadaReal = 0;
        foreach ($array as $key => $value) {
            /**
             * Atribui valores para fazer soma
             */
            $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM'][] = $value['QTD_EMBALAGEM'];
            $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['DSC_EMBALAGEM'][] = $value['DSC_EMBALAGEM'];
            $qtdConferidaReal = $value['QTD_CONFERIDA'];
            $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_CORTADO_REAL'][] = $value['QTD_CORTADO'];
            $qtdCortadaReal = $value['QTD_CORTADO'] + $qtdCortadaReal;
            if (isset($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_SEPARAR'])) {
                $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_SEPARAR'] = $value['QTD_SEPARAR'] . ' + ' . $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_SEPARAR'];
                if ($value['QTD_CONFERIDA'] > 0) {
                    $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_CONFERIDA'] = $value['QTD_CONFERIDA'];
                    if (count($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM']) > 1) {
                        arsort($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM']);
                        $qtdConferidaEmbalagem = '';
                        foreach ($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM'] as $keyEmbalagem => $qtdEmbalagem) {
                            /**
                             * Enquanto a quantidade total conferida menos a quantidade dessa embalagem 
                             * for maior que zero adiciona uma quantidade na embalagem
                             */
                            $qtd = 0;
                            while (($qtdConferidaReal - $qtdEmbalagem) >= 0) {
                                if ($qtdConferidaReal > 0) {
                                    $qtd++;
                                }
                                $qtdConferidaReal = $qtdConferidaReal - $qtdEmbalagem;
                            }
                            /**
                             * Insere uma posicao com a quantidade de embalagens 
                             */
                            if ($qtd > 0) {
                                $qtdConferidaEmbalagem[] = $qtd . $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['DSC_EMBALAGEM'][$keyEmbalagem] . '(' . $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM'][$keyEmbalagem] . ')';
                            }
                        }
                        /**
                         * Ordena o vetor em ordem crescente e concatena
                         */
                        asort($qtdConferidaEmbalagem);
                        $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_CONFERIDA'] = implode(' + ', $qtdConferidaEmbalagem);
                    }
                }
                /**
                 * Apaga linha duplicada
                 */
                unset($array[$key]);
            } else {
                $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_SEPARAR'] = $value['QTD_SEPARAR'];
            }
        }
        foreach ($array as $key => $value) {
            $array[$key]['QTD_SEPARAR'] = $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_SEPARAR'];
            if (isset($array[$key]['QTD_CONFERIDA']) && $value['QTD_CONFERIDA'] > 0) {
                $array[$key]['QTD_CONFERIDA'] = $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_CONFERIDA'];
            }
            if (isset($array[$key]['QTD_CORTADO']) && $value['QTD_CORTADO'] > 0) {
                $qtdCortadaReal = array_sum($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_CORTADO_REAL']);
                $qtd = 0;
                arsort($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM']);
                foreach ($vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM'] as $keyEmbalagem => $qtdEmbalagem) {
                    /**
                     * Enquanto a quantidade total conferida menos a quantidade dessa embalagem 
                     * for maior que zero adiciona uma quantidade na embalagem
                     */
                    while (($qtdCortadaReal - $qtdEmbalagem) > 0) {
                        if ($qtdCortadaReal > 0) {
                            $qtd++;
                        }
                        $qtdCortadaReal = $qtdCortadaReal - $qtdEmbalagem;
                    }
                    /**
                     * Insere uma posicao com a quantidade de embalagens 
                     */
                    $qtdCortadaEmbalagem[] = $qtd . $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['DSC_EMBALAGEM'][$keyEmbalagem] . '(' . $vetDuplicado[$value['COD_PRODUTO'] . $value['DSC_GRADE']]['QTD_EMBALAGEM'][$keyEmbalagem] . ')';
                }
                /**
                 * Ordena o vetor em ordem crescente e concatena
                 */
                asort($qtdCortadaEmbalagem);
                $cortado = implode(' + ', $qtdCortadaEmbalagem);
                $array[$key]['QTD_CORTADO'] = $cortado;
            }
        }
        $this->setShowExport(false);
        $this->setShowPager(true);
        $pager = new Pager(count($array), 1, 100);
        $this->setpager($pager);
        $this->setShowPager(false);

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
                    'pkIndex' => array('COD_PRODUTO', 'DSC_GRADE', 'NUM_CONFERENCIA')
                ))
        /*            ->addAction(array(
          'label' => 'Cortar Item',
          'moduleName' => 'expedicao',
          'controllerName' => 'corte-pedido',
          'actionName' => 'list',
          'cssClass' => 'inside-modal',
          'params'=>array('pedidoCompleto'=>'N','COD_EXPEDICAO'=>$idExpedicao),
          'pkIndex' => array('idProduto'=>'COD_PRODUTO','DSC_GRADE')
          ))
          ->addAction(array(
          'label' => 'Cortar Pedido',
          'moduleName' => 'expedicao',
          'controllerName' => 'corte-pedido',
          'actionName' => 'list',
          'cssClass' => 'inside-modal',
          'params'=>array('pedidoCompleto'=>'S','COD_EXPEDICAO'=>$idExpedicao),
          'pkIndex' => array('idProduto'=>'COD_PRODUTO','DSC_GRADE')
          )) */

        ;
        return $this;
    }

}
