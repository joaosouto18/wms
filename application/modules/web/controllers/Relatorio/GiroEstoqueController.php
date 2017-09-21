<?php

use Wms\Module\Web\Form\Relatorio\Produto\FiltroGiroProdutos as FiltroGiroProdutos;

class Web_Relatorio_GiroEstoqueController extends \Wms\Controller\Action
{

    public function indexAction()
    {
        $form = new FiltroGiroProdutos();

        $values = $form->getParams();

        $source = $this->em->createQueryBuilder()
            ->select('p.id codProduto, p.grade, p.descricao descricaoProduto, de.descricao descricaoEndereco, MAX(he.data) dataMovimentacao')
            ->from('wms:Produto', 'p')
            ->leftJoin('wms:Enderecamento\Estoque', 'e', 'WITH', 'e.codProduto = p.id AND e.grade = p.grade')
            ->innerJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.codProduto = p.id AND pe.grade = p.grade')
            ->innerJoin('pe.endereco', 'de')
            ->innerJoin('wms:Enderecamento\HistoricoEstoque', 'he', 'WITH', 'he.codProduto = p.id AND he.grade = p.grade')
            ->groupBy('p.id, p.grade, p.descricao, de.descricao')
            ->orderBy('de.descricao');


        if ($values) {
            extract($values);

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('produtos-conferidos-index-grid')
                    ->addColumn(array(
                        'label' => 'Código do Produto',
                        'index' => 'codProduto',
                    ))
                    ->addColumn(array(
                        'label' => 'Grade',
                        'index' => 'grade',
                    ))
                    ->addColumn(array(
                        'label' => 'Descrição Produto',
                        'index' => 'descricaoProduto',
                    ))
                    ->addColumn(array(
                        'label' => 'Endereço',
                        'index' => 'descricaoEndereco',
                    ))
                    ->addColumn(array(
                        'label' => 'Data Movimentação',
                        'index' => 'dataMovimentacao'
                    ))
                    ->setShowExport(false)
                    ->setShowMassActions($values);

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        

        $this->view->form = $form;
    }

}