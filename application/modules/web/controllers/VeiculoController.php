<?php

use \Wms\Module\Web\Controller\Action,
    \Wms\Module\Web\Page,
    \Wms\Module\Web\Controller\Action\Crud,
    \Core\Grid;

/**
 * Description of Web_VeiculoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_VeiculoController extends Crud
{

    protected $entityName = 'Movimentacao\Veiculo';

    public function indexAction()
    {
        $form = new Wms\Module\Web\Form\Movimentacao\Veiculo\Filtro;
        $form->setAttrib('class', 'filtro');

        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('v, tv.descricao, p.nomeFantasia')
                    ->from('wms:Movimentacao\Veiculo', 'v')
                    ->innerJoin('v.tipo', 'tv')
                    ->innerJoin('v.transportador', 't')
                    ->innerJoin('t.pessoa', 'p')
                    ->orderBy('tv.descricao, p.nomeFantasia, v.id');

            if (!empty($id)) {
                $id = mb_strtoupper($id, 'UTF-8');
                $source->andWhere("v.id = '{$id}'");
            }
            if (!empty($tipo)) {
                $source->andWhere("tv.id = {$tipo}");
            }
            if (!empty($transportador)) {
                $nomeTransportador = mb_strtoupper($transportador, 'UTF-8');
                $source->andWhere("p.nomeFantasia LIKE '{$nomeTransportador}%'");
            }

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('grid-veiculo');
            $grid->addColumn(array(
                        'label' => 'Tipo do Veículo',
                        'index' => 'descricao'
                    ))
                    ->addColumn(array(
                        'label' => 'Transportador',
                        'index' => 'nomeFantasia'
                    ))
                    ->addColumn(array(
                        'label' => 'Placa',
                        'index' => 'id'
                    ))
                    ->addAction(array(
                        'label' => 'Editar',
                        'actionName' => 'edit',
                        'pkIndex' => 'id'
                    ))
                    ->addAction(array(
                        'label' => 'Excluir',
                        'actionName' => 'delete',
                        'pkIndex' => 'id',
                        'cssClass' => 'del'
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar Veículo',
                        'actionName' => 'view-veiculo-ajax',
                        'cssClass' => 'view-veiculo',
                        'pkIndex' => 'id'
                    ))
                    ->setHasOrdering(true);

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

    public function viewVeiculoAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id == null) {
            throw new \Exception('Placa do veículo inválida');
        }

        $veiculo = $this->em->find('wms:Movimentacao\Veiculo', $id);

        if ($veiculo == null) {
            throw new \Exception('Este veículo não existe');
        }

        $this->view->veiculo = $veiculo;
    }

    /**
     * Edita um registro
     * @return void 
     */
    public function editAction()
    {

        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Adicionar novo',
                    'cssClass' => 'btnAdd',
                    'urlParams' => array(
                        'action' => 'add'
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Excluir',
                    'cssClass' => 'btnDelete',
                    'urlParams' => array(
                        'action' => 'delete'
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        $form = new $formClass;

        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the edit action');

            $entity = $this->repository->findOneBy(array($this->pkField => $id));

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $this->repository->save($entity, $this->getRequest()->getParams());
                $this->em->flush();
                $this->_helper->messenger('success', 'Veículo alterado com sucesso');
                return $this->redirect('index');
            }
            $form->setDefaultsFromEntity($entity); // pass values to form
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        //seta um campo como readonly
        $form->getSubForm('identificacao')->getElement('id')->setAttrib('readonly', 'readonly');

        $this->view->form = $form;
    }

}
