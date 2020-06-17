<?php
use Wms\Module\Web\Controller\Action,
    Wms\Util\Coletor as ColetorUtil,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta;

class Expedicao_ReimpressaoFaixaController  extends Action
{
    public function indexAction() {
        $codBarrasInicial = $this->getRequest()->getParam('codBarrasInicial');
        $codBarrasFinal = $this->getRequest()->getParam('codBarrasFinal');

        $motivo = $this->view->codBarras = $this->getRequest()->getParam('motivo');
        $senha = $this->view->codBarras = $this->getRequest()->getParam('senha');
        $modelo = $this->getSystemParameterValue('MODELO_ETIQUETA_SEPARACAO');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $this->view->codBarrasInicial = $codBarrasInicial;
        $this->view->codBarrasFinal = $codBarrasFinal;

        if ($modelo == '1') {
            $Etiqueta = new Etiqueta("L", 'mm', array(110, 40));
        } elseif ($modelo == '18') {
            $Etiqueta = new Etiqueta("L", 'mm', array(100, 35));
        } else {
            $Etiqueta = new Etiqueta("L", 'mm', array(110, 60));
        }

        if (($codBarrasInicial != NULL) && ($codBarrasFinal != NULL)) {
            $codBarrasInicial = ColetorUtil::retiraDigitoIdentificador($codBarrasInicial);
            $codBarrasFinal = ColetorUtil::retiraDigitoIdentificador($codBarrasFinal);
            if ($EtiquetaRepo->checkAutorizacao($senha)) {
                $etiquetas = $EtiquetaRepo->getEtiquetasReimpressaoByFaixa($codBarrasInicial,$codBarrasFinal);
                if (count($etiquetas) >0) {
                    try {
                        foreach ($etiquetas as $etiqueta) {
                            if ($etiqueta['codStatus'] == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CORTADO) {
                                throw new \Exception('Existem etiquetas cortadas na faixa de etiquetas selecionada (' . $etiqueta['codBarras'] . ')');
                            }
                        }

                        $Etiqueta->reimprimirFaixa  ($etiquetas, $motivo, $modelo);
                    } catch(\Exception $e) {
                        $msg = "Falha na reimpressao. Motivo:" . $e->getMessage();
                        $this->addFlashMessage('error',$msg);
                        $this->redirect('index','reimpressao-faixa','expedicao');
                    }
                } else{
                    $this->addFlashMessage('error',"Nenhuma etiqueta para imprimir encontrada nessa faixa ($codBarrasInicial a $codBarrasFinal)");
                    $this->redirect('index','reimpressao-faixa','expedicao');
                }
            } else {
                $this->addFlashMessage('error',"Senha incorreta");
                $this->redirect('index','reimpressao-faixa','expedicao');
            }
        }else {
            $this->addFlashMessage('info',"Preencha corretamente todas as informações");
        }
    }
}