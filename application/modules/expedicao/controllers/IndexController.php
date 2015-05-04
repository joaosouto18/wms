<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao as ExpedicaoGrid,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria,
    Wms\Module\Web\Grid\Expedicao\PesoCargas as PesoCargasGrid;

class Expedicao_IndexController  extends Action
{
    public function indexAction()
    {
        $form = new FiltroExpedicaoMercadoria();
        $this->view->form = $form;
        $params = $this->_getAllParams();

        $s1 = new Zend_Session_Namespace('sessionAction');
        $s1->setExpirationSeconds(900, 'action');
        $s1->action=$params;

        $s = new Zend_Session_Namespace('sessionUrl');
        $s->setExpirationSeconds(900, 'url');
        $s->url=$params;

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        $dataI1 = new \DateTime;

        if ( !empty($params) ) {

            if ( !empty($params['idExpedicao']) ||  !empty($params['codCargaExterno']) ){
                $idExpedicao=null;
                $idCarga=null;

                if (!empty($params['idExpedicao']) )
                    $idExpedicao=$params['idExpedicao'];


                if (!empty($params['codCargaExterno']) )
                    $idCarga=$params['codCargaExterno'];

                $params=array();
                $params['idExpedicao']=$idExpedicao;
                $params['codCargaExterno']=$idCarga;
            } else {
                if ( empty($params['dataInicial1']) ){
                    $params['dataInicial1']=$dataI1->format('d/m/Y');
                }
            }
            if ( !empty($params['control']) )
                $this->view->control = $params['control'];


            unset($params['control']);

        } else {
            $dataI1 = new \DateTime;
            $dataI2 = new \DateTime;
//            $dataI1->sub(new DateInterval('P01D'));

            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI2->format('d/m/Y')
            );
            unset($params['control']);
        }

        $form->populate($params);

        $Grid = new ExpedicaoGrid();
        $this->view->grid = $Grid->init($params)
            ->render();

        $this->view->refresh = true;
    }

    public function agruparcargasAction()
    {
        $id = $this->_getParam('id');
        $this->view->id = $id;

        if ( $this->getRequest()->getParam('idExpedicaoNova')!='' ){
            try {
                $idNova = $this->getRequest()->getParam('idExpedicaoNova');

                if ($idNova == null)
                    throw new \Exception('Você precisa informar a nova Expedição');

                if ($this->getRequest()->isPost() ) {

                    $idAntiga = $this->getRequest()->getParam('idExpedicao');

                    /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
                    $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
                    /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
                    $AndamentoRepo   = $this->_em->getRepository('wms:Expedicao\Andamento');

                    $novaExpedicaoEn = $this->_em->getReference('wms:Expedicao', $idNova);
                    $antigaExpedicaoEn = $this->_em->getReference('wms:Expedicao', $idAntiga);

                    $cargas=$ExpedicaoRepo->getCargas($idAntiga);

                    foreach ($cargas as $c){
                        $codCarga=$c->getId();
                        $entityCarga = $this->_em->getReference('wms:Expedicao\Carga', $codCarga);
                        $entityCarga->setExpedicao($novaExpedicaoEn);
                        $this->_em->persist($entityCarga);
                        $AndamentoRepo->save("Carga ". $c->getCodCargaExterno(). " transferida pelo agrupamento de cargas", $idNova);
                    }
                    $this->_em->flush();
                    $this->_helper->messenger('success', 'Cargas migradas para a expedição '.$idNova.' com sucesso.');
                    return $this->redirect('index');
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
        }
    }

    public function consultarpesoAction()
    {
        $id = $this->_getParam('id');

        $parametros['id']=$id;
        $parametros['agrup']='carga';

        $GridPeso = new PesoCargasGrid();
        $this->view->gridPeso = $GridPeso->init($parametros)
            ->render();

        $parametros['agrup']='expedicao';
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $pesos=$ExpedicaoRepo->getPesos($parametros);

        $this->view->totalExpedicao=$pesos;
    }

    public function desagruparcargaAction () {

        $idCarga = $this->_getParam('COD_CARGA');

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
        $AndamentoRepo   = $this->_em->getRepository('wms:Expedicao\Andamento');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo      = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo      = $this->_em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $CargaRepo */
        $CargaRepo      = $this->_em->getRepository('wms:Expedicao\Carga');

        try {
            /** @var \Wms\Domain\Entity\Expedicao\Carga $cargaEn */
            $cargaEn = $CargaRepo->findOneBy(array('id'=>$idCarga));

            $countCortadas = $EtiquetaRepo->countByStatus(Expedicao\EtiquetaSeparacao::STATUS_CORTADO, $cargaEn->getExpedicao() ,null,null,$idCarga);
            $countTotal = $EtiquetaRepo->countByStatus(null, $cargaEn->getExpedicao(),null,null,$idCarga);

            if ($countTotal != $countCortadas) {
                throw new \Exception('A Carga '. $cargaEn->getCodCargaExterno(). ' possui etiquetas que não foram cortadas e não pode ser removida da expedição');
            }

            $cargas=$ExpedicaoRepo->getCargas($cargaEn->getCodExpedicao());
            if (count($cargas) <= 1) {
                throw new \Exception('A Expedição não pode ficar sem cargas');
            }
            $AndamentoRepo->save("Carga " . $cargaEn->getCodCargaExterno() . " retirada da expedição atraves do desagrupamento de cargas", $cargaEn->getCodExpedicao());
            $expedicaoAntiga = $cargaEn->getCodExpedicao();
            $expedicaoEn = $ExpedicaoRepo->save($cargaEn->getCodCargaExterno());
            $cargaEn->setExpedicao($expedicaoEn);
            $cargaEn->setSequencia(1);
            $this->_em->persist($cargaEn);

            if ($countCortadas >0) {
                $expedicaoEn->setStatus(EXPEDICAO::STATUS_CANCELADO);
                $this->_em->persist($expedicaoEn);
                $AndamentoRepo->save("Etiquetas da carga " . $cargaEn->getCodCargaExterno() . " canceladas na expedição " . $expedicaoAntiga, $expedicaoEn->getId());
            }

            $this->_em->flush();
            $this->_helper->messenger('Foi criado uma nova expedição código ' . $expedicaoEn->getId() . " com a carga selecionada");
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect("index",'index','expedicao');
    }

    public function semEstoqueReportAction(){
        $idExpedicao = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getProdutosSemEstoqueByExpedicao($idExpedicao);
        $this->exportPDF($result,'semEstoque.pdf','Produtos sem estoque na expedição','L');
    }

    public function imprimirAction(){
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getProdutosSemEstoqueByExpedicao($idExpedicao);
        $this->exportPDF($result,'semEstoque.pdf','Produtos sem estoque na expedição','L');
    }

}