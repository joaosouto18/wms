<?php

use Wms\Module\Web\Controller\Action;
use \Wms\Module\Web\Page;
use \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro as AcaoIntegracaoFiltro;

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
                    'label' => 'Buscar Registros no ERP',
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
            $acoesId = explode(",", $acao);
            $dataUltimaExecucao = $acaoIntRepo->findOneBy(array('id' => $acoesId[0]))->getDthUltimaExecucao();
            $dataUltimaExecucao = $dataUltimaExecucao->format('d/m/Y H:i:s');
            $form = new \Wms\Module\Expedicao\Form\Pedidos();
            $form->start($dataUltimaExecucao);
            $form->populate($params);
            $this->view->form = $form;

            $idFiltro = AcaoIntegracaoFiltro::DATA_ESPECIFICA;
            $options = null;
            if (isset($params['submitCodigos'])) {
                $string = $params['codigo'];
                /** verifica se existe o caracter especifico para cada tipo de filtro */
                $conjuntoCodigo  = strpos($string,',');
                $intervaloCodigo = strpos($string,'-');
                if ($conjuntoCodigo == true) {
                    $idFiltro = AcaoIntegracaoFiltro::CONJUNTO_CODIGO;
                    $options[] = $string;
                } else if ($intervaloCodigo == true) {
                    $idFiltro = AcaoIntegracaoFiltro::INTERVALO_CODIGO;
                    $options = explode('-',$string);
                } else {
                    $idFiltro = AcaoIntegracaoFiltro::CODIGO_ESPECIFICO;
                    $options[] = $string;
                }
            }

            $integracoes = array();
            $arrayFinal = array();

            foreach ($acoesId as $id) {
                $acaoEn = $acaoIntRepo->find($id);
                $integracoes[] = $acaoEn;
            }
            if (isset($params['submit']) || isset($params['submitCodigos'])) {
                $arrayFinal = $acaoIntRepo->listaTemporaria($integracoes, $options, $idFiltro);
            } else if (isset($params['efetivar'])) {
                $result = $acaoIntRepo->efetivaTemporaria($integracoes);
                if (!($result === true)) {
                    $this->addFlashMessage('error',$result);
                }
            }


            $this->view->valores = $arrayFinal;
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }
}