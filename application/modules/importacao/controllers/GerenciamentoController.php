<?php

use Wms\Module\Web\Controller\Action;
use \Wms\Module\Web\Page;
use \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro as AcaoIntegracaoFiltro;
use \Wms\Domain\Entity\Integracao\AcaoIntegracao as AcaoIntegracao;

class Importacao_GerenciamentoController extends Action
{

    public function produtosAjaxAction() {
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        $produtosPendentes = $acaoIntRepo->getProdutosPendentes();
        $this->view->produtos = $produtosPendentes;

        if (count($produtosPendentes) == 0) {
            $this->_redirect("/");
        }
    }

    public function integrarProdutoAjaxAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        $idProduto = $params['id'];

        try {

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

            $idIntegracao = $this->getSystemParameterValue("ID_INTEGRACAO_PRODUTOS");
            if ($idIntegracao == "") {
                throw new \Exception("Integração de Produtos não configurada");
            }

            $acaoEn = $acaoIntRepo->find($idIntegracao);
            if ($acaoEn->getIdAcaoRelacionada() != null ) {
                $idIntegracao = $acaoEn->getIdAcaoRelacionada();
                $acaoEn = $acaoIntRepo->find($idIntegracao);
            }
            $options = explode(",",$idProduto);

            $acaoIntRepo->processaAcao($acaoEn,$options,'E','P',null, \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::CODIGO_ESPECIFICO);
        } catch (\Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }

        $this->redirect("produtos-ajax");

    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        $acao = $params['id'];

        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Importar Dados',
                    'cssClass' => 'btnSave importar-dados',
                    'urlParams' => array(
                        'module' => 'importacao',
                        'controller' => 'gerenciamento',
                        'action' => 'index',
                        'id' => $acao,
                    ),
                    'tag' => 'a'
                )
            )
        ));

        $acaoIntEntity = null;

        try {
            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            $acoesId = explode(",", $acao);
            $acaoIntEntity = $acaoIntRepo->findOneBy(array('id' => $acoesId[0]));
            $dataUltimaExecucao = $acaoIntEntity->getDthUltimaExecucao();
            $this->view->dataInicio = $dataUltimaExecucao->format('d/m/Y H:i:s');

            $idFiltro = AcaoIntegracaoFiltro::DATA_ESPECIFICA;
            $options = null;
            if (isset($params['submitCodigos'])) {
                $codigo = isset($params['codigo']) ? $params['codigo'] : null;

                /** verifica se existe o caracter especifico para cada tipo de filtro */
                $conjuntoCodigo  = strpos($codigo,',');
                $intervaloCodigo = strpos($codigo,'-');

                $options = explode('-',$codigo);
                if ($conjuntoCodigo == true) {
                    $idFiltro = AcaoIntegracaoFiltro::CONJUNTO_CODIGO;
                } else if ($intervaloCodigo == true) {
                    $idFiltro = AcaoIntegracaoFiltro::INTERVALO_CODIGO;
                } else if ($conjuntoCodigo === false && $intervaloCodigo === false) {
                    $idFiltro = AcaoIntegracaoFiltro::CODIGO_ESPECIFICO;
                }
            }

            $integracoes = array();
            $arrayFinal = array();

            foreach ($acoesId as $id) {
                $acaoEn = $acaoIntRepo->find($id);
                $integracoes[] = $acaoEn;
            }

            if (isset($params['submit']) || isset($params['submitCodigos'])) {
                $result = $acaoIntRepo->listaTemporaria($integracoes, $options, $idFiltro, $params['codigo']);
            } else if (isset($params['efetivar'])) {
                $result = $acaoIntRepo->efetivaTemporaria($integracoes, $idFiltro, $params['efetivar']);
            }

            if (isset($result)) {
                if ($result === true) {
                    if ($acaoIntEntity->getTipoAcao()->getId() ==  AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS) {
                        $this->_helper->json(array('success' => 'Notas Fiscais enviadas com sucesso!', 'redirect' => '/web/recebimento/index'));
                    } else if ($acaoIntEntity->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PEDIDOS) {
                        $this->_helper->json(array('success' => 'Cargas enviadas com sucesso!', 'redirect' => '/expedicao/index/index'));
                    }
                } else {
                    $arrayFinal = $result;
                }
            }

            if (count($arrayFinal) == 1) {
                $this->addFlashMessage('info','Nenhum registro encontrado para integrar');
            }

            $this->view->valores = $arrayFinal;
        } catch (\Exception $e) {
            $redirect = '/importacao/gerenciamento/index/id/'.$acao;
            if (!empty($acaoIntEntity)) {
                if ($acaoIntEntity->getTipoAcao()->getId() ==  AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS) {
                    $redirect = '/web/recebimento/index';
                } else if ($acaoIntEntity->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PEDIDOS) {
                    $redirect = '/expedicao/index/index';
                }
            }
            $this->_helper->json(array('error' => $e->getMessage(), 'redirect' => $redirect));
        }
    }
}