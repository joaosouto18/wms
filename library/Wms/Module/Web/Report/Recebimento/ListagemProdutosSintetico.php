<?php

namespace Wms\Module\Web\Report\Recebimento;

use Wms\Module\Web\Report;

/**
 * Description of ListagemProdutosSintetico
 *
 * @author jéssica mayrink
 */
class ListagemProdutosSintetico extends Report
{

    public function init(array $params = array())
    {
        $em = $this->getEm();

        $produtos = $em->getRepository('wms:Produto')->relatorioListagemProdutos($params);

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');
        $pdf->setTitle(utf8_decode("LISTAGEM DE PRODUTOS SINTÉTICO"))
            ->setLabelHeight(6)
            ->setColHeight(5);

        if (empty($produtos)) {
            $dscVazio = 'Não existe produto.';
            $pdf->addLabel(0, 70, $dscVazio, 0, 1, 'L');
        } else {
            // header
            $pdf->addLabel(0, 15, utf8_decode('Código'), 0, 0, 'L');
            $pdf->addLabel(0, 95, 'Produto', 0, 0, 'L');
            $pdf->addLabel(0, 25, 'Grade', 0, 0, 'L');
            $pdf->addLabel(0, 30, 'Qtd.Volumes', 0, 0, 'L');
            $pdf->addLabel(0, 45, utf8_decode('Código de Barras'), 0, 0, 'L');
            $pdf->addLabel(0, 40, utf8_decode('Linha de Separação'), 0, 0, 'L');
            $pdf->addLabel(0, 30, 'End. Picking', 0, 1, 'L');

            foreach ($produtos as $produto) {
                $pdf->addCol(0, 15, $produto['COD_PRODUTO'], 0, 0, 'L');
                $pdf->addCol(0, 95, substr($produto['DSC_PRODUTO'],0,40), 0, 0, 'L');
                $pdf->addCol(0, 25, $produto['DSC_GRADE'], 0, 0, 'L');
                $pdf->addCol(0, 30, $produto['NUM_VOLUMES'], 0, 0, 'L');
                $pdf->addCol(0, 45, $produto['CODIGO_BARRAS'], 0, 0, 'L');
                $pdf->addCol(0, 40, $produto['DSC_LINHA_SEPARACAO'], 0, 0, 'L');
                $pdf->addCol(0, 30, $produto['PICKING'], 0, 1, 'L');

            }
        }
        // page
        $pdf->AddPage()
            ->render()
            ->Output('ListagemProdutosSintetico.pdf', 'D');
    }

}
