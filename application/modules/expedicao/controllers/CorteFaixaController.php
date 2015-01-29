<?php
use Wms\Module\Web\Controller\Action,
    Wms\Service\Recebimento as LeituraColetor;

class Expedicao_CorteFaixaController  extends Action
{
    public function indexAction() {
        $LeituraColetor = new LeituraColetor();

        $codBarrasInicial = $this->getRequest()->getParam('codBarrasInicial');
        $codBarrasFinal = $this->getRequest()->getParam('codBarrasFinal');
        $senha = $this->view->codBarras = $this->getRequest()->getParam('senha');

        $codBarrasInicial = $LeituraColetor->retiraDigitoIdentificador($codBarrasInicial);
        $codBarrasFinal = $LeituraColetor->retiraDigitoIdentificador($codBarrasFinal);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $this->view->codBarrasInicial = $codBarrasInicial;
        $this->view->codBarrasFinal = $codBarrasFinal;

        if (($codBarrasInicial != NULL) && ($codBarrasFinal != NULL)) {
            if ($EtiquetaRepo->checkAutorizacao($senha)) {
                $etiquetas = $EtiquetaRepo->getEtiquetasByFaixa($codBarrasInicial,$codBarrasFinal);
                if (count($etiquetas) >0) {
                    $etiquetaInicial = "";
                    $etiquetaFinal = "";
                    $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
                    /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetaEn */
                    try {
                        foreach ($etiquetas as $etiquetaEn) {
                            $EtiquetaRepo->cortar($etiquetaEn);
                            if ($etiquetaInicial == "") $etiquetaInicial = $etiquetaEn->getId();
                            $etiquetaFinal = $etiquetaEn->getId();
                            $expedicaoId = $etiquetaEn->getPedido()->getCarga()->getExpedicao()->getId();
                            $andamentoRepo->save('Etiqueta '. $etiquetaEn->getId() .' cortada', $expedicaoId);
                        }
                        $this->addFlashMessage('success',"Etiquetas cortadas com sucesso");
                        $this->redirect('index','corte-faixa','expedicao');
                    } catch(\Exception $e) {
                        if ($etiquetaInicial == "") {
                            $msg = "Nenhuma etiqueta foi cortada. Motivo:" . $e->getMessage();
                        } else {
                            if ($etiquetaFinal == $etiquetaInicial) {
                                $msg = "Somente as etiquetas ".$etiquetaInicial." foi cortada. Motivo: " . $e->getMessage();
                            } else {
                                $msg = "Somente as etiquetas da faixa ".$etiquetaInicial." a ".$etiquetaFinal." foram Cortadas. Motivo: ".$e->getMessage();
                            }
                        }
                        $this->addFlashMessage('error',$msg);
                        $this->redirect('index','corte-faixa','expedicao');
                    }
                } else{
                    $this->addFlashMessage('error',"Nenhuma etiqueta encontrada nessa faixa ($codBarrasInicial a $codBarrasFinal)");
                    $this->redirect('index','corte-faixa','expedicao');
                }
            } else {
                $this->addFlashMessage('error',"Senha incorreta");
                $this->redirect('index','corte-faixa','expedicao');
            }
        }else {
            $this->addFlashMessage('info',"Preencha corretamente todas as informações");
        }
    }
}