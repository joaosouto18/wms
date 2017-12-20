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
        /** @var \Wms\Service\Mobile\Inventario $inventarioService */
        $inventarioService = $this->_service;
        $idInventario               = $this->_getParam('idInventario');
        $numContagensRegra          = $this->getSystemParameterValue('REGRA_CONTAGEM');
        $numContagens               = $inventarioService->getContagens(array('idInventario' => $idInventario, 'regraContagem' => $numContagensRegra));
        $this->view->numContagens   = $numContagens;
        $this->view->idInventario   = $idInventario;
    }

    public function consultaEnderecoAction()
    {
        $idInventario = $this->_getParam('idInventario');
        $numContagem = $this->_getParam('numContagem', null);
        $divergencia = $this->_getParam('divergencia', null);
        $this->view->idInventario = $idInventario;
        try {
            /** @var \Wms\Service\Mobile\Inventario $inventarioService */
            $inventarioService = $this->_service;

            $idContagemOs = $inventarioService->criarOS($idInventario);

            $codigoBarras = $this->_getParam('codigoBarras');
            if(empty($codigoBarras)) {
                $enderecos = $inventarioService->getEnderecos($idInventario, $numContagem, $divergencia);
                $this->view->enderecos = $enderecos;
                $this->view->botoes = false;
            }

            $form = new \Wms\Module\Mobile\Form\Endereco();
            $form->setLabel('Busca por endereço');
            $this->view->form = $form;
            $this->view->codigoBarras = $codigoBarras;
            $nivelParam = $this->_getParam('nivel', null);
            if (isset($codigoBarras) && !empty($codigoBarras)) {
            $this->view->parametroItem = $this->getSystemParameterValue('INVENTARIO_ITEM_A_ITEM');

                $codigoBarrasSemDigito = \Wms\Util\Coletor::retiraDigitoIdentificador($codigoBarras);
                if (($nivelParam != null)) {
                    $formNivel = new \Wms\Module\Mobile\Form\Nivel();
                    $formNivel->populate(array('codigoBarras' => $this->_getParam('codigoBarras')));
                    $this->view->form = $formNivel;
                    $this->render('form');
                    return false;
                } else {
                    /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                    $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
                    $endereco = \Wms\Util\Endereco::formatar($codigoBarrasSemDigito);
                    /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                    $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
                    if (empty($enderecoEn)) {
                        throw new Exception("Endereço não encontrado");
                    }
                    $produtosEndPicking = $enderecoRepo->getProdutoByEndereco($endereco, false, true);

                    $result = $inventarioService->consultaVinculoEndereco($idInventario, $enderecoEn->getId(), $numContagem, $divergencia);
                    $this->checkErrors($result);

                    $recontagemMesmoUsuario = $this->getSystemParameterValue('RECONTAGEM_MESMO_USUARIO');
                    $resultOsEnd = $inventarioService->consultaOseEnd($idContagemOs, $result['idInventarioEnd'], $idInventario, $recontagemMesmoUsuario);
                    $this->checkErrors($resultOsEnd);

                    $populateForm = array('idEndereco' => $enderecoEn->getId(), 'codigoBarrasEndereco' => $codigoBarras, 'idContagemOs' => $idContagemOs, 'idInventarioEnd' => $result['idInventarioEnd'], 'numContagem' => $numContagem, 'idInventario' => $idInventario);
                    $this->view->idInventarioEnd = $result['idInventarioEnd'];
                    $this->view->numContagem = $numContagem;
                    $this->view->divergencia = $divergencia;
                    $this->view->idInventario = $idInventario;

                    $this->view->botoes = true;
                    if (count($produtosEndPicking) > 0) {
                        $this->view->headScript()->appendFile($this->view->baseUrl() . '/wms/resources/jquery/jquery.cycle.all.latest.js');
                        $this->view->produtosEndPicking = $produtosEndPicking;
                        $this->view->enderecoBipado = $this->_getParam('codigoBarras');
                    }
                }

                $exigenciaUma = $this->getSystemParameterValue('EXIGENCIA_UMA');
                if ($exigenciaUma == 'S') {
                    $formUma = new \Wms\Module\Mobile\Form\Uma();
                    $formUma->setUrlParams(array('controller' => 'inventario', 'action' => 'consulta-uma'));
                    $this->view->form = $formUma;
                    return false;
                }

                $this->view->form = $inventarioService->formProduto($populateForm);
            }

            $this->view->urlVoltar = '/mobile/inventario/seleciona-contagem/idInventario/' . $idInventario;
            $this->render('form');
        }catch (Exception $e){
            $result = array(
                'status' => 'error',
                'msg' => $e->getMessage(),
                'url' => '/mobile/inventario/consulta-endereco/idInventario/' . $idInventario . '/numContagem/' . $numContagem . '/divergencia/' . $divergencia
            );
            $this->checkErrors($result);
        }
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
        /** @var \Wms\Service\Mobile\Inventario $inventarioService */
        $inventarioService = $this->_service;

        if (isset($codigoBarras) & $codigoBarras != "") {
            $desabilita = 0;
            if(isset($params['desabilitar'])){
                $desabilita = $params['desabilitar'];
            }
            $form =  new \Wms\Module\Mobile\Form\InventarioQuantidade();
            $form->init($desabilita);
            $codigoBarras = \Wms\Util\Coletor::adequaCodigoBarras($codigoBarras);
            $params['codigoBarras'] = $codigoBarras;
            $this->view->parametroValidade = $this->getSystemParameterValue('CONTROLE_VALIDADE');
            $embalagemEn = $this->getEntityManager()->getRepository('wms:Produto\Embalagem')->findOneBy(array('codigoBarras' => $codigoBarras));
            $validadeProduto = null;
            if (!empty($embalagemEn)) {
                $validadeProduto = $embalagemEn->getProduto()->getValidade();
            }
            $this->view->validadeProduto = $validadeProduto;

            if ($codigoBarras == 0 && is_integer($codigoBarras)) {
                $params = $this->_getAllParams();
                $paramsSystem['validaEstoqueAtual'] = $this->getSystemParameterValue('VALIDA_ESTOQUE_ATUAL');
                $paramsSystem['regraContagemParam'] = $this->getSystemParameterValue('REGRA_CONTAGEM');

                $this->checkErrors($inventarioService->checaSeInventariado($params));
                $inventarioService->contagemEndereco($params);

                if ($inventarioService->finalizaContagemEndereco($params,$paramsSystem)) {
                    $this->addFlashMessage('success', 'Endereço vazio inventariado com sucesso');
                } else {
                    $this->addFlashMessage('warning', 'Contagem de endereço finalizada com divergência');
                }

                $this->redirect('consulta-endereco','inventario', 'mobile', array('idInventario' => $this->_getParam('idInventario'),
                    'numContagem' => $params['numContagem'],
                    'divergencia' => $divergencia
                ));
            } else {
                $result = $inventarioService->consultarProduto($params);
                $this->checkErrors($result);
                $result['populateForm']['idContagemOs']       = $params['idContagemOs'];
                //Verifica se existe contagem endereco
                $result['populateForm']['contagemEndId'] = $this->checkErrors($inventarioService->verificaContagemEnd($result['populateForm']));

                if ($result['populateForm']['pickinCorreto'] == false) {
                    $endereco = $result['populateForm']['dscEndereco'];
                    $this->addFlashMessage('warning','Este produto não possuí o endereço ' . $endereco . " como picking");
                }

                $form->populate($result['populateForm']);
            }
            
            $this->view->form = $form;

        } else {

            //NENHUM CÓDIGO DE BARRAS INFORMADO
            $idInventario   = $params['idInventario'];
            $numContagem    = $params['numContagem'];
            if (isset($params['divergencia'])) {
                $divergencia    = $params['divergencia'];
            }

            $result = array(
                'status' => 'error',
                'msg' => 'Nenhum Produto ou U.M.A informado',
                'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario.'/numContagem/'.$numContagem.'/divergencia/'.$divergencia
            );

            $this->checkErrors($result);
        }

        $this->view->urlVoltar       = '/mobile/inventario/consulta-endereco/idInventario/'.$params['idInventario'].'/numContagem/'.$params['numContagem'].'/divergencia/'.$divergencia;

        $this->render('form');
    }

    public function confirmaContagemAction()
    {
        $params = $this->_getAllParams();
        $divergencia = $this->_getParam('divergencia', null);
        /** @var \Wms\Service\Mobile\Inventario $inventarioService */
        $inventarioService = $this->_service;
        $numContagem = $this->_getParam('numContagem', null);
        $idInventario = $this->_getParam('idInventario', null);
            
        $this->view->codigoBarras =  $this->_getParam('codigoBarras', null);
        
        $result = $inventarioService->contagemEndereco($params);
        $this->checkErrors($result);

        $populateForm = array('idEndereco' => $params['idEndereco'], 'idContagemOs' => $params['idContagemOs'],
            'idInventarioEnd' => $params['idInventarioEnd'], 'numContagem' => $params['numContagem'], 'contagemEndId' => $result['contagemEndId']);
        $this->view->form = $inventarioService->formProduto($populateForm);
        $this->view->idInventarioEnd = $params['idInventarioEnd'];
        $this->view->idInventario = $params['idInventario'];
        $this->view->numContagem = $params['numContagem'];
        $this->view->divergencia = $divergencia;
        $this->view->botoes = true;
        $this->view->urlVoltar = '/mobile/inventario/consulta-endereco/idInventario/' . $params['idInventario'] . '/numContagem/' . $params['numContagem'] . '/divergencia/' . $divergencia;
//        $enderecos = $inventarioService->getEnderecos($idInventario, $numContagem, $divergencia);
//        $this->view->enderecos = $enderecos;
        $this->render('form');

    }

    public function mudarEnderecoAction()
    {
        $params = $this->_getAllParams();
        $paramsSystem['validaEstoqueAtual'] = $this->getSystemParameterValue('VALIDA_ESTOQUE_ATUAL');
        $paramsSystem['regraContagemParam'] = $this->getSystemParameterValue('REGRA_CONTAGEM');
        /** @var \Wms\Service\Mobile\Inventario $inventarioService */
        $inventarioService = $this->_service;

        try {
            if ($inventarioService->finalizaContagemEndereco($params,$paramsSystem)) {
                $this->addFlashMessage('success', 'Contagem de endereço finalizada');
            } else {
                $this->addFlashMessage('warning', 'Contagem de endereço finalizada com divergência');
            }

            $divergencia = null;
            if (isset($params['divergencia']) && ($params['divergencia'] <> null)) {
                $divergencia = $params['divergencia'];
            }

            $this->redirect('consulta-endereco','inventario', 'mobile', array('idInventario' => $params['idInventario'], 'numContagem' => $params['numContagem'], 'divergencia' => $divergencia));

        } catch (Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            $this->redirect('consulta-endereco','inventario', 'mobile', array('idInventario' => $params['idInventario'], 'numContagem' => $params['numContagem'], 'divergencia' => $params['divergencia']));
        }

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
        } else {
            return $result;
        }
    }

}

