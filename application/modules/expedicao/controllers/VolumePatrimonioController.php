<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao\VolumePatrimonio as VolumesGrid,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Page,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta;

class Expedicao_VolumePatrimonioController  extends  Crud
{
    protected $entityName = 'Expedicao\VolumePatrimonio';

    public function indexAction()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Imprimir Relatório',
                    'cssClass' => '',
                    'urlParams' => array(
                        'action' => 'imprimir-relatorio'
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Adicionar novo',
                    'cssClass' => 'btnAdd',
                    'urlParams' => array(
                        'action' => 'add'
                    ),
                    'tag' => 'a'
                )
            )
        ));

        $form = new Wms\Module\Expedicao\Form\VolumePatrimonioFiltro();
        $form->setAttrib('class', 'filtro')->setAttrib('method', 'post');
        $form->init('Buscar','Busca',false, true);

        if ($values = $form->getParams()) {
            /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumeRepo */
            $volumeRepo   = $this->em->getRepository('wms:Expedicao\VolumePatrimonio');
            $codigoInicial = $values['identificacao']['inicialCodigo'];
            $codigoFinal = $values['identificacao']['finalCodigo'];
            $descricao = $values['identificacao']['descricao'];

            if (isset($values['identificacao']['imprimir'])) {
                $volumeRepo->imprimirFaixa($codigoInicial,$codigoFinal);
            } else {
                $volumes = $volumeRepo->getVolumes($codigoInicial,$codigoFinal,$descricao,true);
                $Grid = new VolumesGrid();
                $this->view->grid = $Grid->init($volumes) ->render();
            }
        }

        $this->view->form = $form;
    }

    public function imprimirAction () {
        $id = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumeRepo */
        $volumeRepo   = $this->em->getRepository('wms:Expedicao\VolumePatrimonio');
        $volumeRepo->imprimirFaixa($id,$id);
    }

    public function deleteAction()
    {
        try{
            $id = $this->_getParam('id');
            $volumeRepo = $this->em->getRepository('wms:Expedicao\VolumePatrimonio');
            $volumeEn   = $volumeRepo->findOneBy(array('id'=>$id));

            $this->getEntityManager()->remove($volumeEn);
            $this->getEntityManager()->flush();
            $this->addFlashMessage('success', 'Volume excluido com sucesso' );
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage() );
        }
        $this->_redirect('/expedicao/volume-patrimonio');
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
            )
        ));
        $form = new Wms\Module\Expedicao\Form\VolumePatrimonioFiltro();
        $form->init('Verificar Disponibilidade','Adicionar Novo');
        $form->setAttrib('class', 'filtro')->setAttrib('method', 'post');

        /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumeRepo */
        $volumeRepo   = $this->em->getRepository('wms:Expedicao\VolumePatrimonio');

        if ($values = $form->getParams()) {
            $codigoInicial = $values['identificacao']['inicialCodigo'];
            $codigoFinal = $values['identificacao']['finalCodigo'];
            $descricao = $values['identificacao']['descricao'];

            if ((($codigoInicial == "") OR ($codigoFinal == "")) OR ($codigoFinal < $codigoInicial)){
                $this->addFlashMessage('error', 'Preencha corretamente os campos código inicial e final');
                $this->_redirect('/expedicao/volume-patrimonio/add');
            }

            if (!isset($values['identificacao']['salvar'])) {
                $volumes = $volumeRepo->getVolumes($codigoInicial,$codigoFinal,"",true);

                if (count($volumes) == 0) {
                    $this->view->msg = "Não existe nenhum volume patrimonio neste intervalo filtrado";
                } else {
                    $this->view->msg = "Os seguintes volumes patrimonios abaixo serão substituidos";
                    $Grid = new VolumesGrid();
                    $this->view->grid = $Grid->init($volumes, false) ->render();
                }

                $form->init('Verificar Disponibilidade','Adicionar Novo', true);
                $form->setDefaultsFromValue($codigoInicial,$codigoFinal,$descricao);
            } else {
                $volumeRepo->salvarSequencia($codigoInicial,$codigoFinal,$descricao);
                $this->addFlashMessage('success', 'Volumes adicionados com sucesso');
                $this->_redirect('/expedicao/volume-patrimonio');
            }
        }

        $this->view->form = $form;

    }

    public function desfazerAction()
    {
        $idVolume = $this->_getParam('id');
        try {
            /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
            $expVolumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
            /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumePatrimonioRepo */
            $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
            $expedicao = $volumePatrimonioRepo->getExpedicaoByVolume($idVolume,'array');
            $idExpedicao = $expedicao[0]['expedicao'];
            $expVolumePatrimonioRepo->desocuparVolume($idVolume,$idExpedicao);
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            $this->_redirect('/expedicao/volume-patrimonio');
        }

        $this->addFlashMessage('success', 'Volumes desocupado com sucesso');
        $this->_redirect('/expedicao/volume-patrimonio');
    }

    public function imprimirRelatorioAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumePatrimonioRepository */
        $volumePatrimonioRepository = $this->em->getRepository("wms:Expedicao\VolumePatrimonio");
        $getRelatorio = $volumePatrimonioRepository->imprimirRelatorio();
        $this->exportPDF($getRelatorio,'imprimir-relatorio','Caixas Expedidas','P');
    }

    public function imprimirVolumePatrimonioAction()
    {
        $idExpedicao = $this->_getParam('id');
        $params = $this->_getAllParams();
        $parametroEtiquetaVolume = $this->getSystemParameterValue('MODELO_ETIQUETA_VOLUME');

        /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumePatrimonioRepository */
        $volumePatrimonioRepository = $this->em->getRepository("wms:Expedicao\VolumePatrimonio");
        $volumePatrimonio = $volumePatrimonioRepository->getVolumesByExpedicao($idExpedicao);
        $this->view->volumesPatrimonio = $volumePatrimonio;
        $this->idExpedicao = $idExpedicao;
        
        /** @var Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
        $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');

        foreach ($volumePatrimonio as $key => $volume) {

            $produtos = $expVolumePatrimonioRepo->getProdutosVolumeByMapa($idExpedicao, $volumePatrimonio[$key]['volume']);
            if (empty($produtos)){
                //unset($volumePatrimonio[$key]);
                //continue;
                $volumePatrimonio[$key]['produtos'] = null;
                $volumePatrimonio[$key]['sequencia'] = 0;
            } else {
                $volumePatrimonio[$key]['produtos'] = $produtos;
                $volumePatrimonio[$key]['sequencia'] = $produtos[0]['sequencia'];
            }

        }

        if (isset($params['btnImprimir'])) {
            //exit;
            switch ($parametroEtiquetaVolume) {
                case 1:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 50));
                    $gerarEtiqueta->imprimirExpedicaoModelo1($volumePatrimonio);
                    break;
                case 2:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 62,5));
                    $gerarEtiqueta->imprimirExpedicaoModelo2($volumePatrimonio);
                    break;
                case 3:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 75));
                    $gerarEtiqueta->imprimirExpedicaoModelo3($volumePatrimonio);
                    break;
            }
        }
    }

}