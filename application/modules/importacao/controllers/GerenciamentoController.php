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
            $fornecedorRepo = $this->getEntityManager()->getRepository('wms:Pessoa\Papel\Fornecedor');
            $this->view->getFornecedores = $fornecedorRepo->getAllByExterno();
            $acoesId = explode(",", $acao);
            $acaoIntEntity = $acaoIntRepo->findOneBy(array('id' => $acoesId[0]));
            $this->view->tipoAcao = $acaoIntEntity->getTipoAcao()->getId();
            $dataUltimaExecucao = $acaoIntEntity->getDthUltimaExecucao();
            $this->view->dataInicio = $dataUltimaExecucao->format('d/m/Y H:i:s');

            $idFiltro = AcaoIntegracaoFiltro::DATA_ESPECIFICA;
            $options = null;
            if (isset($params['submitCodigos'])) {
                $codigo = isset($params['codigo']) ? $params['codigo'] : null;
                $serie = isset($params['serie']) ? $params['serie'] : null;
                $fornecedor = isset($params['fornecedor']) ? $params['fornecedor'] : null;

                /** verifica se existe o caracter especifico para cada tipo de filtro */
                $conjuntoCodigo  = strpos($codigo,',');
                $intervaloCodigo = strpos($codigo,'-');

                $string = null;
                if ($conjuntoCodigo == true) {
                    $idFiltro = AcaoIntegracaoFiltro::CONJUNTO_CODIGO;
                    $string = $codigo.'-'.$serie.'-'.$fornecedor;
                    $string = str_replace('--','',$string);
                    $options = explode('-',$string);
                } else if ($intervaloCodigo == true) {
                    $idFiltro = AcaoIntegracaoFiltro::INTERVALO_CODIGO;
                    $string = $codigo.'-'.$serie.'-'.$fornecedor;
                    $string = str_replace('--','',$string);
                    $options = explode('-',$string);
                } else if ($conjuntoCodigo === false && $intervaloCodigo === false) {
                    $idFiltro = AcaoIntegracaoFiltro::CODIGO_ESPECIFICO;
                    $string = $codigo.'-'.$serie.'-'.$fornecedor;
                    $string = str_replace('--','',$string);
                    $options = explode('-',$string);
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