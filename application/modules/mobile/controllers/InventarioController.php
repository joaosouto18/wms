<?php
use Wms\Controller\Action;


class Mobile_InventarioController extends Action
{

    protected $_service;

    public function init()
    {
        /** @var \Wms\Service\Mobile\Inventario _service */
        $this->_service = new \Wms\Service\Mobile\Inventario();
        $this->_service->setEm($this->getEntityManager());
        parent::init();
    }

    public function selecionaContagemAction()
    {
        $idInventario               = $this->_getParam('idInventario');
        $numContagensRegra          = $this->getSystemParameterValue('REGRA_CONTAGEM');
        $numContagens               = $this->_service->getContagens(array('idInventario' => $idInventario, 'regraContagem' => $numContagensRegra));
        $this->view->numContagens   = $numContagens;
        $this->view->idInventario   = $idInventario;
    }

    public function consultaEnderecoAction()
    {
        $idInventario = $this->_getParam('idInventario');
        $numContagem  = $this->_getParam('numContagem', null);
        $divergencia  = $this->_getParam('divergencia', null);
        $this->view->idInventario = $idInventario;
        $idContagemOs = $this->_service->criarOS($idInventario);

        $enderecos                  = $this->_service->getEnderecos($idInventario, $numContagem, $divergencia);
        $this->view->enderecos      = $enderecos['enderecos'];
        $this->view->botoes         = false;

        $form = new \Wms\Module\Mobile\Form\Endereco();
        $form->setLabel('Busca por endereço');
        $this->view->form   = $form;
        $codigoBarras       = $this->_getParam('codigoBarras');
        $nivelParam         = $this->_getParam('nivel', null);
        if (isset($codigoBarras) && !empty($codigoBarras)) {

            $coletorService = new \Wms\Service\Coletor();
            $codigoBarras = $coletorService->retiraDigitoIdentificador($codigoBarras);
            if (($nivelParam != null)) {
                $formNivel = new \Wms\Module\Mobile\Form\Nivel();
                $formNivel->populate(array('codigoBarras' => $this->_getParam('codigoBarras')));
                $this->view->form = $formNivel;
                $this->render('form');
                return false;
            } else {
                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
                $enderecoArray  = $enderecoRepo->getEnderecoIdByDescricao($codigoBarras);
                if (count($enderecoArray) == 0) {
                    $result = array(
                        'status' => 'error',
                        'msg' => 'Endereço não encontrado',
                        'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario
                    );
                    $this->checkErrors($result);
                }
                $enderecoId = $enderecoArray[0]['COD_DEPOSITO_ENDERECO'];

                $result = $this->_service->consultaVinculoEndereco($idInventario, $enderecoId);
                $this->checkErrors($result);

                $recontagemMesmoUsuario = $this->getSystemParameterValue('RECONTAGEM_MESMO_USUARIO');
                $resultOsEnd = $this->_service->consultaOseEnd($idContagemOs, $result['idInventarioEnd'], $idInventario, $recontagemMesmoUsuario);
                $this->checkErrors($resultOsEnd);

                $populateForm   = array('idEndereco' => $enderecoId, 'idContagemOs' => $idContagemOs, 'idInventarioEnd' => $result['idInventarioEnd'], 'numContagem' => $numContagem);
                $this->view->idInventarioEnd = $result['idInventarioEnd'];
                $this->view->numContagem     = $numContagem;
                $this->view->botoes          = true;
            }

            $exigenciaUma = $this->getSystemParameterValue('EXIGENCIA_UMA');
            if ($exigenciaUma == 'S') {
                $formUma =  new \Wms\Module\Mobile\Form\Uma();
                $formUma->setUrlParams(array('controller' => 'inventario', 'action' => 'consulta-uma'));
                $this->view->form = $formUma;
                return false;
            }

            $this->view->form = $this->_service->formProduto($populateForm);
        }

        $this->view->urlVoltar       = '/mobile/inventario/seleciona-contagem/idInventario/'.$idInventario;
        $this->render('form');
    }

