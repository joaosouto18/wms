<?php


use Wms\Module\Web\Controller\Action;
use Wms\Module\Web\Page;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_Novo_IndexController  extends Action
{

    public function indexAction()
    {
        $importaInventario = $this->getSystemParameterValue("IMPORTA_INVENTARIO");
        $this->view->usaGrade = ($this->getSystemParameterValue("UTILIZA_GRADE") === 'S');
        $this->view->showCodInvErp = ($importaInventario == 'S');

        $buttons[] = array(
            'label' => 'Novo Inventário por Endereço',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'inventario_novo',
                'controller' => 'index',
                'action' => 'criar-inventario',
                'criterio' => 'endereco'
            ),
            'tag' => 'a'
        );
        $buttons[] = array(
            'label' => 'Novo Inventário por Produto',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'inventario_novo',
                'controller' => 'index',
                'action' => 'criar-inventario',
                'criterio' => 'produto'
            ),
            'tag' => 'a'
        );

        $this->configurePage($buttons);
    }

    public function getInventariosAjaxAction()
    {
        $data = json_decode($this->getRequest()->getRawBody(),true);
        $response = new stdClass();
        if (isset($data['getStatusArr'])) {
            $response->statusArr = \Wms\Domain\Entity\InventarioNovo::$tipoStatus;
            unset($data['getStatusArr']);
        }
        $response->inventarios = $this->_em->getRepository('wms:InventarioNovo')->listInventarios($data);
        $this->_helper->json($response);
    }

    public function criarInventarioAction()
    {
        if ($this->getRequest()->isGet()) {
            $this->view->criterio = $this->getRequest()->getParam("criterio");
            $buttons = [];
            if ($this->view->criterio === \Wms\Domain\Entity\InventarioNovo::CRITERIO_PRODUTO) {
                $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
                $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioProdutoForm();
                $this->view->form->init($utilizaGrade);
                $buttons[] = array(
                    'label' => 'Novo Inventário por Endereço',
                    'cssClass' => 'button',
                    'urlParams' => array(
                        'module' => 'inventario_novo',
                        'controller' => 'index',
                        'action' => 'criar-inventario',
                        'criterio' => 'endereco'
                    ),
                    'tag' => 'a'
                );
            } else {
                $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioEnderecoForm();
                $buttons[] = array(
                    'label' => 'Novo Inventário por Produto',
                    'cssClass' => 'button',
                    'urlParams' => array(
                        'module' => 'inventario_novo',
                        'controller' => 'index',
                        'action' => 'criar-inventario',
                        'criterio' => 'produto'
                    ),
                    'tag' => 'a'
                );
            }
            $this->configurePage($buttons);
        } elseif ($this->getRequest()->isPost()) {
            $data = json_decode($this->getRequest()->getRawBody(),true);
            $objResponse = new stdClass();
            try{
                /** @var \Wms\Service\InventarioService $invServc */
                $invServc = $this->getServiceLocator()->getService("Inventario");
                $novoInventario = $invServc->registrarNovoInventario($data);
                $objResponse->msg = $novoInventario->getDescricao() . " número: " . $novoInventario->getId();
                $this->_helper->json($objResponse);
            } catch (Exception $e) {
                $this->getResponse()->setHttpResponseCode((!empty($e->getCode())) ? $e->getCode() : 500);
                $objResponse->exception = $e->getMessage();
                $this->_helper->json($objResponse);
            }
        }
    }

    public function getEnderecosCriarAjaxAction()
    {
        $data = $this->getRequest()->getParams();
        $source = $this->_em->getRepository('wms:InventarioNovo')->getEnderecosCriarNovoInventario($data);
        $this->_helper->json($source);
    }

    public function getProdutosCriarAjaxAction()
    {
        $data = $this->getRequest()->getParams();
        $source = $this->_em->getRepository('wms:InventarioNovo')->getProdutosCriarNovoInventario($data);
        $this->_helper->json($source);
    }

    public function configurePage($buttons = [])
    {
        Page::configure(array('buttons' => $buttons));
    }

    public function liberarAction ()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            if (empty($id)) {
                throw new Exception("ID do Inventário não foi especificado");
            }

            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");
            $result = $invServc->liberarInventario($id);

            if (is_array($result)) {
                $grid = new \Wms\Module\InventarioNovo\Grid\ImpedimentosGrid();
                $this->view->grid = $grid->init($result);
                $this->addFlashMessage("warning", "Estes elementos impedem de liberar o inventário $id");
                $this->renderScript('index\impedimentos.phtml');
            } else {
                $this->addFlashMessage("success", "Inventário $id liberado com sucesso");
                $this->redirect();
            }
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
    }

}