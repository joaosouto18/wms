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
                    'label' => 'Cód.Produto',
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'DESCRICAO_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Validade Informada',
                    'index' => 'DATA_VALIDADE_DIGITADA',
                ))
                ->addColumn(array(
                    'label' => 'ShelfLife',
                    'index' => 'DIAS_SHELF_LIFE',
                ))
                ->addColumn(array(
                    'label' => 'Diferença (Dias)',
                    'index' => 'DIAS_DIFERENCA',
                ))
                ->addColumn(array(
                    'label' => 'Percentual',
                    'index' => 'PORCENTAGEM',
                    'render' => 'N3'
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Conferida',
                    'index' => 'QTD_CONFERIDA',
                ))
                ->addColumn(array(
                    'label' => 'Usuario Conferencia',
                    'index' => 'USUARIO_CONFERENCIA',
                ))
                ->addColumn(array(
                    'label' => 'Usuario Liberação',
                    'index' => 'USUARIO_LIBERACAO',
                ))
                ->addColumn(array(
                    'label' => 'Observação',
                    'index' => 'OBSERVACAO',
                ))
                ->setShowExport(true);

            $this->view->grid = $grid->build();
            $form->setSession($values)
                ->populate($values);
        }

        $this->view->form = $form;
    }

}