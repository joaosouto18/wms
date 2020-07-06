<?php

use Wms\Module\Web\Page,
    Core\Util\Converter;;

class Inventario_ComparativoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        ini_set('max_execution_time', 3000);
        ini_set('memory_limit', '-1');
        $this->configurePage();
        $params = $this->_getAllParams();
        $form = new \Wms\Module\Inventario\Form\FormComparativo();
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueErpRepository $estoqueErpRepo */
        $estoqueErpRepo = $this->_em->getRepository("wms:Enderecamento\EstoqueErp");

        $form->populate($params);
        $this->view->form = $form;

        if (isset($params['inventario']) && !empty($params['inventario']) || isset($params['divergencia'])
            || isset($params['tipoDivergencia']) || isset($params['linhaSeparacao'])) {
            $result = $estoqueErpRepo->getProdutosDivergentesByInventario($params);
            $grid = new \Wms\Module\Inventario\Grid\ComparativoEstoque();
            $this->view->grid = $grid->init($result,$params);

            if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
                $pdf = array();
                foreach ($result as $line) {
                    $pdf[] = array(
                        'Código' => $line['COD_PRODUTO'],
                        'Grade' => $line['DSC_GRADE'],
                        'Produto' => $line['DSC_PRODUTO'],
                        'Estoque WMS' => Converter::enToBr($line['ESTOQUE_WMS'],3),
                        'Estoque ERP' => Converter::enToBr($line['ESTOQUE_ERP'],3),
                        'Divergência' => Converter::enToBr($line['DIVERGENCIA'],3),
                        'Vlr. Estoque WMS' => Converter::enToBr($line['VLR_ESTOQUE_WMS'],2),
                        'Vlr. Estoque ERP' => Converter::enToBr($line['VLR_ESTOQUE_ERP'],2),
                        'Vlr. Divergencia' => Converter::enToBr($line['VLR_DIVERGENCIA'],2),
                        'Cod. Fabricante' => $line['COD_FABRICANTE'],
                        'Fabricante' => $line['FABRICANTE']);
                }
                $this->exportCSV($pdf, 'comparativoEstoque');
            }
        }

        if (isset($result)) {
            $this->showTotais($result);
        }


    }

    private function showTotais($result){
        $qtdProdFalta = 0;
        $qtdTotalFalta = 0;
        $vlrTotalFalta = 0;
        $qtdProdSobra = 0;
        $qtdTotalSobra = 0;
        $vlrTotalSobra = 0;

        foreach ($result as $row) {
            if ($row['DIVERGENCIA'] >0) { //SOBRA
                $qtdProdSobra += 1;
                $qtdTotalSobra += $row['DIVERGENCIA'];
                $vlrTotalSobra += $row['VLR_DIVERGENCIA'];
            } else { //FALTA
                $qtdProdFalta += 1;
                $qtdTotalFalta += $row['DIVERGENCIA'];
                $vlrTotalFalta += $row['VLR_DIVERGENCIA'];
            }
        }

        $qtdProdAcumulado = $qtdProdSobra - $qtdProdFalta;
        $qtdTotalAcumulado = $qtdTotalSobra + $qtdTotalFalta;
        $vlrAcumulado = $vlrTotalSobra + $vlrTotalFalta;

        $qtdTotalFalta = $qtdTotalFalta * -1;
        $vlrTotalFalta = $vlrTotalFalta * -1;

        $qtdProdDivTotal = $qtdProdSobra + $qtdProdFalta;
        $qtdDivTotal = $qtdTotalSobra + $qtdTotalFalta;
        $vlrTotal = $vlrTotalSobra + $vlrTotalFalta;


        $qtdProdAcumulado = number_format($qtdProdAcumulado,0);
        $qtdTotalAcumulado = number_format($qtdTotalAcumulado,3);
        $vlrAcumulado = number_format($vlrAcumulado,2);
        $qtdProdFalta = number_format($qtdProdFalta,0);
        $qtdTotalFalta = number_format($qtdTotalFalta,3);
        $vlrTotalFalta = number_format($vlrTotalFalta,2);
        $qtdProdSobra = number_format($qtdProdSobra);
        $qtdTotalSobra = number_format($qtdTotalSobra,3);
        $vlrTotalSobra = number_format($vlrTotalSobra,2);
        $qtdProdDivTotal = number_format($qtdProdDivTotal,0);
        $qtdDivTotal = number_format($qtdDivTotal,3);
        $vlrTotal = number_format($vlrTotal,2);

        $totais =  '</br> </br>
            <fieldset>
                <legend>Resumo</legend>
                <table width="100%">
                    <tr>
                        <td>
                            <fieldset>
                            <legend>Sobras no WMS</legend>
                            <table width="100%">
                                <tr>
                                    <td>Qtd.Prod.</td>
                                    <td>Qtd.Total</td>
                                    <td>Vlr.Total</td>
                                </tr>
                                <tr>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="8" value="'. $qtdProdSobra . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $qtdTotalSobra . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $vlrTotalSobra . '" disabled=""/></td>
                                </tr>
                            </table>                
                            </fieldset>                        
                        </td>
                        <td>
                            <fieldset>
                            <legend>Faltas no WMS</legend>
                            <table width="100%">
                                <tr>
                                    <td>Qtd.Prod.</td>
                                    <td>Qtd.Total</td>
                                    <td>Vlr.Total</td>
                                </tr>
                                <tr>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="8" value="'. $qtdProdFalta . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $qtdTotalFalta . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $vlrTotalFalta . '" disabled=""/></td>
                                </tr>
                            </table>                
                            </fieldset>                        
                        </td>
                        <td>
                            <fieldset>
                            <legend>Divergencias Acumuladas</legend>
                            <table width="100%">
                                <tr>
                                    <td>Qtd.Prod.</td>
                                    <td>Qtd.Total</td>
                                    <td>Vlr.Total</td>
                                </tr>
                                <tr>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="8" value="'. $qtdProdAcumulado . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $qtdTotalAcumulado . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $vlrAcumulado . '" disabled=""/></td>
                                </tr>
                            </table>                
                            </fieldset>                        
                        </td>                        
                        <td>
                            <fieldset>
                            <legend>Total das Divergencias</legend>
                            <table width="100%">
                                <tr>
                                    <td>Qtd.Prod.</td>
                                    <td>Qtd.Total</td>
                                    <td>Vlr.Total</td>
                                </tr>
                                <tr>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="8" value="'. $qtdProdDivTotal . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $qtdDivTotal . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;padding-right:2px;" size="14" value="'. $vlrTotal . '" disabled=""/></td>
                                </tr>
                            </table>                
                            </fieldset>                        
                        </td>
                    </tr>
                </table>
            </fieldset>';

        echo $totais;

    }

    public function exportarAjaxAction(){
        try {
            /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
            $inventarioRepo = $this->_em->getRepository('wms:Inventario');

            $inventarioRepo->exportaInventarioModelo02(null);
            $this->addFlashMessage('success', "Saldo de estoque exportado com sucesso");

        } catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
        }

        $this->redirect('index');

    }

    public function saldoAction(){
        ini_set('max_execution_time', 3000);
        ini_set('memory_limit', '-1');
        try {
            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

            $idAcao = $this->getSystemParameterValue('COD_ACAO_INTEGRACAO_ESTOQUE');
            if (empty($idAcao)) throw new Exception('Integração com ERP não configurada');

            $acaoEn = $acaoIntRepo->find($idAcao);
            if (empty($acaoEn)) throw new Exception('Integração com ERP não encontrada');

            $acaoIntRepo->processaAcao($acaoEn);
        } catch (Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }

        $this->redirect('index');
    }

    public function configurePage()
    {
        if ($this->getSystemParameterValue("TIPO_INTEGRACAO_ESTOQUE_ERP") == "WebService") {
            $buttons[] = array(
                'label' => 'Consultar Saldo do ERP',
                'cssClass' => 'button atualizarEstoque',
                'urlParams' => array(
                    'module' => 'inventario',
                    'controller' => 'comparativo',
                    'action' => 'saldo',
                ),
                'tag' => 'a'
            );
        } else {
            $buttons[] = array(
                'label' => 'Consultar Saldo do ERP',
                'cssClass' => 'button dialogAjax',
                'urlParams' => array(
                    'module' => 'importacao',
                    'controller' => 'index',
                    'action' => 'index',
                ),
                'tag' => 'a'
            );
        }

        $buttons[] = array(
            'label' => 'Exportar Saldo',
            'cssClass' => 'button exportarSaldo',
            'urlParams' => array(
                'module' => 'inventario',
                'controller' => 'comparativo',
                'action' => 'exportar-ajax',
            ),
            'tag' => 'a'
        );

        Page::configure(array('buttons' => $buttons));
    }

}
