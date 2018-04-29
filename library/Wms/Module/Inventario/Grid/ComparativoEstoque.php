<?php

namespace Wms\Module\Inventario\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class ComparativoEstoque extends Grid {

    public function init($result) {

        $this->setAttrib('title', 'comparativo-estoque');
        $this->setSource(new \Core\Grid\Source\ArraySource($result));
        $this->addColumn(array(
            'label' => 'Cod. Produto',
            'index' => 'COD_PRODUTO',
        ));
        $this->addColumn(array(
            'label' => 'Grade',
            'index' => 'DSC_GRADE',
        ));
        $this->addColumn(array(
            'label' => 'Produto',
            'index' => 'DSC_PRODUTO',
        ));
        $this->addColumn(array(
            'label' => 'Estoque ERP',
            'index' => 'ESTOQUE_ERP',
            'render' => 'N3'
        ));
        $this->addColumn(array(
            'label' => 'Estoque WMS',
            'index' => 'ESTOQUE_WMS',
            'render' => 'N3'
        ));
        $this->addColumn(array(
            'label' => 'Divergência',
            'index' => 'DIVERGENCIA',
                    'render' => 'N3'
        ));
        $this->addColumn(array(
            'label' => 'Vlr.WMS',
            'index' => 'VLR_ESTOQUE_WMS',
            'render' => 'N2'
        ));
        $this->addColumn(array(
            'label' => 'Vlr.ERP',
            'index' => 'VLR_ESTOQUE_ERP',
            'render' => 'N2'
        ));
        $this->addColumn(array(
            'label' => 'Vlr.Div.',
            'index' => 'VLR_DIVERGENCIA',
            'render' => 'N2'
        ));

        $this->setShowExport(false)
                ->addMassAction('mass-select', 'Selecionar');
        $pg = new Pager(count($result), 0, count($result));
        $this->setPager($pg);

        $qtdProdFalta = 0;
        $qtdTotalFalta = 0;
        $vlrTotalFalta = 0;
        $qtdProdSobra = 0;
        $qtdTotalSobra = 0;
        $vlrTotalSobra = 0;
        $qtdProdDivTotal = 0;
        $qtdDivTotal = 0;
        $vlrTotal = 0;

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

        $qtdTotalFalta = $qtdTotalFalta * -1;
        $vlrTotalFalta = $vlrTotalFalta * -1;

        $qtdProdDivTotal = $qtdProdSobra + $qtdProdSobra;
        $qtdDivTotal = $qtdTotalSobra + $qtdTotalFalta;
        $vlrTotal = $vlrTotalSobra + $vlrTotalFalta;

        $qtdProdFalta = number_format($qtdProdFalta,0);
        $qtdTotalFalta = number_format($qtdTotalFalta,3);
        $vlrTotalFalta = number_format($vlrTotalFalta,2);
        $qtdProdSobra = number_format($qtdProdSobra);
        $qtdTotalSobra = number_format($qtdTotalSobra,3);
        $vlrTotalSobra = number_format($vlrTotalSobra,2);
        $qtdProdDivTotal = number_format($qtdProdDivTotal,0);
        $qtdDivTotal = number_format($qtdDivTotal,3);
        $vlrTotal = number_format($vlrTotal,2);

        echo '</br> </br>
            <fieldset>
                <legend>Resumo</legend>
                <table width="100%">
                    <tr>
                        <td>
                            <fieldset>
                            <legend>Sobras</legend>
                            <table width="100%">
                                <tr>
                                    <td>Qtd.Prod.</td>
                                    <td>Qtd.Total</td>
                                    <td>Vlr.Total</td>
                                </tr>
                                <tr>
                                    <td><input type="text" style="text-align:right;" size="10" value="'. $qtdProdSobra . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;" size="15" value="'. $qtdTotalSobra . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;" size="15" value="'. $vlrTotalSobra . '" disabled=""/></td>
                                </tr>
                            </table>                
                            </fieldset>                        
                        </td>
                        <td>
                            <fieldset>
                            <legend>Faltas</legend>
                            <table width="100%">
                                <tr>
                                    <td>Qtd.Prod.</td>
                                    <td>Qtd.Total</td>
                                    <td>Vlr.Total</td>
                                </tr>
                                <tr>
                                    <td><input type="text" style="text-align:right;" size="10" value="'. $qtdProdFalta . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;" size="15" value="'. $qtdTotalFalta . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;" size="15" value="'. $vlrTotalFalta . '" disabled=""/></td>
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
                                    <td><input type="text" style="text-align:right;" size="10" value="'. $qtdProdDivTotal . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;" size="15" value="'. $qtdDivTotal . '" disabled=""/></td>
                                    <td><input type="text" style="text-align:right;" size="15" value="'. $vlrTotal . '" disabled=""/></td>
                                </tr>
                            </table>                
                            </fieldset>                        
                        </td>
                    </tr>
                </table>
            </fieldset>';

        return $this;
    }

}
