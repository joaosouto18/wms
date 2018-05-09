<?php

use Wms\Module\Web\Page;

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

        $idInventario = null;
        if (isset($params['inventario'])&& ($params['inventario'] != null)) {
            $idInventario = $params['inventario'];
        }

        if (isset($params['inventario']) && !empty($params['inventario']) || isset($params['divergencia'])
            || isset($params['tipoDivergencia']) || isset($params['linhaSeparacao'])) {
            $result = $estoqueErpRepo->getProdutosDivergentesByInventario($idInventario, $params);
            $grid = new \Wms\Module\Inventario\Grid\ComparativoEstoque();
            $this->view->grid = $grid->init($result,$params);

            if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
                $pdf = array();
                foreach ($result as $line) {
                    $pdf[] = array(
                        'Código' => $line['COD_PRODUTO'],
                        'Grade' => $line['DSC_GRADE'],
                        'Produto' => $line['DSC_PRODUTO'],
                        'Estoque WMS' => $line['ESTOQUE_WMS'],
                        'Estoque ERP' => $line['ESTOQUE_ERP'],
                        'Divergência' => $line['DIVERGENCIA'],
                        'Vlr. Estoque WMS' => $line['VLR_ESTOQUE_WMS'],
                        'Vlr. Estoque ERP' => $line['VLR_ESTOQUE_ERP'],
                        'Vlr. Divergencia' => $line['VLR_DIVERGENCIA'],);
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

    public function saldoAction(){
        ini_set('max_execution_time', 3000);
        ini_set('memory_limit', '-1');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        $idAcao = $this->getSystemParameterValue('COD_ACAO_INTEGRACAO_ESTOQUE');
        $acaoEn = $acaoIntRepo->find($idAcao);
        if ($acaoEn != null) {
            $acaoIntRepo->processaAcao($acaoEn);
        } else {
            $this->addFlashMessage('error','Integração com ERP não configurada');
        }

        $this->redirect('index');
    }

    public function configurePage()
    {
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
        Page::configure(array('buttons' => $buttons));
    }

}