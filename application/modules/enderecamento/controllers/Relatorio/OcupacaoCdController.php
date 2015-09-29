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
                $sql = "SELECT COUNT(DISTINCT(E.COD_DEPOSITO_ENDERECO)) QTD_ENDERECO, PC.NOM_PRODUTO_CLASSE CLASSE_PRODUTO,
                        (CAST ((NVL(COUNT(DISTINCT(E.COD_DEPOSITO_ENDERECO)),0) * 100) / NVL(TOTAL_ENDERECO,0) AS NUMBER(9,2)) || '%')
                        TOTAL_ENDERECO
                        FROM ESTOQUE E
                        INNER JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                        INNER JOIN PRODUTO_CLASSE PC ON PC.COD_PRODUTO_CLASSE = P.COD_PRODUTO_CLASSE
                        INNER JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                        LEFT JOIN (
                          SELECT COUNT(DISTINCT(DE.COD_DEPOSITO_ENDERECO)) TOTAL_ENDERECO, DE.COD_DEPOSITO
                            FROM ESTOQUE E
                            INNER JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                            WHERE DE.NUM_RUA BETWEEN $values[ruaInicial] AND $values[ruaFinal]
                          GROUP BY DE.COD_DEPOSITO
                        ) DEP_END ON DEP_END.COD_DEPOSITO = DE.COD_DEPOSITO
                        WHERE DE.NUM_RUA BETWEEN $values[ruaInicial] AND $values[ruaFinal]
                        GROUP BY PC.NOM_PRODUTO_CLASSE, TOTAL_ENDERECO
                        ORDER BY CLASSE_PRODUTO";

                $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                $this->exportPDF($result, utf8_encode('Relatório de Ocupação por Produto'), utf8_encode('Relatório de Ocupação por Produto'), 'L');

            } else if ($values['tipoRelatorio'] == 'P') {
                $sql = "SELECT COUNT(DISTINCT(E.COD_DEPOSITO_ENDERECO)) QTD_ENDERECO, P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO,
                        (CAST ((NVL(COUNT(DISTINCT(E.COD_DEPOSITO_ENDERECO)),0) * 100) / NVL(TOTAL_ENDERECO,0) AS NUMBER(9,2)) || '%')
                        TOTAL_ENDERECO
                        FROM ESTOQUE E
                        INNER JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                        INNER JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                            LEFT JOIN (SELECT COUNT(DISTINCT(DE.COD_DEPOSITO_ENDERECO)) TOTAL_ENDERECO, DE.COD_DEPOSITO
                            FROM ESTOQUE E
                            INNER JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                            WHERE DE.NUM_RUA BETWEEN $values[ruaInicial] AND $values[ruaFinal]
                            GROUP BY DE.COD_DEPOSITO
                            ) DEP_END ON DEP_END.COD_DEPOSITO = DE.COD_DEPOSITO
                        WHERE DE.NUM_RUA BETWEEN $values[ruaInicial] AND $values[ruaFinal]
                        GROUP BY P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO, TOTAL_ENDERECO
                        ORDER BY P.COD_PRODUTO, P.DSC_PRODUTO";

                $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                $this->exportPDF($result, utf8_encode('Relatório de Ocupação por Produto'), utf8_encode('Relatório de Ocupação por Produto'), 'L');
            }
        }

        $this->view->form = $form;
    }

}