<?php

use Wms\Module\Web\Form\Relatorio\Produto\FiltroGiroProdutos as FiltroGiroProdutos;

class Web_Relatorio_GiroEstoqueController extends \Wms\Controller\Action
{

    public function indexAction()
    {
        $form = new FiltroGiroProdutos();

        $values = $form->getParams();

        if ($values) {

            extract($values);

            $andWhere = " ";
            if (isset($codProduto) && !empty($codProduto)) {
                $andWhere .= " AND P.COD_PRODUTO = $codProduto";
            }

            if (isset($linhaSeparacao) && !empty($linhaSeparacao)) {
                $andWhere .= " AND LS.COD_LINHA_SEPARACAO = $linhaSeparacao";
            }

            $having = " ";
            if (isset($dataInicio) && !empty($dataInicio)) {
                $having = " AND MAX(HE.DTH_MOVIMENTACAO) >= '$dataInicio' ";
            }

            if (isset($dataFinal) && !empty($dataFinal)) {
                $having = " AND MAX(HE.DTH_MOVIMENTACAO) <= '$dataFinal' ";
            }


            $source = "SELECT P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO, DE.DSC_DEPOSITO_ENDERECO, TO_CHAR(MAX(HE.DTH_MOVIMENTACAO), 'DD/MM/YYYY HH24:MI:SS') DATA_MOVIMENTACAO
                        FROM PRODUTO P 
                        LEFT JOIN ESTOQUE E ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                        LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                        LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                        LEFT JOIN DEPOSITO_ENDERECO DE ON PV.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO OR PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                        INNER JOIN HISTORICO_ESTOQUE HE ON P.COD_PRODUTO = HE.COD_PRODUTO AND P.DSC_GRADE = HE.DSC_GRADE
                        INNER JOIN LINHA_SEPARACAO LS ON P.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                        WHERE 1 = 1
                        $andWhere
                      GROUP BY P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO, DE.DSC_DEPOSITO_ENDERECO
                        HAVING 1 = 1
                        $having
                      ORDER BY DE.DSC_DEPOSITO_ENDERECO ";

            $result = $this->getEntityManager()->getConnection()->query($source)->fetchAll(\PDO::FETCH_ASSOC);

            $grid = new \Wms\Module\Web\Grid\RelatorioGiroEstoque();
            $grid->init($result);
            $grid->setShowExport(false)
                ->setShowMassActions($values);
            $this->view->grid = $grid->build();

            $form->setSession($values)
                    ->populate($values);
        }

        $this->view->form = $form;
    }

}