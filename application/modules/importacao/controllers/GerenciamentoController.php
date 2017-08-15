<?php

use Wms\Module\Web\Controller\Action;
use \Wms\Module\Web\Page;
use \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro as AcaoIntegracaoFiltro;
use \Wms\Domain\Entity\Integracao\AcaoIntegracao as AcaoIntegracao;

class Importacao_GerenciamentoController extends Action
{

    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        $acao = $params['id'];

        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Importar Dados',
                    'cssClass' => 'btnSave',
                    'urlParams' => array(
                        'module' => 'importacao',
                        'controller' => 'gerenciamento',
                        'action' => 'index',
                        'id' => $acao,
                        'efetivar' => 'efetivar'
                    ),
                    'tag' => 'a'
                )
            )
        ));

        try {
            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            /** @var \Wms\Domain\Entity\Pessoa\Papel\FornecedorRepository $fornecedorRepo */
//            $fornecedorRepo = $this->getEntityManager()->getRepository('wms:Pessoa\Papel\Fornecedor');
//            $this->view->getFornecedores = $fornecedorRepo->getAllByExterno();
            $acoesId = explode(",", $acao);
            $acaoIntEntity = $acaoIntRepo->findOneBy(array('id' => $acoesId[0]));
            $this->view->tipoAcao = $acaoIntEntity->getTipoAcao()->getId();
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
                $result = $acaoIntRepo->listaTemporaria($integracoes, $options, $idFiltro);
            } else if (isset($params['efetivar'])) {
                $result = $acaoIntRepo->efetivaTemporaria($integracoes, $idFiltro);
            }

            if (isset($result)) {
                if (is_string($result)) {
                    $this->addFlashMessage('error',$result);
                    $this->redirect('index','gerenciamento','importacao', array('id' => $acao));
                } else if ($result === true) {
                    if ($acaoIntEntity->getTipoAcao()->getId() ==  AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS) {
                        $this->addFlashMessage('success','Notas Fiscais enviadas com sucesso!');
                        $this->redirect('index','recebimento','web');
                    } else if ($acaoIntEntity->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PEDIDOS) {
                        $this->addFlashMessage('success','Cargas enviadas com sucesso!');
                        $this->redirect('index','index','expedicao');
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
            $this->_helper->messenger('error', $e->getMessage());
        }
    }
}