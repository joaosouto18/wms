<?php
use Wms\Module\Web\Controller\Action;
use Wms\Module\Enderecamento\Form\Modelo as Modelo;
use Wms\Module\Web\Page;
use Wms\Module\Enderecamento\Grid\Modelo as ModeloGrid;

class Enderecamento_ModeloController extends Action
{

    public function indexAction()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Novo',
                    'cssClass' => 'btnSave',
                    'urlParams' => array(
                        'action' => 'add'
                    ),
                    'tag' => 'a'
                ),
            )
        ));
        /** @var \Wms\Domain\Entity\Enderecamento\ModeloRepository $modeloRepo */
        $modeloRepo = $this->em->getRepository('wms:Enderecamento\Modelo');
        $modelos = $modeloRepo->getModelos();

        $grid = new ModeloGrid();
        $this->view->grid = $grid->init($modelos)->render();
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

        $areaArmazenagemRepo = $this->getEntityManager()->getRepository("wms:Deposito\AreaArmazenagem");
        $this->view->areaArmazenagens = $areaArmazenagemRepo->findAll();

        $estruturaArmazenagemRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Estrutura\Tipo");
        $this->view->estruturaArmazenagens = $estruturaArmazenagemRepo->findAll();

        $tipoEnderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco\Tipo");
        $this->view->tiposEndereco = $tipoEnderecoRepo->findAll();

        $caracteristicaEnderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco\Caracteristica");
        $this->view->caracteristicaEndereco = $caracteristicaEnderecoRepo->findAll();

        try {
            if ($this->getRequest()->isPost()) {
                $params = $this->getRequest()->getParams();
                $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');

                $params['referencia'] = null;
                if ( $params['endereco'] != "") {
                    $depositoEnderecoEn = $depositoEnderecoRepo->findOneBy(array('descricao' => $params['endereco']));
                    if (!isset($depositoEnderecoEn)) {
                        $this->_helper->messenger('error', 'Insira um Endereço Válido.');
                        $this->_redirect('enderecamento/modelo/index');
                    }
                    $params['referencia'] = $depositoEnderecoEn->getId();
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloRepository $modeloEnderecamentoRepo */
                $modeloEnderecamentoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo');
                $modeloEnderecamento = $modeloEnderecamentoRepo->insert($params);
                $params['modeloEnderecamento'] = $modeloEnderecamento->getId();

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloAreaArmazenagemRepository $modeloAreaArmazenagemRepo */
                $modeloAreaArmazenagemRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloAreaArmazenagem');

                foreach ($params['areaArmazenagem'] as $key => $modeloAreaArmazenagem) {
                    if (isset($modeloAreaArmazenagem) && !empty($modeloAreaArmazenagem)) {
                        $params['idAreaArmazenagem'] = $key;
                        $params['prioridadeAreaArmazenagem'] = $modeloAreaArmazenagem;
                        $modeloAreaArmazenagemEn = $modeloAreaArmazenagemRepo->insert($params);
                    }
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloEstruturaArmazenagemRepository $modeloEstruturaArmazenagemRepo */
                $modeloEstruturaArmazenagemRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloEstruturaArmazenagem');

                foreach ($params['estruturaArmazenagem'] as $key => $estruturaArmazenagem) {
                    if (isset($estruturaArmazenagem) && !empty($estruturaArmazenagem)) {
                        $params['idTipoEstruturaArmazenagem'] = $key;
                        $params['prioridadeEstruturaArmazenagem'] = $estruturaArmazenagem;
                        $modeloEstruturaArmazenagemRepo->insert($params);
                    }
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloTipoEnderecoRepository $modeloTipoEnderecoRepo */
                $modeloTipoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloTipoEndereco');

                foreach ($params['tipoEndereco'] as $key => $tipoEndereco) {
                    if (isset($tipoEndereco) && !empty($tipoEndereco)) {
                        $params['idTipoEndereco'] = $key;
                        $params['prioridadeTipoEndereco'] = $tipoEndereco;
                        $modeloTipoEnderecoRepo->insert($params);
                    }
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloCaracteristicaEnderecoRepository $modeloCaracteristicaEnderecoRepo */
                $modeloCaracteristicaEnderecoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloCaracteristicaEndereco');

                foreach ($params['caracteristicaEndereco'] as $key => $caracteristicaEndereco) {
                    if (isset($caracteristicaEndereco) && !empty($caracteristicaEndereco)) {
                        $params['idCaracteristicaEndereco'] = $key;
                        $params['prioridadeCaracteristicaEndereco'] = $caracteristicaEndereco;
                        $modeloCaracteristicaEnderecoRepo->insert($params);
                    }
                }
                $this->em->flush();

                $this->_helper->messenger('success', 'Modelo cadastrado com sucesso.');
                $this->_redirect('enderecamento/modelo/index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
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

        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the edit action');

            $areaArmazenagemRepo = $this->getEntityManager()->getRepository("wms:Deposito\AreaArmazenagem");
            $this->view->areaArmazenagens = $areaArmazenagemRepo->findAll();

            $estruturaArmazenagemRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Estrutura\Tipo");
            $this->view->estruturaArmazenagens = $estruturaArmazenagemRepo->findAll();

            $tipoEnderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco\Tipo");
            $this->view->tiposEndereco = $tipoEnderecoRepo->findAll();

            $caracteristicaEnderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco\Caracteristica");
            $this->view->caracteristicaEndereco = $caracteristicaEnderecoRepo->findAll();

            $this->view->modelo = $entity = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo')->findOneBy(array('id' => $id));
            $this->view->populaTipoEndereco = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloTipoEndereco')->findBy(array('modeloEnderecamento' => $id));
            $this->view->populaAreaArmazenagens = $entityModeloAreaArmazenagem = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloAreaArmazenagem')->findBy(array('modeloEnderecamento' => $id));
            $this->view->populaEstruturaArmazenagens = $entityModeloEstruturaArmazenagem = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloEstruturaArmazenagem')->findBy(array('modeloEnderecamento' => $id));
            $this->view->populaCaracteristicaEndereco = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloCaracteristicaEndereco')->findBy(array('modeloEnderecamento' => $id));

            if ($this->getRequest()->isPost()) {

                $params = $this->getRequest()->getParams();
                $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');

                $params['referencia'] = null;
                if ( $params['endereco'] != "") {
                    $depositoEnderecoEn = $depositoEnderecoRepo->findOneBy(array('descricao' => $params['endereco']));
                    if (!isset($depositoEnderecoEn)) {
                        $this->_helper->messenger('error', 'Insira um Endereço Válido.');
                        $this->_redirect('enderecamento/modelo/index');
                    }
                    $params['referencia'] = $depositoEnderecoEn->getId();
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloRepository $modeloEnderecamentoRepo */
                $modeloEnderecamentoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo');
                $modeloEnderecamento = $modeloEnderecamentoRepo->update($params);
                $params['modeloEnderecamento'] = $modeloEnderecamento->getId();

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloAreaArmazenagemRepository $modeloAreaArmazenagemRepo */
                $modeloAreaArmazenagemRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloAreaArmazenagem');
                $modeloAreaArmazenagemRepo->delete($params);


                foreach ($params['areaArmazenagem'] as $key => $modeloAreaArmazenagem) {
                    if (isset($modeloAreaArmazenagem) && !empty($modeloAreaArmazenagem)) {
                        $params['idAreaArmazenagem'] = $key;
                        $params['prioridadeAreaArmazenagem'] = $modeloAreaArmazenagem;
                        $modeloAreaArmazenagemRepo->insert($params);
                    }
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloEstruturaArmazenagemRepository $modeloEstruturaArmazenagemRepo */
                $modeloEstruturaArmazenagemRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloEstruturaArmazenagem');
                $modeloEstruturaArmazenagemRepo->delete($params);

                foreach ($params['estruturaArmazenagem'] as $key => $estruturaArmazenagem) {
                    if (isset($estruturaArmazenagem) && !empty($estruturaArmazenagem)) {
                        $params['idTipoEstruturaArmazenagem'] = $key;
                        $params['prioridadeEstruturaArmazenagem'] = $estruturaArmazenagem;
                        $modeloEstruturaArmazenagemRepo->insert($params);
                    }
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloTipoEnderecoRepository $modeloTipoEnderecoRepo */
                $modeloTipoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloTipoEndereco');
                $modeloTipoEnderecoRepo->delete($params);

                foreach ($params['tipoEndereco'] as $key => $tipoEndereco) {
                    if (isset($tipoEndereco) && !empty($tipoEndereco)) {
                        $params['idTipoEndereco'] = $key;
                        $params['prioridadeTipoEndereco'] = $tipoEndereco;
                        $modeloTipoEnderecoRepo->insert($params);
                    }
                }

                /** @var \Wms\Domain\Entity\Enderecamento\ModeloCaracteristicaEnderecoRepository $modeloCaracteristicaEnderecoRepo */
                $modeloCaracteristicaEnderecoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloCaracteristicaEndereco');
                $modeloCaracteristicaEnderecoRepo->delete($params);

                foreach ($params['caracteristicaEndereco'] as $key => $caracteristicaEndereco) {
                    if (isset($caracteristicaEndereco) && !empty($caracteristicaEndereco)) {
                        $params['idCaracteristicaEndereco'] = $key;
                        $params['prioridadeCaracteristicaEndereco'] = $caracteristicaEndereco;
                        $modeloCaracteristicaEnderecoRepo->insert($params);
                    }
                }

                $this->em->flush();

                $this->_helper->messenger('success', 'Registro alterado com sucesso');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function deleteAction()
    {
        $params = $this->_getAllParams();
        try {
            $params['modeloEnderecamento'] = $params['id'];
            /** @var \Wms\Domain\Entity\Enderecamento\ModeloRepository $modeloEnderecamentoRepo */
            $modeloEnderecamentoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo');
            $modeloEnderecamentoRepo->delete($params);

            /** @var \Wms\Domain\Entity\Enderecamento\ModeloAreaArmazenagemRepository $modeloAreaArmazenagemRepo */
            $modeloAreaArmazenagemRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloAreaArmazenagem');
            $modeloAreaArmazenagemRepo->delete($params);

            /** @var \Wms\Domain\Entity\Enderecamento\ModeloEstruturaArmazenagemRepository $modeloEstruturaArmazenagemRepo */
            $modeloEstruturaArmazenagemRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloEstruturaArmazenagem');
            $modeloEstruturaArmazenagemRepo->delete($params);

            /** @var \Wms\Domain\Entity\Enderecamento\ModeloTipoEnderecoRepository $modeloTipoEnderecoRepo */
            $modeloTipoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\ModeloTipoEndereco');
            $modeloTipoEnderecoRepo->delete($params);

            $this->em->flush();

            $this->_helper->messenger('success', 'Modelo de Enderecamento excluido com sucesso');
            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }
}