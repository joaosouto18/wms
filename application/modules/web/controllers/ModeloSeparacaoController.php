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
class Web_ModeloSeparacaoController extends Crud
{

    protected $entityName = 'MapaSeparacao\ModeloSeparacao';

    public function indexAction()
    {
        $form = new Wms\Module\Web\Form\MapaSeparacao\FiltroModeloSeparacao;
        $form->setAttrib('class', 'filtro');

        if ( $form->getParams()!="" ){
            $values = $form->getParams();
        } else {
            $values = array('submit'=>'Buscar');
        }

        if ($values) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('m.id,m.tipoSeparacaoFracionado,m.tipoSeparacaoNaofracionado,m.tipoQuebraFracionado,m.tipoQuebraNaofracionado')
                    ->from('wms:MapaSeparacao\ModeloSeparacao', 'm')
                    ->orderBy('m.id');

            if (!empty($id)) {
                $source->andWhere("m.id = '{$id}'");
            }


            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('grid-modelo-separacao');
            $grid
                ->addColumn(array(
                        'label' => 'Código Modelo Separação',
                        'index' => 'id'
                    ))
                    ->addColumn(array(
                        'label' => 'Tipo Separação Fracionado',
                        'index' => 'tipoSeparacaoFracionado'
                    ))
                    ->addColumn(array(
                        'label' => 'Tipo Separação Não-Fracionado',
                        'index' => 'tipoSeparacaoNaofracionado'
                    ))

                    ->addColumn(array(
                        'label' => 'Tipo Quebra Fracionado',
                        'index' => 'tipoQuebraFracionado'
                    ))
                    ->addColumn(array(
                        'label' => 'Tipo Quebra Não-Fracionado',
                        'index' => 'tipoQuebraNaofracionado'
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
                    ->setHasOrdering(true)
                    ->showExport(false)
                    ;

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

    public function addAction()
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
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        $form = new $formClass;

        try {
            /** @var \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacaoRepo */
            $modeloSeparacaoRepo = $this->getEntityManager()->getRepository("wms:".$this->entityName);

            $valores=$this->getRequest()->getPost();
            if ($this->getRequest()->isPost() && $form->isValid($valores)) {
                $modeloSeparacaoRepo->salvar($valores);
                $this->_helper->messenger('success', 'Modelo de Separação Cadastrado com Sucesso');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }



        $this->view->form = $form;
    }


    public function deleteAction()
    {
        try{
            $id = $this->_getParam('id');
            $modeloRepo = $this->em->getRepository('wms:MapaSeparacao\ModeloSeparacao');
            $modeloEn   = $modeloRepo->findOneBy(array('id'=>$id));

            $this->getEntityManager()->remove($modeloEn);
            $this->getEntityManager()->flush();
            $this->addFlashMessage('success', 'Modelo de Separação excluido com sucesso' );
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage() );
        }
        $this->_redirect('/modelo-separacao/index');
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

            /** @var \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacaoRepo */
            $modeloSeparacaoRepo = $this->getEntityManager()->getRepository("wms:".$this->entityName);
            $entity = $this->repository->findOneBy(array($this->pkField => $id));

            $valores=$this->getRequest()->getPost();
            if ($this->getRequest()->isPost() && $form->isValid($valores)) {
                $this->repository->save($entity,$valores);
                $this->_helper->messenger('success', 'Modelo de Separação Cadastrado com Sucesso');
                return $this->redirect('index');
            }
            $form->setDefaultsFromEntity($entity); // pass values to form

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }



        $this->view->form = $form;
    }

}
