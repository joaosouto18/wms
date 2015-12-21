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
class Web_PracaController extends Crud
{

    protected $entityName = 'MapaSeparacao\Praca';


    public function indexAction()
    {
        $form = new Wms\Module\Web\Form\MapaSeparacao\FiltroPraca;
        $form->setAttrib('class', 'filtro');

        if ( $form->getParams()!="" ){
            $values = $form->getParams();
        } else {
            $values = array('submit'=>'Buscar');
        }

        if ($values ) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                ->select('p.id,p.nomePraca')
                ->from('wms:MapaSeparacao\Praca', 'p')
                ->orderBy('p.id');

            if (!empty($id)) {
                $source->andWhere("p.id = '{$id}'");
            }

            if (!empty($nomePraca)) {
                $source->andWhere("p.nomePraca = '{$nomePraca}'");
            }


            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('grid-praca');
            $grid
                ->addColumn(array(
                    'label' => 'Código Praça',
                    'index' => 'id'
                ))
                ->addColumn(array(
                    'label' => 'Nome Praça',
                    'index' => 'nomePraca'
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
            $pracaRepo = $this->getEntityManager()->getRepository("wms:".$this->entityName);

            $valores=$this->getRequest()->getPost();
            if ($this->getRequest()->isPost() && $form->isValid($valores)) {

                $pracaRepo->salvar($valores);
                $this->_helper->messenger('success', 'Praça Cadastrada com Sucesso');
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

            $pracaRepo = $this->em->getRepository('wms:MapaSeparacao\Praca');
            $faixas=$pracaRepo->getFaixas($id);

            foreach ($faixas as $c => $v){
                $pracaFaixaRepo = $this->em->getRepository('wms:MapaSeparacao\PracaFaixa');
                $pracaFaixaEn   = $pracaFaixaRepo->findOneBy(array('id'=>$v['id']));

                $this->getEntityManager()->remove($pracaFaixaEn);
                $this->getEntityManager()->flush();
            }

            $rotas=$pracaRepo->getRotas($id);

            foreach ($rotas as $c => $v){
                $rotaPracaRepo = $this->em->getRepository('wms:MapaSeparacao\RotaPraca');
                $rotaPracaEn   = $rotaPracaRepo->findOneBy(array('id'=>$v['id']));

                $this->getEntityManager()->remove($rotaPracaEn);
                $this->getEntityManager()->flush();
            }

            $pracaRepo = $this->em->getRepository('wms:MapaSeparacao\Praca');
            $pracaEn   = $pracaRepo->findOneBy(array('id'=>$id));

            $this->getEntityManager()->remove($pracaEn);
            $this->getEntityManager()->flush();

            $this->addFlashMessage('success', 'Praça excluida com sucesso' );
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage() );
        }
        $this->_redirect('/praca/index');
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
        $form->init(3);
        $idPraca = $this->_getParam('id');
        try {
            /** @var \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacaoRepo */
            $pracaRepo = $this->getEntityManager()->getRepository("wms:".$this->entityName);

            $valores=$this->getRequest()->getPost();
            if ($this->getRequest()->isPost() && $form->isValid($valores)) {
                $pracaRepo->salvar($valores, $idPraca);
                $this->_helper->messenger('success', 'Praça Alterada com Sucesso');
                return $this->redirect('index');
            }
            $form->setDefaultsFromIdPraca ($idPraca); // pass values to form
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }

}
