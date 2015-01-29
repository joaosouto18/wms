<?php

use Wms\Domain\Entity\Pessoa\Papel\Fornecedor,
    Wms\Module\Web\Page,
    Wms\Module\Web\Form\Subform\Pessoa\Papel\FiltroPJ;

/**
 * Description of Web_FornecedorController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_FornecedorController extends \Wms\Controller\Action
{

    protected $entityName = 'Pessoa\Papel\Fornecedor';

    public function indexAction()
    {

        $form = new FiltroPJ;

        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('f, p.nome, p.nomeFantasia, p.cnpj')
                    ->from('wms:Pessoa\Papel\Fornecedor', 'f')
                    ->innerJoin('f.pessoa', 'p')
                    ->orderBy('p.nome');

            if (!empty($nome)) {
                $nomeFornecedor = mb_strtoupper($nome, 'UTF-8');
                $source->andWhere("p.nome LIKE '{$nomeFornecedor}%'");
            }
            if (!empty($nomeFantasia)) {
                $nomeFantasiaFornecedor = mb_strtoupper($nomeFantasia, 'UTF-8');
                $source->andWhere("p.nomeFantasia LIKE '{$nomeFantasiaFornecedor}%'");
            }
            if (!empty($cnpj)) {
                $cnpjNum = str_replace(array(".", "-", "/"), "", $cnpj);
                $source->andWhere("p.cnpj = '{$cnpjNum}'");
            }


            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addColumn(array(
                        'label' => 'Razao Social',
                        'index' => 'nome'
                    ))
                    ->addColumn(array(
                        'label' => 'Nome',
                        'index' => 'nomeFantasia'
                    ))
                    ->addColumn(array(
                        'label' => 'CNPJ',
                        'index' => 'cnpj',
                        'render' => 'documento',
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar Fornecedor',
                        'actionName' => 'view',
                        'pkIndex' => 'id'
                    ));

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

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
            throw new \Exception('ID do fornecedor inválido');
        }

        $fornecedor = $this->em->find('wms:Pessoa\Papel\Fornecedor', $id);

        if ($fornecedor == null) {
            throw new \Exception('Este fornecedor não existe');
        }

        $this->view->pessoa = $fornecedor;
    }

    /**
     *
     */
    public function getFornecedorJsonAction()
    {
        $term = $this->getRequest()->getParam('term');
        $term = mb_strtoupper($term, 'UTF-8');

        $em = $this->getEntityManager();

        // busco fornecedores
        $dql = $this->em->createQueryBuilder()
                ->select('f.id, p.nome')
                ->from('wms:Pessoa\Papel\Fornecedor', 'f')
                ->innerJoin('f.pessoa', 'p')
                ->where("p.nome LIKE '{$term}%'");

        $fornecedores = $dql->getQuery()->execute();

        $array = array();
        foreach ($fornecedores as $fornecedor) {
            $array[] = array(
                'id' => $fornecedor['id'],
                'value' => $fornecedor['nome'],
            );
        }

        $this->_helper->json($array, true);
    }

}
