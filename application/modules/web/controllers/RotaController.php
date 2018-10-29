<?php

use \Wms\Module\Web\Controller\Action,
    \Wms\Module\Web\Page,
    \Wms\Module\Web\Controller\Action\Crud,
    \Wms\Module\Web\Form\MapaSeparacao\Rota as RotaForm,
    \Core\Grid;

/**
 * Description of Web_VeiculoController
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class Web_RotaController extends Crud
{

    protected $entityName = 'MapaSeparacao\Rota';

    public function indexAction()
    {
        $form = new Wms\Module\Web\Form\MapaSeparacao\FiltroRota;
        $form->setAttrib('class', 'filtro');

        if ( $form->getParams()!="" ){
            $values = $form->getParams();
        } else {
            $values = array('submit'=>'Buscar');
        }

        if ($values ) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                ->select('r.id,r.nomeRota')
                ->from('wms:MapaSeparacao\Rota', 'r')
                ->orderBy('r.id');

            if (!empty($id)) {
                $source->andWhere("r.id = '{$id}'");
            }

            if (!empty($nomeRota)) {
                $source->andWhere("r.nomeRota = '{$nomeRota}'");
            }


            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('grid-praca');
            $grid
                ->addColumn(array(
                    'label' => 'Código Rota',
                    'index' => 'id'
                ))
                ->addColumn(array(
                    'label' => 'Nome Rota',
                    'index' => 'nomeRota'
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
        /** @var \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacaoRepo */
        $pracaRepo = $this->getEntityManager()->getRepository("wms:MapaSeparacao\Praca");
        $pracas=$pracaRepo->getPracas();

        //finds the form class from the entity name
        $form = new RotaForm($pracas);

        try {
            /** @var \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacaoRepo */
            $rotaRepo = $this->getEntityManager()->getRepository("wms:".$this->entityName);

            $valores=$this->getRequest()->getPost();
            if ($this->getRequest()->isPost() && $form->isValid($valores)) {

                $rotaRepo->salvar($valores);
                $this->_helper->messenger('success', 'Rota Cadastrada com Sucesso');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }


        $this->view->form = $form;
    }

    public function getpracasajaxAction(){
        /** @var \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacaoRepo */
        $pracaRepo = $this->getEntityManager()->getRepository("wms:MapaSeparacao\Praca");
        $pracas=$pracaRepo->getPracas();
        foreach ($pracas as $c => $vlr){
            echo "<option value='".$vlr['id']."' label='".$vlr['nomePraca']."'>".$vlr['nomePraca']."</option>";
        }

        die();
    }

    public function deleteAction()
    {
        try{
            $id = $this->_getParam('id');

            $rotaRepo = $this->em->getRepository('wms:MapaSeparacao\Rota');
            $pracas=$rotaRepo->getPracas($id);
            
            foreach ($pracas as $c => $v){
                $rotaPracaRepo = $this->em->getRepository('wms:MapaSeparacao\RotaPraca');
                $rotaPracaEn   = $rotaPracaRepo->findOneBy(array('id'=>$v['id']));

                $this->getEntityManager()->remove($rotaPracaEn);
                $this->getEntityManager()->flush();
            }

            $rotaRepo = $this->em->getRepository('wms:MapaSeparacao\Rota');
            $rotaEn   = $rotaRepo->findOneBy(array('id'=>$id));

            $this->getEntityManager()->remove($rotaEn);
            $this->getEntityManager()->flush();

            $this->addFlashMessage('success', 'Rota excluida com sucesso' );
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage() );
        }
        $this->_redirect('/rota/index');
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
