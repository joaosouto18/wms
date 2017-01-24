<?php
use Wms\Module\Web\Controller\Action,
    Wms\Util\Coletor as ColetorUtil;


class Expedicao_CorteFaixaController  extends Action
{
    public function indexAction()
    {
        ini_set('max_execution_time', 3000);

        $codBarrasInicial = $this->getRequest()->getParam('codBarrasInicial');
        $codBarrasFinal = $this->getRequest()->getParam('codBarrasFinal');
        $senha = $this->view->codBarras = $this->getRequest()->getParam('senha');

        $codBarrasInicial = ColetorUtil::retiraDigitoIdentificador($codBarrasInicial);
        $codBarrasFinal = ColetorUtil::retiraDigitoIdentificador($codBarrasFinal);

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
                            if ($etiquetaEn->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CORTADO) {
                                $this->addFlashMessage('error',"Etiqueta já cortada!");
                                $this->redirect('index','corte-faixa','expedicao');
                            }

                            $EtiquetaRepo->cortar($etiquetaEn, true);

                            if ($etiquetaEn->getProdutoEmbalagem() != NULL) {
                                $codBarrasProdutos = $etiquetaEn->getProdutoEmbalagem()->getCodigoBarras();
                            } else {
                                $codBarrasProdutos = $etiquetaEn->getProdutoVolume()->getCodigoBarras();
                            }

                            if ($etiquetaInicial == "") $etiquetaInicial = $etiquetaEn->getId();
                            $etiquetaFinal = $etiquetaEn->getId();
                            $expedicaoId = $etiquetaEn->getPedido()->getCarga()->getExpedicao()->getId();
                            $andamentoRepo->save('Etiqueta '. $etiquetaEn->getId() .' cortada', $expedicaoId, false, true, $etiquetaEn->getId(),$codBarrasProdutos);
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