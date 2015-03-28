<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Expedicao_AgruparCargasController  extends Action
{
    public function indexAction()
    {
        $id = $this->_getParam('id');
        $this->view->idExpedicao = $id;
        $params = array(
            'dataInicial1' => '',
            'dataInicial2' => '',
            'submit' => 'Buscar',
            'idExpedicao' => '',
            'dataFinal1' => '',
            'dataFinal2' =>  '',
            'status' => '462',
            'centrais'=>'',
            'codCargaExterno' => '',
            'placa' => ''
        );

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $expedicoes = $ExpedicaoRepo->buscar($params);

        $this->view->expedicoes = $expedicoes;
    }

    public function agruparAction() {
        $idExpedicaoMae = $this->_getParam('id');
        $expedicoes = $this->_getParam('expedicao');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
        $AndamentoRepo   = $this->_em->getRepository('wms:Expedicao\Andamento');
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepo */
        $cargaRepo   = $this->_em->getRepository('wms:Expedicao\Carga');
        $ultimaCarga = $cargaRepo->getSequenciaUltimaCarga($idExpedicaoMae);
        $sequenciaUltimaCarga = $ultimaCarga[0]['sequencia'];
        try {
            $statusCancelado = $this->_em->getRepository("wms:Util\Sigla")->findOneBy(array('id'=>\Wms\Domain\Entity\Expedicao::STATUS_CANCELADO));
            foreach ($expedicoes as $idExpedicaoFilha) {
                $expedicaoMaeEn = $this->_em->getReference('wms:Expedicao', $idExpedicaoMae);
                $expedicaoFilhaEN = $this->_em->getRepository("wms:Expedicao")->findOneBy(array('id'=>$idExpedicaoFilha));
                $cargas = $ExpedicaoRepo->getCargas($idExpedicaoFilha);
                foreach ($cargas as $c) {
                    $sequenciaUltimaCarga = $sequenciaUltimaCarga + 1;
                    $codCarga = $c->getId();
                    $entityCarga = $this->_em->getReference('wms:Expedicao\Carga', $codCarga);
                    $entityCarga->setExpedicao($expedicaoMaeEn);
                    $entityCarga->setSequencia($sequenciaUltimaCarga);
                    $this->_em->persist($entityCarga);
                    $AndamentoRepo->save("Carga ". $c->getCodCargaExterno(). " transferida da expedição $idExpedicaoFilha pelo agrupamento de cargas", $idExpedicaoMae);
                }

                $expedicaoFilhaEN->setStatus($statusCancelado);
                $this->_em->persist($expedicaoFilhaEN);
                $AndamentoRepo->save("Expedição cancelada devido a transferida de suas cargas para a expedição " . $idExpedicaoMae, $idExpedicaoFilha);
           }
            $this->_em->flush();
            $this->_helper->messenger('success', 'Cargas migradas para a expedição '.$idExpedicaoMae.' com sucesso.');
        }
        catch (\Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }
        $this->redirect("index",'index','expedicao');
    }

}