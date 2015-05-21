<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao\ProdutosParaConferencia as ConferenciaGrid,
    Wms\Module\Web\Grid\Expedicao\Andamento as AndamentoGrid,
    Wms\Module\Web\Grid\Expedicao\CortesPendentes as CortesPendentesGrid,
    Wms\Module\Web\Page,
    Wms\Module\Expedicao\Form\OsProdutosFiltro as filtroProdutos,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao,
    Wms\Module\Web\Grid\Expedicao\OrdemServico as OsGrid;

class Expedicao_OsController extends Action
{
    public function indexAction()
    {
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('id');
        $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaSeparacaoRepo */
        $EtiquetaSeparacaoRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $resumoConferencia = $ExpedicaoRepo->getResumoConferenciaByID($idExpedicao);

        $buttons = array();

        if (($resumoConferencia['codSigla'] == \Wms\Domain\Entity\Expedicao::STATUS_EM_CONFERENCIA)
            || ($resumoConferencia['codSigla'] == \Wms\Domain\Entity\Expedicao::STATUS_EM_SEPARACAO)
            || ($resumoConferencia['codSigla'] == \Wms\Domain\Entity\Expedicao::STATUS_PRIMEIRA_CONFERENCIA)
            || ($resumoConferencia['codSigla'] == \Wms\Domain\Entity\Expedicao::STATUS_SEGUNDA_CONFERENCIA)
            || ($resumoConferencia['codSigla'] == \Wms\Domain\Entity\Expedicao::STATUS_PARCIALMENTE_FINALIZADO) ){
            $buttons[] = array(
                'label' => 'Finalizar Conferência',
                'cssClass' => 'dialogAjax',
                'urlParams' => array(
                    'module' => 'expedicao',
                    'controller' => 'conferencia',
                    'action' => 'index',
                    'origin' => 'andamento',
                    'id' => $idExpedicao
                ),
                'tag' => 'a'
            );
            $buttons[] = array(
                'label' => 'Itens Pend. Conf.',
                'cssClass' => 'dialogAjax',
                'urlParams' => array(
                    'module' => 'expedicao',
                    'controller' => 'pendencia',
                    'action' => 'index',
                    'id' => $idExpedicao,
                    'embalado' => "N"
                ),
                'tag' => 'a'
            );
            if ($ExpedicaoRepo->getProdutosEmbalado($idExpedicao)> 0) {
                $buttons[] = array(
                    'label' => 'Embalados Pend. Conf.',
                    'cssClass' => 'dialogAjax',
                    'urlParams' => array(
                        'module' => 'expedicao',
                        'controller' => 'pendencia',
                        'action' => 'index',
                        'id' => $idExpedicao,
                        'embalado' => "S"
                    ),
                    'tag' => 'a'
                );
            }
        }

        if ($resumoConferencia['codSigla'] == \Wms\Domain\Entity\Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
            $buttons[] = array(
                'label' => 'Itens Pendentes de Recebimento no Transbordo',
                'cssClass' => 'dialogAjax',
                'urlParams' => array(
                    'module' => 'expedicao',
                    'controller' => 'pendencia',
                    'action' => 'recebimento',
                    'id' => $idExpedicao
                ),
                'tag' => 'a'
            );
        }

        $buttons[] =  array(
            'label' => 'Itens conferidos',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'expedicao',
                'controller' => 'os',
                'action' => 'list'
            ),
            'tag' => 'a'
        );
        $s = new Zend_Session_Namespace('sessionUrl');

        $buttons[] =  array(
            'label' => 'Voltar para Busca de Expedições',
            'cssClass' => 'btnBack',
            //'urlParams'=>array_merge($s->url,array('control'=>'roll')),
            'tag' => 'a'
        );

        Page::configure(array('buttons' => $buttons));

        if ($resumoConferencia['qtdEtiquetas'] == NULL) {
            $qtdEtiqueta = 0;
        } else {
            $qtdEtiqueta   = $resumoConferencia['qtdEtiquetas'];
        }

        if ($resumoConferencia['qtdConferidas'] == NULL) {
            $qtdConferidas = 0;
        } else {
            $qtdConferidas = $resumoConferencia['qtdConferidas'];
        }

        $qtdPendente = $qtdEtiqueta - $qtdConferidas;
        if ($qtdEtiqueta == 0) {
            $percentualConclusao = 0.00;
        } else {
            $percentualConclusao = ($qtdConferidas / $qtdEtiqueta) * 100;
        }

