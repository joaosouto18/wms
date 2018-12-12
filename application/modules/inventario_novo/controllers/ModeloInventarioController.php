    <?php
    /**
     * Created by PhpStorm.
     * User: Joaby
     * Date: 04/12/2018
     * Time: 12:15
     */

    use Wms\Module\Web\Controller\Action,
        Wms\Module\Web\Grid\InventarioNovo\ModeloInventario as ModelosInventarioGrid,
        Wms\Module\InventarioNovo\Form\ModeloInventario as ModeloInventarioForm,
        Wms\Module\Web\Page,
        Wms\Domain\Entity\InventarioNovo;


    class Inventario_Novo_ModeloInventarioController  extends  Action\Crud
    {
        protected $entityName = 'InventarioNovo\ModeloInventario';

        public function indexAction()
        {
            /** @var \Wms\Domain\Entity\InventarioNovo\ModeloInventarioRepository $modeloRepository */
            /*
            $modeloRepository   = $this->em->getRepository('wms:InventarioNovo\ModeloInventario');

            $modelos = $modeloRepository->getModelos();

            $grid = new ModelosInventarioGrid();
            $this->view->grid = $grid->init($modelos)->render();
            */


            echo 'teste';
        }

        public function deleteAction()
        {
            try{
                $id = $this->_getParam('id');
                $modeloRepository = $this->em->getRepository('wms:InventarioNovo\ModeloInventario');
                $modeloSeparacao   = $modeloRepository->findOneBy(array('id'=>$id));

                $this->getEntityManager()->remove($modeloSeparacao);
                $this->getEntityManager()->flush();
                $this->addFlashMessage('success', 'Modelo de Separação excluido com sucesso' );
            } catch (\Exception $ex) {
                $this->addFlashMessage('error', $ex->getMessage() );
            }
            $this->_redirect('/inventario-novo/modelo-inventario');
        }

        public function addAction()
        {
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
                    ),
                )
            ));

            $form = new ModeloInventarioForm();

            try {
                if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                    $params = $this->getRequest()->getParams();
                    $this->repository->save(new InventarioNovo\ModeloInventario(), $params);
                    $this->_helper->messenger('success', 'Modelo de Inventário inserido com sucesso.');
                    return $this->redirect('index');
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }

            $this->view->form = $form;
        }

        public function editAction()
        {
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
                    ),
                )
            ));

            $form = new ModeloInventarioForm();

            try {

                $id = $this->getRequest()->getParam('id');

                if ($id == null)
                    throw new \Exception('Id must be provided for the edit action');

                /** @var InventarioNovo\ModeloInventario $entity */
                $entity = $this->repository->findOneBy(array($this->pkField => $id));

                if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

                    $params = $this->getRequest()->getParams();
                    try {
                        $this->repository->save($entity, $params);
                        $this->_helper->messenger('success', 'Registro alterado com sucesso');
                    }
                    catch(Exception $e) {
                        $this->_helper->messenger("error", $e->getMessage());
                    }

                    return $this->redirect('index');
                }
                else {

                    $dados = array();

                    $dados['descricao'] = $entity->getDescricao();
                    $dados['ativo'] = $entity->getAtivo();
                    $dados['dthCriacao'] = $entity->getDthCriacao();
                    $dados['itemAItem'] = $entity->getItemAItem();
                    $dados['controlaValidade'] = $entity->getControlaValidade();
                    $dados['exigeUma'] = $entity->getExigeUMA();
                    $dados['numContagens'] = $entity->getNumContagens();
                    $dados['comparaEstoque'] = $entity->getComparaEstoque();
                    $dados['usuarioNContagens'] = $entity->getUsuarioNContagens();
                    $dados['contarTudo'] = $entity->getContarTudo();
                    $dados['volumesSeparadamente'] = $entity->getVolumesSeparadamente();
                    $dados['importaErp'] = $entity->getImportaERP();
                    $dados['idLayoutExp'] = $entity->getIdLayoutEXP();
                    $dados['default'] = $entity->getDefault();

                    $form->populate($dados); // pass values to form
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
            $this->view->form = $form;
        }
    }