<?php

namespace Wms\Module\Web\Controller\Action;

use \Wms\Module\Web\Page;

/**
 * Description of Action
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
abstract class Crud extends \Wms\Module\Web\Controller\Action {

    protected $repository;
    protected $entityName = null;
    protected $pkField = 'id';

    public function init() {
        parent::init();

        $this->repository = $this->em->getRepository('wms:' . $this->entityName);

        //adding default buttons to the page
        if ($this->entityName != 'Armazenagem\Estrutura\Tipo') {
            Page::configure(array(
                'buttons' => array(
                    array(
                        'label' => 'Adicionar novo',
                        'cssClass' => 'btnAdd',
                        'urlParams' => array(
                            'action' => 'add'
                        ),
                        'tag' => 'a'
                    )
                )
            ));
        }
    }

    /**
     * Adiciona um registro ao banco
     * @return void 
     */
    public function addAction() {
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
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        $form = new $formClass;

        try {
            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
                $entityClass = '\\Wms\Domain\Entity\\' . $this->entityName;
                $entity = new $entityClass;
                $this->repository->save($entity, $_POST);
                $this->em->flush();
                $this->_helper->messenger('success', 'Registro adicionado com sucesso');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $form->populate($this->getRequest()->getParams());
        }

        $this->view->form = $form;
    }

    /**
     * Edita um registro
     * @return void 
     */
    public function editAction() {

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
                        'action' => 'add',
                        'id' => null
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
                $this->_helper->messenger('success', 'Registro alterado com sucesso');
                return $this->redirect('index');
            }
            $form->setDefaultsFromEntity($entity); // pass values to form
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->view->form = $form;
    }

    /**
     * Remove um registro do banco
     * @return void
     */
    public function deleteAction() {
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the delete action');

            $this->repository->remove($id);
            $this->em->flush();
            $this->_helper->messenger('success', 'Registro deletado com sucesso');
            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            return $this->redirect('index');
        }
    }

    /**
     * 
     */
    public function massDeleteAction()
    {
        $params = $this->getRequest()->getParams();
        
        if (!isset($params['mass-id']) || count($params['mass-id']) == 0) {
            throw new \Exception('Pelo menos um Id tem que ser enviado para a remocao');
        }
        
        foreach($params['mass-id'] as $id) {
            $this->repository->remove($id);
        }
        
        $this->em->flush();
        $this->_helper->messenger('success', 'Registros removidos com sucesso');
        
        $this->redirect('index');
    }
}