        $this->view->idExpedicao         = $idExpedicao;
        $this->view->expedicao           = $ExpedicaoRepo->find($idExpedicao);
        $this->view->qtdEtiquetas        = $qtdEtiqueta;
        $this->view->qtdConferidas       = $qtdConferidas;
        $this->view->qtdPendentes        = $qtdPendente ;
        $this->view->percentualConclusao = number_format($percentualConclusao,2) . '%';
        $this->view->status              = $resumoConferencia['sigla'];
        $this->view->dataInicio          = $resumoConferencia['dataInicio']->format('d/m/Y H:i:s');

        $resumoByPlacaCarga = $EtiquetaSeparacaoRepo->getCountGroupByCentralPlaca($idExpedicao);
        foreach ($resumoByPlacaCarga as $key => $resumo) {
            $resumoByPlacaCarga[$key]['qtdExpedidoTransbordo'] = $EtiquetaSeparacaoRepo->countByPontoTransbordo(EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO,$idExpedicao , $resumo['pontoTransbordo'], $resumo['placaCarga'], $resumo['codCargaExterno']);
            $resumoByPlacaCarga[$key]['qtdRecebidoTransbordo'] = $EtiquetaSeparacaoRepo->countByPontoTransbordo(EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO,$idExpedicao , $resumo['pontoTransbordo'], $resumo['placaCarga'], $resumo['codCargaExterno']) + $resumoByPlacaCarga[$key]['qtdExpedidoTransbordo'];
            $resumoByPlacaCarga[$key]['qtdConferidas']         = $EtiquetaSeparacaoRepo->countByPontoTransbordo(EtiquetaSeparacao::STATUS_CONFERIDO,          $idExpedicao , $resumo['pontoTransbordo'], $resumo['placaCarga'], $resumo['codCargaExterno']) + $resumoByPlacaCarga[$key]['qtdRecebidoTransbordo'];
        }
        $this->view->resumoPlacaCarga    = $resumoByPlacaCarga;

        if ($resumoConferencia['dataFinalizacao'] == Null) {
            $this->view->dataFim = "Expedição em andamento";
        } else {
            $this->view->dataFim = $resumoConferencia['dataFinalizacao']->format('d/m/Y H:i:s');
        }

        $GridOs = new OsGrid();
        $this->view->gridOS = $GridOs->init($idExpedicao, $verificaReconferencia)
            ->render();

        $GridAndamento = new AndamentoGrid();
        $this->view->gridAndamento = $GridAndamento->init($idExpedicao)
            ->render();

        $pendencias = $EtiquetaSeparacaoRepo->getPendenciasByExpedicaoAndStatus($idExpedicao, EtiquetaSeparacao::STATUS_PENDENTE_CORTE, "Array");

        if (count($pendencias) > 0) {
            $GridPendencias = new CortesPendentesGrid();
            $this->view->gridPendencias = $GridPendencias->init($idExpedicao)
                ->render();
        }

