<?php

use Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Form\Relatorio\Recebimento\FiltroProdutosConferidos,
    Wms\Module\Web\Report\Recebimento\ProdutosConferidos;

/**
 * Description of Web_Relatorio_ProdutosConferidosController
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class Web_Relatorio_ProdutosConferidosController extends \Wms\Controller\Action
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

        $source = $this->em->createQueryBuilder()
                ->select('r, b.descricao as dscBox, b, s.sigla as status, s.id as idStatus')
                ->addSelect("
                    (
                        SELECT COUNT(nf.id) 
                        FROM wms:NotaFiscal nf 
                        WHERE nf.recebimento = r.id
                    )
                    AS qtdNotaFiscal
                    ")
                ->addSelect("
                    (
                        SELECT SUM(nfi.quantidade)
                        FROM wms:NotaFiscal nf2
                        JOIN nf2.itens nfi
                        WHERE nf2.recebimento = r.id
                    )
                    AS qtdProduto
                    ")
                ->addSelect("
                    (
                        SELECT os.id
                        FROM wms:OrdemServico os
                        WHERE os.recebimento = r.id AND os.dataFinal IS NULL
                    )
                    AS idOrdemServico
                    ")
                ->from('wms:Recebimento', 'r')
                ->leftJoin('r.box', 'b')
                ->innerJoin('r.status', 's')
                ->andWhere("r.status = ?5")
                ->setParameter(5, RecebimentoEntity::STATUS_FINALIZADO)
                ->orderBy('r.id');

        if ($values) {
            extract($values);

            if ((!empty($dataInicial1)) && (!empty($dataInicial2))) {
                $dataInicial1 = str_replace("/", "-", $dataInicial1);
                $dataI1 = new \DateTime($dataInicial1);

                $dataInicial2 = str_replace("/", "-", $dataInicial2);
                $dataI2 = new \DateTime($dataInicial2);

                $source->andWhere("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                        ->setParameter(1, $dataI1)
                        ->setParameter(2, $dataI2);
            }

            if ((!empty($dataFinal1)) && (!empty($dataFinal2))) {
                $DataFinal1 = str_replace("/", "-", $dataFinal1);
                $dataF1 = new \DateTime($DataFinal1);

                $DataFinal2 = str_replace("/", "-", $dataFinal2);
                $dataF2 = new \DateTime($DataFinal2);

                $source->andWhere("((TRUNC(r.dataFinal) >= ?3 AND TRUNC(r.dataFinal) <= ?4) OR r.dataFinal IS NULL)")
                        ->setParameter(3, $dataF1)
                        ->setParameter(4, $dataF2);
            }

            if ((!empty($idRecebimento))) {
                $source->andWhere("r.id = ?6")
                        ->setParameter(6, $idRecebimento);
            }
            
            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('produtos-conferidos-index-grid')
                    ->addColumn(array(
                        'label' => 'Código do Recebimento',
                        'index' => 'id',
                    ))
                    ->addColumn(array(
                        'label' => 'Data Inicial',
                        'index' => 'dataInicial',
                        'render' => 'DataTime',
                    ))
                    ->addColumn(array(
                        'label' => 'Data Final',
                        'index' => 'dataFinal',
                        'render' => 'DataTime',
                    ))
                    ->addColumn(array(
                        'label' => 'Status',
                        'index' => 'status',
                    ))
                    ->addColumn(array(
                        'label' => 'Box',
                        'index' => 'dscBox'
                    ))
                    ->addColumn(array(
                        'label' => 'Qtd. Nota Fiscal',
                        'index' => 'qtdNotaFiscal',
                    ))
                    ->addColumn(array(
                        'label' => 'Qtd. Produtos',
                        'index' => 'qtdProduto',
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar Relatório',
                        'title' => 'Relatório de Produtos Conferidos',
                        'actionName' => 'produtos-conferidos-pdf',
                        'pkIndex' => 'id',
                        'target' => '_blank',
                    ))
                    ->setShowExport(false)
                    ->setShowMassActions($values);

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        
        $this->addFlashMessage('info', 'Esta busca só considera os recebimentos com status FINALIZADO.');

        $this->view->form = $form;
    }

    /**
     * Relatorio de Produtos Conferidos
     */
    public function produtosConferidosPdfAction()
    {
        $idRecebimento = $this->getRequest()->getParam('id');

        $produtosConferidosReport = new ProdutosConferidos();

        $produtosConferidosReport->init(array(
            'idRecebimento' => $idRecebimento,
        ));
    }

}