<?php
use Wms\Module\Web\Controller\Action;

class Expedicao_ConferenciaController extends Action
{

    public function indexAction()
    {
        $idExpedicao = $this->getRequest()->getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->em->getRepository('wms:Expedicao');
        $cargas = $ExpedicaoRepo->getCargas($idExpedicao);
        $centrais = $ExpedicaoRepo->getCentralEntregaPedidos($idExpedicao);
        $this->view->idExpedicao = $idExpedicao;
        $this->view->centraisEntrega = $centrais;
        $this->view->cargas = $cargas;
    }

    public function finalizarAction()
    {
        $request = $this->getRequest();
        $params = $this->_getAllParams();

        if ($request->isPost()) {
            $idExpedicao      = $request->getParam('id');
            $senhaDigitada    = $request->getParam('senhaConfirmacao');
            $centrais         = $request->getParam('centrais');
            $origin           = $request->getParam('origin');
            $senhaAutorizacao = $this->getSystemParameterValue('SENHA_FINALIZAR_EXPEDICAO');
            $submit           = $request->getParam('btnFinalizar');

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo    = $this->em->getRepository('wms:Expedicao');

            if (isset($params['codCargaExterno']) && !empty($params['codCargaExterno'])) {
                $cargaRepo = $this->em->getRepository('wms:Expedicao\Carga');
                $entityCarga = $cargaRepo->findOneBy(array('codCargaExterno' => $params['codCargaExterno'], 'expedicao'=>$idExpedicao));
                $idExpedicao = $entityCarga->getExpedicao()->getId();
            }
            $redirect = false;
            if ($submit == 'semConferencia') {
                if ($senhaDigitada == $senhaAutorizacao) {

                    $values['identificacao'] = array(
                        'tipoOrdem' => 'expedicao',
                        'idExpedicao' => $idExpedicao,
                        'idAtividade' => \Wms\Domain\Entity\Atividade::CONFERIR_EXPEDICAO,
                        'formaConferencia' => 'F'
                    );
                    /** @var Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepository */
                    $ordemServicoRepository = $this->getEntityManager()->getRepository('wms:OrdemServico');
                    $ordemServicoEntity = new \Wms\Domain\Entity\OrdemServico();
                    $ordemServicoId = $ordemServicoRepository->save($ordemServicoEntity,$values);

                    /** @var Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepository */
                    $mapaSeparacaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
                    /** @var Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepository */
                    $mapaSeparacaoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
                    /** @var Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepository */
                    $mapaSeparacaoConferenciaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');

                    $mapaSeparacaoConferenciaEntities = $mapaSeparacaoConferenciaRepository->getQuantidadesConferidasToForcarConferencia($idExpedicao);


                    foreach ($mapaSeparacaoConferenciaEntities as $mapaSeparacaoConferenciaEntity) {
                        $embalagemEntity = null;
                        $volumeEntity = null;
                        if (isset($mapaSeparacaoConferenciaEntity['COD_PRODUTO_EMBALAGEM']) && !empty($mapaSeparacaoConferenciaEntity['COD_PRODUTO_EMBALAGEM'])) {
                            $embalagemEntity = $this->getEntityManager()->getReference('wms:Produto\Embalagem', $mapaSeparacaoConferenciaEntity['COD_PRODUTO_EMBALAGEM']);
                        }
                        if (isset($mapaSeparacaoConferenciaEntity['COD_PRODUTO_VOLUME']) && !empty($mapaSeparacaoConferenciaEntity['COD_PRODUTO_VOLUME'])) {
                            $volumeEntity = $this->getEntityManager()->getReference('wms:Produto\Volume', $mapaSeparacaoConferenciaEntity['COD_PRODUTO_VOLUME']);
                        }
                        $mapaSeparacaoEntity = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao', $mapaSeparacaoConferenciaEntity['COD_MAPA_SEPARACAO']);

                        $mapaSeparacaoRepository->adicionaQtdConferidaMapa($embalagemEntity,$volumeEntity,$mapaSeparacaoEntity,null,$mapaSeparacaoConferenciaEntity['QTD_CONFERIR'], null, $ordemServicoId, true);
                    }

                    $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais[0],false, 'S');
                    if ($result == 'true') {
                        $result = 'Expedição Finalizada com Sucesso!';
                        if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                            $this->addFlashMessage('success', $result);
                            $this->_redirect('/produtividade/carregamento/index/id/' . $idExpedicao);
                        }
                    }
                    $this->addFlashMessage('success', $result);
                } else {
                    $result = 'Senha informada não é válida';
                    $this->addFlashMessage('error', $result);
                }
                if ($origin == "expedicao") {
                    $this->_redirect('/expedicao');
                } else {
                    $this->_redirect('/expedicao/os/index/id/' . $idExpedicao);
                }
            } else {
                $result = $expedicaoRepo->finalizarExpedicao($idExpedicao,$centrais,true, 'M');

                if ($origin == 'coletor') {
                    if ($result == 'true') {
                        $result = 'Expedição Finalizada com Sucesso!';
                        if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                            $redirect = true;
                        }
                    }
                    $this->addFlashMessage('success', $result);
                    $this->_redirect('/mobile/expedicao/index/idCentral/'.$centrais);
                }
                if ($result == 'true') {
                    if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                        $redirect = true;
                    }
                    $result = 'Expedição Finalizada com Sucesso!';
                }
            }
            $this->_helper->json(array('result' => $result,
                                       'redirect' => $redirect,
                                       'idExpedicao'=>$idExpedicao));
        }
    }
}