    public function consultaUmaAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        if (isset($codigoBarras) && !empty($codigoBarras)) {
            $this->view->form = $this->_service->formProduto();
        }
        $this->render('form');
    }

    public function consultaProdutoAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $params = $this->_getAllParams();
        $divergencia  = $this->_getParam('divergencia', null);

        if (isset($codigoBarras)) {
            $form =  new \Wms\Module\Mobile\Form\InventarioQuantidade();

            $coletorService = new \Wms\Service\Coletor();
            $codigoBarras = $coletorService->adequaCodigoBarras($codigoBarras);
            $params['codigoBarras'] = $codigoBarras;

            if ($codigoBarras == 0 && is_integer($codigoBarras)) {

                $params = $this->_getAllParams();
                $paramsSystem['validaEstoqueAtual'] = $this->getSystemParameterValue('VALIDA_ESTOQUE_ATUAL');
                $paramsSystem['regraContagemParam'] = $this->getSystemParameterValue('REGRA_CONTAGEM');

                $this->_service->contagemEndereco($params);
                if ($this->_service->finalizaContagemEndereco($params,$paramsSystem)) {
                    $this->addFlashMessage('success', 'Endereço vazio invetariado com sucesso');
                }

                $this->redirect('consulta-endereco','inventario', 'mobile', array('idInventario' => $this->_getParam('idInventario')));
            } else {

                $result = $this->_service->consultarProduto($params);
                $this->checkErrors($result);
                $result['populateForm']['numContagem']  =  $params['numContagem'];
                //Verifica se existe contagem endereco
                $result['populateForm']['contagemEndId'] = $this->_service->verificaContagemEnd($result['populateForm']);

                $form->populate($result['populateForm']);
            }
            $this->view->form = $form;
        }
        $this->view->urlVoltar       = '/mobile/inventario/consulta-endereco/idInventario/'.$params['idInventario'].'/numContagem/'.$params['numContagem'].'/divergencia/'.$divergencia;

        $this->render('form');
    }

    public function confirmaContagemAction()
    {
        $params = $this->_getAllParams();
        $result = $this->_service->contagemEndereco($params);
        $this->checkErrors($result);

        $populateForm = array('idEndereco' => $params['idEndereco'], 'idContagemOs' => $params['idContagemOs'],
            'idInventarioEnd' => $params['idInventarioEnd'], 'numContagem' => $params['numContagem'], 'contagemEndId' => $result['contagemEndId']);
        $this->view->form = $this->_service->formProduto($populateForm);
        $this->view->idInventarioEnd = $params['idInventarioEnd'];
        $this->view->idInventario    = $params['idInventario'];
        $this->view->numContagem     = $params['numContagem'];
        $this->view->botoes          = true;
        $this->view->urlVoltar       = '/mobile/inventario/consulta-endereco/idInventario/'.$params['idInventario'].'/numContagem/'.$params['numContagem'].'/divergencia/'.$params['divergencia'];;
        $this->render('form');
    }

    public function mudarEnderecoAction()
    {
        $params = $this->_getAllParams();
        $paramsSystem['validaEstoqueAtual'] = $this->getSystemParameterValue('VALIDA_ESTOQUE_ATUAL');
        $paramsSystem['regraContagemParam'] = $this->getSystemParameterValue('REGRA_CONTAGEM');

        if ($this->_service->finalizaContagemEndereco($params,$paramsSystem)) {
            $this->addFlashMessage('success', 'Contagem de endereço finalizada');
        } else {
            $this->addFlashMessage('warning', 'Contagem de endereço finalizada com divergência');
        }
        $this->redirect('consulta-endereco','inventario', 'mobile', array('idInventario' => $params['idInventario'], 'numContagem' => $params['numContagem'], 'divergencia' => $params['divergencia']));
    }

    public function reiniciarAction()
    {
        $params = $this->_getAllParams();
        $this->_service->removeEnderecoInventario($params);
        $this->redirect('consulta-endereco','inventario', 'mobile', array('idInventario' => $params['idInventario']));
    }

    public function checkErrors($result)
    {
        if (isset($result['status']) && $result['status'] == 'error') {
            $this->addFlashMessage("error",$result['msg']);
            $this->_redirect($result['url']);
        }
    }

}