        $this->view->verificaReconferencia = $verificaReconferencia;

    }

    public function relatorioAction()
    {
        $request = $this->getRequest();
        $idOS   = $request->getParam('id');
        $tipo   = $request->getParam('tipo');
        if (empty($idOS) && empty($tipo)) {
            return false;
        }
        $transbordo = false;
        if ($tipo == 'transbordo') {
            $transbordo = true;
        }
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $OsRepo */
        $OsRepo   = $this->_em->getRepository('wms:OrdemServico');
        $result = $OsRepo->getConferenciaByOs($idOS,$transbordo);
        $arrayResult = $result->getQuery()->getArrayResult();
        $this->exportCSV($arrayResult, 'conferencia-'.$idOS);
        exit;
    }

    public function conferenciaAction()
    {
        $request = $this->getRequest();
        $idOS = $request->getParam('OS');
        $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

        if ($idOS == null) {
            $idOS = $request->getParam('id');
        }

        /** @var \Wms\Domain\Entity\OrdemServicoRepository $OsRepo */
        $OsRepo   = $this->_em->getRepository('wms:OrdemServico');
        $resumoOS = $OsRepo->getResumoOsById($idOS);

        $this->view->idOS        = $resumoOS['idOS'];
        $this->view->conferente  = $resumoOS['pessoa'];
        $this->view->inicioOS    = $resumoOS['dataInicial']->format('d/m/Y H:i:s');
        $this->view->atividade   = $resumoOS['atividade'];
        $this->view->idExpedicao = $resumoOS['idExpedicao'];

        if ($resumoOS['dataFinal'] == Null) {
            $this->view->fimOS = "OS Aberta";
        } else {
            $this->view->fimOS = $resumoOS['dataFinal']->format('d/m/Y H:i:s');
        }

        if ($verificaReconferencia == 'S') {
            $GridConferencia = new ConferenciaGrid();
            $this->view->gridConferencia = $GridConferencia->init($idOS, false, 'Conferencia')->render();

            $GridReconferencia = new ConferenciaGrid();
            $this->view->gridReconferencia = $GridReconferencia->init($idOS, false, 'Reconferencia')->render();
        } else {
            $Grid = new ConferenciaGrid();
            $this->view->grid = $Grid->init($idOS, false, null)->render();
        }


    }

    public function conferenciaTransbordoAction()
    {
        $request = $this->getRequest();
        $idOS = $request->getParam('id');

        /** @var \Wms\Domain\Entity\OrdemServicoRepository $OsRepo */
        $OsRepo   = $this->_em->getRepository('wms:OrdemServico');
        $resumoOS = $OsRepo->getResumoOsById($idOS);

        $this->view->idOS        = $resumoOS['idOS'];
        $this->view->conferente  = $resumoOS['pessoa'];
        $this->view->inicioOS    = $resumoOS['dataInicial']->format('d/m/Y H:i:s');
        $this->view->atividade   = $resumoOS['atividade'];
        $this->view->idExpedicao = $resumoOS['idExpedicao'];

        if ($resumoOS['dataFinal'] == Null) {
            $this->view->fimOS = "OS Aberta";
        } else {
            $this->view->fimOS = $resumoOS['dataFinal']->format('d/m/Y H:i:s');
        }

        $Grid = new ConferenciaGrid();
        $this->view->grid = $Grid->init($idOS, true, null)
            ->render();
    }

    public function listAction()
    {
        $idExpedicao = $this->_getParam('id');

        $buttons[] =  array(
            'label' => 'Voltar para Gerencia de Expedição',
            'cssClass' => 'btnBack',
            'urlParams' => array(
                'module' => 'expedicao',
                'controller' => 'os',
                'action' => 'index',
                'id' => $idExpedicao
            ),
            'tag' => 'a'
        );
        $buttons[] =  array(
            'label' => 'Exportar CSV',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'expedicao',
                'controller' => 'os',
                'action' => 'view',
                'id' => $idExpedicao
            ),
            'tag' => 'a'
        );
        Page::configure(array('buttons' => $buttons));

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $volumesExpedicao = $expedicaoRepo->getVolumesPatrimonioByExpedicao($idExpedicao);

        $form = new filtroProdutos();
        $form->setAttrib('class', 'listar-produtos-os')
            ->setAttrib('method', 'post');

        $form->init($volumesExpedicao);

        $volumes = $expedicaoRepo->getVolumesPatrimonio($idExpedicao);
        $GridVolumes = new \Wms\Module\Web\Grid\Expedicao\VolumeExpedicao();
        $this->view->gridVolumes = $GridVolumes->init($volumes)->render();

        $values = $form->getParams();
        if ($values) {

            $idVolume = null;
            if (isset($values['volumes']) && ($values['volumes'] != "")) {
                $idVolume = $values['volumes'];
            }
            $result = $expedicaoRepo->getEtiquetasConferidasByVolume($idExpedicao,$idVolume);
            if (isset($values['exportarpdf'])) {
                unset($values);
                $form->getElement('exportarpdf')->setValue(null);
                $RelProdutosConferidos = new \Wms\Module\Expedicao\Report\ProdutosConferidos("L");
                $RelProdutosConferidos->init($result);
            }
            $Grid = new \Wms\Module\Web\Grid\Expedicao\OsProdutosConferidos();
            $this->view->grid = $Grid->init($result,true)->render();
        }

        $this->view->form = $form;
    }

    public function viewAction () {
        $idExpedicao = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $result = $expedicaoRepo->getEtiquetasConferidasByVolume($idExpedicao, null);
        $this->exportCSV($result,"produtos-conferidos");
    }

}