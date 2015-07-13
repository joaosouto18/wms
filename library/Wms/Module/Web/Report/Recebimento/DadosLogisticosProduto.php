<?php

namespace Wms\Module\Web\Report\Recebimento;

use Wms\Module\Web\Report;

/**
 * Description of DadosLogisticosProduto
 *
 * @author adriano uliana
 */
class DadosLogisticosProduto extends Report
{

    public function init(array $params = array())
    {
        $em = $this->getEm();

        $produtos = $em->getRepository('wms:NotaFiscal')->relatorioProdutoDadosLogisticos($params);
        
        switch ($params['indDadosLogisticos']) {
            case 'S':
                $tituloRelatorio = 'Relatório de Produtos Com Dados Logísticos';
                $dscVazio = 'Não existe produto com dados logísticos.';
                break;
            case 'N':
                $tituloRelatorio = 'Relatório de Produtos Sem Dados Logísticos';
                $dscVazio = 'Não existe produto sem dados logísticos.';
                break;
            default :
                $tituloRelatorio = 'Relatório de Produtos';
                $dscVazio = 'Não existe produto.';
                break;
        }

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');

       $pdf->setTitle(utf8_decode($tituloRelatorio))
               ->setLabelHeight(6)
               ->setColHeight(5)
               ->setNumRows(count($produtos));

        if (empty($produtos)) {
            $dscVazio = 'Não existe produto.';
            $pdf->addLabel(0, 70, $dscVazio, 0, 1, 'L');
        } else {
            // header
            $pdf->addLabel(0, 6, '', 0, 0, 'L');
            $pdf->addLabel(0, 20, utf8_decode('Código'), 0, 0, 'L');
            $pdf->addLabel(0, 30, 'Grade', 0, 0, 'L');
            $pdf->addLabel(0, 100, utf8_decode('Descrição'), 0, 0, 'L');
            $pdf->addLabel(0, 70, utf8_decode('Cód. Barras'), 0, 0, 'L');
            $pdf->addLabel(0, 30, 'Altura', 0, 0, 'L');
            $pdf->addLabel(0, 30, 'Largura', 0, 1, 'L');


            foreach ($produtos as $produto) {
                $pdf->addCol(0, 5, '', 1, 0, 'L');
                $pdf->addCol(0, 1, '', 0, 0, 'L');
                $pdf->addCol(0, 20, $produto['COD_PRODUTO'],  0, 0, 'TB');
                $pdf->addCol(0, 30, $produto['DSC_GRADE'], 0, 0, 'L');
                $pdf->addCol(0, 100, substr($produto['DSC_PRODUTO'], 0, 40) , 0, 0, 'L');
                $pdf->addCol(0, 70, $produto['COD_BARRAS'], 0, 0, 'L');
                $pdf->addCol(0, 30,  $produto['ALTURA'], 0, 0, 'L');
                $pdf->addCol(0, 30, $produto['LARGURA'], 0, 1, 'L');
                $pdf->addCol(0, 40, 'Profundidade: ' . $produto['PROFUNDIDADE'], 0, 0, 'L');
                $pdf->addCol(0, 30, 'Peso: ' . $produto['PESO'], 0, 0, 'L');
                $pdf->addCol(0, 30, 'Lastro: ' . $produto['LASTRO'], 0, 0, 'L');
                $pdf->addCol(0, 30, 'Camada: ' .$produto['CAMADA'], 0, 0, 'L');
                $pdf->addCol(0, 70, 'Palete: ' .$produto['UNITIZADOR'], 0, 0, 'L');
                $pdf->addCol(0, 50, utf8_decode('Descrição:') . $produto['DESCRICAO'], 0, 1, 'L');;
                $pdf->addCol(0, 70, '-------------------------------------------------------------------', 0, 0, 'L');
                $pdf->addCol(70, 70, '-------------------------------------------------------------------', 0, 0, 'L');
                $pdf->addCol(140, 70, '-------------------------------------------------------------------', 0, 0, 'L');
                $pdf->addCol(210, 70, '---------------------------------------------------------', 0, 1, 'L');

            }
        }

        // page
        $pdf->AddPage()
                ->render()
                ->Output('', 'I');
    }

}
