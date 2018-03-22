<?php

use Wms\Module\Web\Form\Relatorio\Recebimento\FiltroProdutosConferidos;

class Web_Relatorio_ProdutosConferidosSenhaController extends \Wms\Controller\Action
{

    protected $repository = 'Recebimento';

    /**
     *
     * @return type
     */
    public function indexAction()
    {
        $form = new FiltroProdutosConferidos;

        $values = $form->getParams();

        if ($values) {
            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
            $result = $recebimentoRepo->getProdutosRecebidosComSenha($values);

            $grid = new \Core\Grid(new \Core\Grid\Source\ArraySource($result));
            $grid->setId('produtos-conferidos-index-grid')
                ->addColumn(array(
                    'label' => 'Recebimento',
                    'index' => 'COD_RECEBIMENTO',
                ))
                ->addColumn(array(
                    'label' => 'Cód.Fornecedor',
                    'index' => 'COD_EXTERNO',
                ))
                ->addColumn(array(
                    'label' => 'Fornecedor',
                    'index' => 'FORNECEDOR',
                ))
                ->addColumn(array(
                    'label' => 'N.F.',
                    'index' => 'NF',
                ))
                ->addColumn(array(
                    'label' => 'Série',
                    'index' => 'SERIE',
                ))
                ->addColumn(array(
                    'label' => 'Cód.Produto',
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'DSC_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'ShelfLife Min',
                    'index' => 'SHELFLIFE_MIN',
                ))
                ->addColumn(array(
                    'label' => 'ShelfLife Max',
                    'index' => 'SHELFLIFE_MAX',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Conf',
                    'index' => 'QTD_CONFERIDA',
                ))
                ->addColumn(array(
                    'label' => 'Validade',
                    'index' => 'DTH_VALIDADE',
                ))
                ->addColumn(array(
                    'label' => 'ShelfLife',
                    'index' => 'SHELFLIFE',
                ))
                ->addColumn(array(
                    'label' => 'Dth. Conferencia',
                    'index' => 'DTH_CONFERENCIA',
                ))
                ->addColumn(array(
                    'label' => 'Conferente',
                    'index' => 'CONFERENTE',
                ))
                ->addColumn(array(
                    'label' => 'Dth. Finalização',
                    'index' => 'DTH_FINALIZACAO',
                ))
                ->addColumn(array(
                    'label' => 'Usuário Finalização',
                    'index' => 'USUARIO_FINALIZACAO',
                ))
                ->addColumn(array(
                    'label' => 'Observação',
                    'index' => 'OBSERVACAO_RECEBIMENTO',
                ))
                ->setShowExport(true);

            $this->view->grid = $grid->build();
            $form->setSession($values)
                ->populate($values);
        }

        $this->view->form = $form;
    }

}