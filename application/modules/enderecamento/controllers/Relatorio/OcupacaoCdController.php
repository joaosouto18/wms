<?php
use  Wms\Module\Armazenagem\Report\OcupacaoCD;

class Enderecamento_Relatorio_OcupacaoCdController extends \Wms\Controller\Action
{
    public function indexAction(){
        $form = new \Wms\Module\Armazenagem\Form\OcupacaocdPeriodo\Filtro();
        $form->init(false);
        $values = $form->getParams();

        if ($values)
        {
            $RelAcompanhamento = new OcupacaoCD();
            $RelAcompanhamento->imprimir($values);
        }
        $this->view->form = $form;
    }

    public function listAction()
    {
        $form = new \Wms\Module\Armazenagem\Form\OcupacaocdPeriodo\FiltroProduto();
        $form->init();

        $values = $form->getParams();

        if ($values)
        {
            if ($values['tipoRelatorio'] == 'C') {
                $sql = "SELECT COUNT(DISTINCT(E.COD_DEPOSITO_ENDERECO)), PC.NOM_PRODUTO_CLASSE
                        FROM ESTOQUE E
                        INNER JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                        INNER JOIN PRODUTO_CLASSE PC ON PC.COD_PRODUTO_CLASSE = P.COD_PRODUTO_CLASSE
                        GROUP BY PC.NOM_PRODUTO_CLASSE";

                $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                $this->exportPDF($result, 'Relatório de Ocupação por Produto.pdf', 'Relatório de Ocupação por Produto', 'L');

            } else if ($values['tipoRelatorio'] == 'P') {


            }
        }

        $this->view->form = $form;
    }

}