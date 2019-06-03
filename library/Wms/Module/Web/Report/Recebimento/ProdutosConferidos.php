<?php

namespace Wms\Module\Web\Report\Recebimento;

use Wms\Module\Web\Report;

/**
 * Description of ConferenciaCega
 *
 * @author medina
 */
class ProdutosConferidos extends Report
{

    public function init(array $params = array())
    {
        extract($params);
        $em = $this->getEm();

        $recebimentoEntity = $em->getRepository('wms:Recebimento')->find($idRecebimento);
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => 'CONTROLE_VALIDADE'));

        $parametroGrade = $parametroRepo->findOneBy(array('constante' => 'UTILIZA_GRADE'));
        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));
        $placaVeiculo = '';
        if ($notaFiscalEntity)
            $placaVeiculo = $notaFiscalEntity->getPlaca();

        //geracao de relatorio
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        //header('Content-type: application/pdf');

        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');

        // header
        $pdf->setTitle(utf8_decode('Relatório de Produtos Conferidos'))
            ->setLabelHeight(6)
            ->setColHeight(7);

        $nomeFilial = $recebimentoEntity->getFilial()->getPessoa()->getNome();

        if(strlen($nomeFilial) > 25){
            $nomeFilial = substr($nomeFilial,0,25)." ...";
        }

        // header
        $pdf->addLabel(0, 30, 'Recebimento', 0, 0, 'L');
        $pdf->addLabel(0, 65, 'Filial', 0, 0, 'L');
        $pdf->addLabel(0, 55, utf8_decode('Depósito'), 0, 0, 'L');
        $pdf->addLabel(0, 25, 'Box', 0, 0, 'L');
        $pdf->addLabel(0, 25, utf8_decode('Veículo'), 0, 0, 'L');
        $pdf->addLabel(0, 35, 'Data Inicial', 0, 0, 'L');
        $pdf->addLabel(0, 50, 'Data Final', 0, 1, 'L');
        $pdf->addLabel(0, 30, $recebimentoEntity->getId(), 0, 0, 'L');
        $pdf->addLabel(0, 65, utf8_decode($nomeFilial), 0, 0, 'L');
        $pdf->addLabel(0, 55, utf8_decode($recebimentoEntity->getDeposito()->getDescricao()), 0, 0, 'L');
        $pdf->addLabel(0, 25, $recebimentoEntity->getBox()->getDescricao(), 0, 0, 'L');
        $pdf->addLabel(0, 25, $placaVeiculo, 0, 0, 'L');
        $pdf->addLabel(0, 35, $recebimentoEntity->getDataInicial(), 0, 0, 'L');
        $pdf->addLabel(0, 50, $recebimentoEntity->getDataFinal(), 0, 1, 'L');

        $pdf->addLabel(0, 190, '', 0, 1, 'L');

        if ($parametroGrade->getValor() == 'S') {
            $pdf->addLabel(1, 15, 'Codigo', 'B', 0, 'L');
        } else
            $pdf->addLabel(1, 30, 'Codigo', 'B', 0, 'L');

        $pdf->addLabel(1, 2, '', '', 0, 'L');
        $pdf->addLabel(2, 83, 'Produto', 'B', 0, 'L');
        $pdf->addLabel(2, 2, '', '', 0, 'L');


        if ($parametro->getValor() == 'S') {
            if ($parametroGrade->getValor() == 'S') {
                $pdf->addLabel(3, 20, 'Grade', 'B', 0, 'L');
                $pdf->addLabel(3, 2, '', '', 0, 'L');
            }
        } else {
            if ($parametro->getValor() == 'S') {
                $pdf->addLabel(3, 50, 'Grade', 'B', 0, 'L');
                $pdf->addLabel(3, 2, '', '', 0, 'L');
            }
        }

        $pdf->addLabel(4, 25, 'Data Conf.', 'B', 0, 'L');
        $pdf->addLabel(4, 2, '', '', 0, 'L');
        $pdf->addLabel(5, 28, 'Qtd. Conferida', 'B', 0, 'L');
        $pdf->addLabel(5, 2, '', '', 0, 'L');

        if ($parametro->getValor() == 'S') {
            $pdf->addLabel(5, 28, 'Data Validade', 'B', 0, 'L');
            $pdf->addLabel(5, 2, '', '', 0, 'L');
        }

        $pdf->addLabel(6, 22, 'Qtd. Avaria', 'B', 0, 'L');
        $pdf->addLabel(6, 2, '', '', 0, 'L');
        $pdf->addLabel(7, 25, 'Qtd. Diverg.', 'B', 0, 'L');

        $codigoGradeTmp = '';
        $notaFiscalTmp = '';

        $itemsRecebimento = $em->getRepository('wms:NotaFiscal')->getConferenciaPorRecebimento($idRecebimento);

        foreach ($itemsRecebimento as $item) {

            $divergente = false;
            if ($item['DSC_MOTIVO_DIVER_RECEB'])
                $divergente = true;

            $dataConf = \DateTime::createFromFormat('Y-m-d H:i:s', $item['DTH_CONFERENCIA']);
            $dataEmissaoNF = \DateTime::createFromFormat('Y-m-d H:i:s', $item['DAT_EMISSAO']);

            $notaFiscal = utf8_decode('-----------------------------------   Nota Fiscal Nº: ' . $item['NUM_NOTA_FISCAL'] . ' - Série: ' . $item['COD_SERIE_NOTA_FISCAL'] . ' - Data Emissão: ' . $dataEmissaoNF->format('d/m/Y') . '    -----------------------------------');
            $codigoGrade = $item['COD_PRODUTO'] . $item['DSC_GRADE'];

            $border = 'T';

            if ($notaFiscalTmp != $notaFiscal) {
                $notaFiscalTmp = $notaFiscal;
                $pdf->addCol(1, 279, '', '', 1, 'C');
                $pdf->addCol(2, 279, $notaFiscal, '', 1, 'C');
                $codigoGradeTmp = '';
            }

            if ($codigoGradeTmp == $codigoGrade) {
                $border = '';
                $item['COD_PRODUTO'] = '';
                $item['DSC_PRODUTO'] = '';
                $item['DSC_GRADE'] = '';
            } else
                $codigoGradeTmp = $codigoGrade;

            //$pdf->addCol(1, 279, $notaFiscal, $borderNF, 1, 'L');
            if ($parametroGrade->getValor() == 'S') {
                $pdf->addCol(3, 17, $item['COD_PRODUTO'], $border, 0, 'L');
            } else {
                $pdf->addCol(3, 32, $item['COD_PRODUTO'], $border, 0, 'L');
            }
            $pdf->addCol(4, 85, utf8_decode(substr($item['DSC_PRODUTO'], 0, 40)), $border, 0, 'L');
            if ($parametro->getValor() == 'S') {
                if ($parametroGrade->getValor() == 'S') {
                    $pdf->addCol(5, 22, $item['DSC_GRADE'], $border, 0, 'L');
                }
            } else {
                if ($parametroGrade->getValor() == 'S') {
                    $pdf->addCol(5, 50, $item['DSC_GRADE'], $border, 0, 'L');
                }
            }
            $pdf->addCol(6, 27, $dataConf->format('d/m/y H:i'), $border, 0, 'L');
            $pdf->addCol(7, 28, $item['QTD_CONFERIDA'], $border, 0, 'C');

            if ($parametro->getValor() == 'S') {
                if (!is_null($item['DTH_VALIDADE'])) {
                    $dataValidade = new \DateTime($item['DTH_VALIDADE']);
                    $pdf->addCol(7, 28, $dataValidade->format('d/m/y'), $border, 0, 'C');
                } else {
                    $pdf->addCol(7, 28, '-', $border, 0, 'C');
                }
            }

            $pdf->addCol(8, 24, $item['QTD_AVARIA'], $border, 0, 'C');
            $pdf->addCol(9, 27, $item['QTD_DIVERGENCIA'], $border, 0, 'C');
            $pdf->addCol(10, 2, '', $border, 1, 'L');

            if ($divergente)
                $pdf->addCol(11, 72, 'OBS.: ' . $item['DSC_MOTIVO_DIVER_RECEB'], 0, 1, 'L');

        }

        // page
        $pdf->AddPage()
            ->render()
            ->Output();
    }

}
