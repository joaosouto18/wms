<?php

namespace Wms\Module\Web\Report\Recebimento;

use Wms\Module\Web\Report;

/**
 * Description of ConferenciaCega
 *
 * @author medina
 */
class ConferenciaCega extends Report {

    public function init(array $params = array()) {
        extract($params);
        $em = $this->getEm();

        $recebimentoEntity = $em->getRepository('wms:Recebimento')->find($idRecebimento);
        $itens = $em->getRepository('wms:NotaFiscal')->buscarItensConferenciaCega($idRecebimento);
        
        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));
        $placaVeiculo = '';
            if ($notaFiscalEntity)
                $placaVeiculo = $notaFiscalEntity->getPlaca();

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        header('Content-type: application/pdf');

        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');

        // header
        $pdf->setTitle(utf8_decode('Relatório de Conferência Cega'))
                ->setLabelHeight(6)
                ->setColHeight(7);

        $tmpItens = array();

        // header
        $pdf->addLabel(0, 20, 'Codigo', 0, 0, 'L');
        $pdf->addLabel(0, 65, 'Filial', 0, 0, 'L');
        $pdf->addLabel(0, 30, 'Data Inicial', 0, 0, 'L');
        $pdf->addLabel(0, 50, 'Status', 0, 0, 'L');
        $pdf->addLabel(0, 20, 'Box', 0, 0, 'L');
        $pdf->addLabel(0, 25, utf8_decode('Veículo'), 0, 0, 'L');
        $pdf->addLabel(0, 90, 'Conferente', 0, 1, 'L');
        $pdf->addLabel(0, 20, $recebimentoEntity->getId(), 0, 0, 'L');
        $pdf->addLabel(0, 65, $recebimentoEntity->getFilial()->getPessoa()->getNome(), 0, 0, 'L');
        $pdf->addLabel(0, 30, $recebimentoEntity->getDataInicial(), 0, 0, 'L');
        $pdf->addLabel(0, 50, $recebimentoEntity->getStatus()->getSigla(), 0, 0, 'L');
        $pdf->addLabel(0, 20, $recebimentoEntity->getBox()->getDescricao(), 0, 0, 'L');
        $pdf->addLabel(0, 25, $placaVeiculo, 0, 0, 'L');
        $pdf->addLabel(0, 70, '', 'B', 1, 'L');
        $pdf->addLabel(0, 280, '', 'B', 1, 'L');

        $pdf->addLabel(1, 147, 'Produto', 'B', 0, 'L');
        $pdf->addLabel(1, 2, '', 0, 0, 'L');
        $pdf->addLabel(2, 23, 'Quantidade', 'B', 0, 'C');
        $pdf->addLabel(1, 2, '', 0, 0, 'L');
        $pdf->addLabel(3, 100, utf8_decode('Observação'), 'B', 1, 'L');

        $produtoGradeAnterior = '';
        $dscUnitizadorAnterior = '';
        foreach ($itens as $item) {
            $dscUnitizador = '';
            $normaPaletizacao = '';
            $bordaUnitizador = 0;


            if ($item['DSC_UNITIZADOR']) {
                $dscUnitizador = $item['DSC_UNITIZADOR'];
                $normaPaletizacao = '- Norma: ' . $item['NUM_LASTRO'] . ' x ' . $item['NUM_CAMADAS'] . ' = ' . $item['NUM_NORMA'];
            }
            if ($item['COD_SEQUENCIA_VOLUME']) {
                $dscItem = 'Volume ' . $item['COD_SEQUENCIA_VOLUME'];
                $endereco = utf8_decode(' -   Endereço: ' . $item['ENDERECO_VOLUME']);
            } else {
                $dscItem = $item['DSC_EMBALAGEM'];
                $endereco = utf8_decode(' -   Endereço: ' . $item['ENDERECO_EMBALAGEM']);
            }

            $produto = $item['CODIGO'] . ' - ' . $item['GRADE'] . ' - ' . $item['DESCRICAO'];

            if( isset( $tmpItens[$produto][$dscItem] ) ) {
                continue;
            }

            $tmpItens[$produto][$dscItem] = $dscItem;

            if ($produto != $produtoGradeAnterior) {
                $pdf->addCol(1, 50, '', 0, 1, 'L');
                $pdf->addCol(1, 148, utf8_decode($produto), 1, 0, 'L');
                $pdf->addCol(7, 25, '', 1, 0, 'R');
                $pdf->addCol(8, 100, '', 1, 1, 'R');

                $observacoes = $notaFiscalRepo->getObservacoesNotasByProduto($idRecebimento,$item['CODIGO'],$item['GRADE']);
                $notas = $notaFiscalRepo->getNotaFiscalByProduto($idRecebimento,$item['CODIGO'],$item['GRADE']);
                if ($item['DSC_UNITIZADOR']) {
                    $pdf->addCol(4, 45, $dscUnitizador, $bordaUnitizador, 0, 'R');
                    $pdf->addCol(5, 105, $normaPaletizacao, $bordaUnitizador, 0, 'L');
                    $pdf->addCol(1,55,'NF: ' . $notas . $observacoes,0,1,'L');
                    $pdf->addCol(6, 45, $dscItem, 0, 0, 'R');
                    $pdf->addCol(7, 45, $endereco, 0, 0, 'L');
                } else {
                    $pdf->addCol(1, 55, utf8_decode('Não possui dados logísticos.'), 0, 0, 'R');
                    $pdf->addCol(1,95,'',0,0,'L');
                    $pdf->addCol(1,55,'NF: ' . $notas . $observacoes,0,0,'L');
                }

                $produtoGradeAnterior = $produto;
            } else if ($item['DSC_UNITIZADOR']) {
                if ($item['DSC_UNITIZADOR'] == $dscUnitizadorAnterior) {
                    $dscUnitizador = '';
                    $normaPaletizacao = '';
                    $pdf->addCol(4, 1, $dscUnitizador, 0, 0, 'L');
                    $pdf->addCol(5, 1, $normaPaletizacao, 0, 0, 'L');
                    $pdf->addCol(6, 15, $dscItem, 0, 0, 'R');
                    $pdf->addCol(7, 45, $endereco, 0, 0, 'L');
                } else {
                    $pdf->addCol(4, 50, '', 0, 1, 'L');
                    $pdf->addCol(4, 45, $dscUnitizador, 0, 0, 'R');
                    $pdf->addCol(5, 20, $normaPaletizacao, 0, 1, 'L');
                    $pdf->addCol(6, 45, $dscItem, 0, 1, 'R');
                    $pdf->addCol(7, 45, $endereco, 0, 0, 'L');
                }

                $produtoGradeAnterior = $produto;
            }

            $dscUnitizadorAnterior = $item['DSC_UNITIZADOR'];

        }
        // page
        $pdf->AddPage()
                ->render()
                ->Output();
    }

}
