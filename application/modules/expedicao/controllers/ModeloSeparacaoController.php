<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao\ModeloSeparacao as ModelosSeparacaoGrid,
    Wms\Module\Expedicao\Form\ModeloSeparacao as ModeloSeparacaoForm,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Page,
    Wms\Domain\Entity\Expedicao;

class Expedicao_ModeloSeparacaoController  extends  Crud
{
    protected $entityName = 'Expedicao\ModeloSeparacao';

    public function indexAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloRepository */
        $modeloRepository   = $this->em->getRepository('wms:Expedicao\ModeloSeparacao');

        $modelos = $modeloRepository->getModelos();

        $grid = new ModelosSeparacaoGrid();
        $this->view->grid = $grid->init($modelos)->render();
    }

    public function deleteAction()
    {
        try{
            $id = $this->_getParam('id');
            $modeloRepository = $this->em->getRepository('wms:Expedicao\ModeloSeparacao');
            $modeloSeparacao   = $modeloRepository->findOneBy(array('id'=>$id));

            $this->getEntityManager()->remove($modeloSeparacao);
            $this->getEntityManager()->flush();
            $this->addFlashMessage('success', 'Modelo de Separação excluido com sucesso' );
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage() );
        }
        $this->_redirect('/expedicao/modelo-separacao');
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

        $form = new ModeloSeparacaoForm();

        try {
            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $params = $this->getRequest()->getParams();
                $this->repository->save(new Expedicao\ModeloSeparacao(), $params);
                $this->_helper->messenger('success', 'Modelo de Separação inserido com sucesso.');
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

        $form = new ModeloSeparacaoForm();

        try {

            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the edit action');

            /** @var Expedicao\ModeloSeparacao $entity */
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
                $dados['utilizaCaixaMaster'] = $entity->getUtilizaCaixaMaster();
                $dados['utilizaQuebraColetor'] = $entity->getUtilizaQuebraColetor();
                $dados['utilizaEtiquetaMae'] = $entity->getUtilizaEtiquetaMae();
                $dados['usaSequenciaRotaPraca'] = $entity->getUsaSequenciaRotaPraca();
                $dados['utilizaVolumePatrimonio'] = $entity->getUtilizaVolumePatrimonio();
                $dados['agrupContEtiquetas'] = $entity->getAgrupContEtiquetas();
                $dados['tipoAgroupSeqEtiquetas'] = $entity->getTipoAgroupSeqEtiquetas();
                $dados['usaCaixaPadrao'] = $entity->getUsaCaixaPadrao();
                $dados['criarVolsFinalCheckout'] = $entity->getCriarVolsFinalCheckout();
                $dados['imprimeEtiquetaPatrimonio'] = $entity->getImprimeEtiquetaVolume();
                $dados['quebraPulmaDoca'] = $entity->getQuebraPulmaDoca();
                $dados['quebraUnidFracionavel'] = $entity->getQuebraUnidFracionavel();
                $dados['forcarEmbVenda'] = $entity->getForcarEmbVenda();
                $dados['produtoInventario'] = $entity->getProdutoInventario();
                $dados['tipoQuebraVolume'] = $entity->getTipoQuebraVolume();
                $dados['separacaoPc'] = $entity->getSeparacaoPC();
                $dados['tipoConfCarregamento'] = $entity->getTipoConfCarregamento();
                $dados['tipoDefaultEmbalado'] = $entity->getTipoDefaultEmbalado();
                $dados['tipoConferenciaEmbalado'] = $entity->getTipoConferenciaEmbalado();
                $dados['tipoConferenciaNaoEmbalado'] = $entity->getTipoConferenciaNaoEmbalado();
                $dados['tipoSeparacaoFracionado'] = $entity->getTipoSeparacaoFracionado();
                $dados['tipoSeparacaoNaoFracionado'] = $entity->gettipoSeparacaoNaoFracionado();
                $dados['tipoSeparacaoFracionadoEmbalado'] = $entity->getTipoSeparacaoFracionadoEmbalado();
                $dados['tipoSeparacaoNaoFracionadoEmbalado'] = $entity->getTipoSeparacaoNaoFracionadoEmbalado();

                $entityModeloSeparacaoTipoQuebraFracionado = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacaoTipoQuebraFracionado")->findBy(array('modeloSeparacao' => $id));

                /** @var Expedicao\ModeloSeparacaoTipoQuebraFracionado $tipoFracionado */
                foreach ($entityModeloSeparacaoTipoQuebraFracionado as $tipoFracionado) {
                    $dados['quebraFracionados'][] = $tipoFracionado->getTipoQuebra();
                }

                $entityModeloSeparacaoTipoQuebraNaoFracionado = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado")->findBy(array('modeloSeparacao' => $id));

                /** @var Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado $tipoNaoFracionado */
                foreach ($entityModeloSeparacaoTipoQuebraNaoFracionado as $tipoNaoFracionado) {
                    $dados['quebraNaoFracionados'][] = $tipoNaoFracionado->getTipoQuebra();
                }

                $entityModeloSeparacaoTipoQuebraEmbalado = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacaoTipoQuebraEmbalado")->findBy(array('modeloSeparacao' => $id));

                /** @var Expedicao\ModeloSeparacaoTipoQuebraEmbalado $tipoEmbalado */
                foreach ($entityModeloSeparacaoTipoQuebraEmbalado as $tipoEmbalado) {
                    $dados['quebraEmbalados'][] = $tipoEmbalado->getTipoQuebra();
                }

                $form->populate($dados); // pass values to form
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->view->form = $form;
    }
}