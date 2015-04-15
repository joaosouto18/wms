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
            $params = $this->getRequest()->getParams();

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $this->montarModeloSeparacao($params);
                $this->em->flush();
                $this->_helper->messenger('success', 'Modelo de Separação inserido com sucesso.');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }

    private function montarModeloSeparacao($params) {

        $entity = new Expedicao\ModeloSeparacao();
        $entity->setDescricao($params['descricao']);
        $entity->setUtilizaCaixaMaster($this->getBooleanValue($params['utilizaCaixaMaster']));
        $entity->setUtilizaEtiquetaMae($this->getBooleanValue($params['utilizaEtiquetaMae']));
        $entity->setUtilizaQuebraColetor($this->getBooleanValue($params['utilizaQuebraColetor']));
        $entity->setQuebraPulmaDoca($params['quebraPulmaDoca']);
        $entity->setTipoQuebraVolume($params['tipoQuebraVolume']);
        $entity->setTipoDefaultEmbalado($params['tipoDefaultEmbalado']);
        $entity->setTipoConferenciaEmbalado($params['tipoConferenciaEmbalado']);


        $entity->setTipoConferenciaNaoEmbalado($params['tipoConferenciaNaoEmbalado']);
        $entity->setTipoSeparacaoFracionado($params['tipoSeparacaoFracionado']);
        $entity->setTipoSeparacaoNaoFracionado($params['tipoSeparacaoNaoFracionado']);

        $this->em->persist($entity);
        $this->em->flush();


//        $entityModeloSeparacaoTipoQuebraFracionado = new Expedicao\ModeloSeparacaoTipoQuebraFracionado();


        $id = $this->em->getReference("wms:Expedicao\ModeloSeparacao", $entity->getId());

        if (isset($params['ruaFracionados']) && $params['ruaFracionados'] != '0') {
            $entityModeloSeparacaoTipoQuebraFracionado = new Expedicao\ModeloSeparacaoTipoQuebraFracionado();
            $entityModeloSeparacaoTipoQuebraFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoTipoQuebraFracionado->setTipoQuebra($params['ruaFracionados']);
            $this->em->persist($entityModeloSeparacaoTipoQuebraFracionado);
            $this->em->flush();
        }
        if (isset($params['linhaDeSeparacaoFracionados']) && $params['linhaDeSeparacaoFracionados'] != '0') {
            $entityModeloSeparacaoTipoQuebraFracionado = new Expedicao\ModeloSeparacaoTipoQuebraFracionado();
            $entityModeloSeparacaoTipoQuebraFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoTipoQuebraFracionado->setTipoQuebra($params['linhaDeSeparacaoFracionados']);
            $this->em->persist($entityModeloSeparacaoTipoQuebraFracionado);
            $this->em->flush();
        }
        if (isset($params['pracaFracionados']) && $params['pracaFracionados'] != '0') {
            $entityModeloSeparacaoTipoQuebraFracionado = new Expedicao\ModeloSeparacaoTipoQuebraFracionado();
            $entityModeloSeparacaoTipoQuebraFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoTipoQuebraFracionado->setTipoQuebra($params['pracaFracionados']);
            $this->em->persist($entityModeloSeparacaoTipoQuebraFracionado);
            $this->em->flush();
        }
        if (isset($params['clienteFracionados']) && $params['clienteFracionados'] != '0') {
            $entityModeloSeparacaoTipoQuebraFracionado = new Expedicao\ModeloSeparacaoTipoQuebraFracionado();
            $entityModeloSeparacaoTipoQuebraFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoTipoQuebraFracionado->setTipoQuebra($params['clienteFracionados']);
            $this->em->persist($entityModeloSeparacaoTipoQuebraFracionado);
            $this->em->flush();
        }

        $entityModeloSeparacaoNaoFracionado = new Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado();

        if (isset($params['ruaNaoFracionados']) && $params['ruaNaoFracionados'] != '0') {
            $entityModeloSeparacaoNaoFracionado = new Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado();
            $entityModeloSeparacaoNaoFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoNaoFracionado->setTipoQuebra($params['ruaNaoFracionados']);
            $this->em->persist($entityModeloSeparacaoNaoFracionado);
            $this->em->flush();
        }
        if (isset($params['linhaDeSeparacaoNaoFracionados']) && $params['linhaDeSeparacaoNaoFracionados'] != '0') {
            $entityModeloSeparacaoNaoFracionado = new Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado();
            $entityModeloSeparacaoNaoFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoNaoFracionado->setTipoQuebra($params['linhaDeSeparacaoNaoFracionados']);
            $this->em->persist($entityModeloSeparacaoNaoFracionado);
            $this->em->flush();
        }
        if (isset($params['pracaNaoFracionados']) && $params['pracaNaoFracionados'] != '0') {
            $entityModeloSeparacaoNaoFracionado = new Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado();
            $entityModeloSeparacaoNaoFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoNaoFracionado->setTipoQuebra($params['pracaNaoFracionados']);
            $this->em->persist($entityModeloSeparacaoNaoFracionado);
            $this->em->flush();
        }
        if (isset($params['clienteNaoFracionados']) && $params['clienteNaoFracionados'] != '0') {
            $entityModeloSeparacaoNaoFracionado = new Expedicao\ModeloSeparacaoTipoQuebraNaoFracionado();
            $entityModeloSeparacaoNaoFracionado->setModeloSeparacao($id);
            $entityModeloSeparacaoNaoFracionado->setTipoQuebra($params['clienteNaoFracionados']);
            $this->em->persist($entityModeloSeparacaoNaoFracionado);
            $this->em->flush();
        }

        return $entity;
    }

    private function adicionarTipoQuebra($attribute, $tipo) {
        if ($tipo) {
            array_push($attribute, $tipo);
        }
    }

    private function getBooleanValue($param) {
        return $param ? 'S' : 'N';
    }
}