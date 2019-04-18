<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\MovimentacaoProduto;

class Enderecamento_Relatorio_MovimentacaoProdutoController extends Action
{
    public function indexAction()
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");


        $this->view->utilizaGrade = $utilizaGrade;
        $form = new \Wms\Module\Armazenagem\Form\MovimentacaoProduto\Filtro();
        $form->init($utilizaGrade);
        $values = $form->getParams();

        if ($values)
        {
            if (isset($values['submitPDF']) && $values['submitPDF'] != null) {
                $relatorio = new MovimentacaoProduto();
                $relatorio->init($values);
            }

            if (isset($values['submit']) && $values['submit'] != null) {

                /** @var \Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository $HistEstoqueRepo */
                $HistEstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\HistoricoEstoque");
                $movimentacoes = $HistEstoqueRepo->getMovimentacaoProduto($values);

                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");

                foreach ($movimentacoes as $key => $movimentacao) {
                    if ($movimentacao['qtd'] > 0) {
                        $movimentacoes[$key]['tipo'] = "ENTRADA";
                    } else {
                        $movimentacoes[$key]['tipo'] = "SAIDA";
                    }

                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($movimentacao['codProduto'], $movimentacao['grade'], $movimentacao['qtd']);
                    if(is_array($vetEstoque)) {
                        $qtdEstoque = implode(' + ', $vetEstoque);
                    }else{
                        $qtdEstoque = $vetEstoque;
                    }
                    if ($qtdEstoque == "") {$qtdEstoque = "-";}
                    $movimentacoes[$key]['qtd'] = $qtdEstoque;

                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($movimentacao['codProduto'], $movimentacao['grade'], $movimentacao['saldoFinal']);
                    if(is_array($vetEstoque)) {
                        $qtdEstoque = implode(' + ', $vetEstoque);
                    }else{
                        $qtdEstoque = $vetEstoque;
                    }
                    if ($qtdEstoque == "") {$qtdEstoque = "-";}
                    $movimentacoes[$key]['saldoFinal'] = $qtdEstoque;

                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($movimentacao['codProduto'], $movimentacao['grade'], $movimentacao['saldoAnterior']);
                    if(is_array($vetEstoque)) {
                        $qtdEstoque = implode(' + ', $vetEstoque);
                    }else{
                        $qtdEstoque = $vetEstoque;
                    }
                    if ($qtdEstoque == "") {$qtdEstoque = "-";}
                    $movimentacoes[$key]['saldoAnterior'] = $qtdEstoque;


                }

                $this->view->movimentacoes = $movimentacoes;

            }
        }

        $this->view->form = $form;

    }

}