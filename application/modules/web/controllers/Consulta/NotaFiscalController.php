<?php

use Wms\Domain\Entity\NotaFiscal,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Form\Subform\FiltroNotaFiscal,
    Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity;

/**
 * Description of Web_Consulta_NotaFiscalController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_Consulta_NotaFiscalController extends \Wms\Controller\Action
{

    protected $entityName = 'NotaFiscal';

    /**
     * 
     */
    public function indexAction()
    {
        $form = new FiltroNotaFiscal;

        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('nf, r.id codRecebimento, p.nomeFantasia fornecedor, s.sigla as status')
                    ->addSelect("
                        (
                            SELECT SUM(nfi.quantidade)
                            FROM wms:NotaFiscal nf2
                            INNER JOIN nf2.itens nfi
                            WHERE nf2.id = nf.id
                        )
                        AS qtdProduto, nf.placa
                    ")
                    ->from('wms:NotaFiscal', 'nf')
                    ->leftJoin('nf.recebimento', 'r')
                    ->innerJoin('nf.fornecedor', 'f')
                    ->innerJoin('f.pessoa', 'p')
                    ->leftJoin('nf.status', 's')
                    ->orderBy('nf.id', 'DESC');

            if ($idFornecedor)
                $source->andWhere("nf.fornecedor = '" . $idFornecedor . "'");
            else if ($fornecedor)
                $source->andWhere("p.nome LIKE '" . $fornecedor . "%'");

            if ($numero)
                $source->andWhere("nf.numero = '" . $numero . "'");

            if ($placa)
                $source->andWhere("nf.placa = '" . mb_strtoupper($placa, 'UTF-8') . "'");

            if ($serie)
                $source->andWhere("nf.serie = '" . $serie . "'");

            if ($dataEntradaInicial) {
                $dataEntradaInicial = new \DateTime(str_replace("/", "-", $dataEntradaInicial));

                $source->andWhere("TRUNC(nf.dataEntrada) >= ?1")
                        ->setParameter(1, $dataEntradaInicial);
            }

            if ($dataEntradaFinal) {
                $dataEntradaFinal = new \DateTime(str_replace("/", "-", $dataEntradaFinal));

                $source->andWhere("TRUNC(nf.dataEntrada) <= ?2")
                        ->setParameter(2, $dataEntradaFinal);
            }

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addColumn(array(
                        'label' => 'Placa',
                        'index' => 'placa',
                    ))
                    ->addColumn(array(
                        'label' => 'Cod. Recebimento',
                        'index' => 'codRecebimento'
                    ))
                    ->addColumn(array(
                        'label' => 'Nota Fiscal',
                        'index' => 'numero'
                    ))
                    ->addColumn(array(
                        'label' => 'Serie',
                        'index' => 'serie'
                    ))
                    ->addColumn(array(
                        'label' => 'Data Entrada',
                        'index' => 'dataEntrada',
                        'render' => 'DataTime',
                    ))
                    ->addColumn(array(
                        'label' => 'Fornecedor',
                        'index' => 'fornecedor',
                    ))
                    ->addColumn(array(
                        'label' => 'Status',
                        'index' => 'status',
                    ))
                    ->addColumn(array(
                        'label' => 'Qtd. Produtos',
                        'index' => 'qtdProduto',
                        'align' => 'R',
                        'hasTotal' => true,
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar NotaFiscal',
                        'title' => 'Detalhes da NotaFiscal',
                        'cssClass' => 'dialogAjax',
                        'actionName' => 'view-nota-ajax',
                        'pkIndex' => 'id'
                    ))
                    ->setHasOrdering(true);

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

    /**
     *
     * @throws \Exception 
     */
    public function viewAction()
    {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index'
                    ),
                    'tag' => 'a'
                )
            )
        ));

        $id = $this->getRequest()->getParam('id');

        if ($id == null) {
            throw new \Exception('ID do NotaFiscal inválido');
        }

        $NotaFiscal = $this->em->find('wms:Pessoa\Papel\NotaFiscal', $id);

        if ($NotaFiscal == null) {
            throw new \Exception('Este NotaFiscal não existe');
        }

        $this->view->pessoa = $NotaFiscal;
    }

    /**
     * Nota Fiscal
     */
    public function viewNotaAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');
        $em = $this->em;

        // busco notas fiscais
        $dql = $this->em->createQueryBuilder()
                ->select('nf.id, nf.numero, nf.serie, nf.dataEmissao, pj.nomeFantasia, s.sigla as status, s.id as idStatus')
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin('nf.fornecedor', 'f')
                ->innerJoin('f.pessoa', 'pj')
                ->leftJoin('nf.status', 's')
                ->where('nf.id = :id')
                ->setParameter('id', $id);

        $notaFiscal = $dql->getQuery()->execute();

        //busco produtos da nota
        $dql = $this->em->createQueryBuilder()
                ->select('p.id, nfi.grade, nfi.quantidade, p.descricao, p.possuiPesoVariavel,nfi.numPeso as peso')
                ->from('wms:NotaFiscal\Item', 'nfi')
                ->innerJoin('nfi.produto', 'p')
                ->andWhere('p.grade = nfi.grade')
                ->andWhere('nfi.notaFiscal = :idNotafiscal')
                ->setParameter('idNotafiscal', $id)
                ->orderBy('nfi.id');

        $itens = $dql->getQuery()->execute();

        $notaFiscal[0]['itens'] = $itens;

        $this->view->idStatusCancelado = NotaFiscalEntity::STATUS_CANCELADA;
        $this->view->notasFiscais = $notaFiscal;
    }

}
