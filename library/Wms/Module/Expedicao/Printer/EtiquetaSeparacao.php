<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;

class EtiquetaSeparacao extends Pdf
{
    private $total;
    private $strReimpressao;
    private $modelo;
    private $etqMae;
    protected $chaveCargas;

    public function Footer()
    {
        if ($this->etqMae ==false) {

            switch($this->modelo) {
                case 2:
                        // font
                        $this->SetFont('Arial','B',7);
                        //Go to 1.5 cm from bottom
                        $this->SetY(-22);
                        $this->Cell(20, 3, "", 0, 1, "L");
                        $this->Cell(20, 3, "", 0, 1, "L");
                        $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                break;
                case 4:
                case 3:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY(-22);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;
                case 6:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY(-22);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;

            }
        }
    }

    protected  function setChaveCargas($idExpedicao)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo   = $em->getRepository('wms:Expedicao');
        $cargas = $expedicaoRepo->getCodCargasExterno($idExpedicao);
        $chaveCarga = array();
        foreach ($cargas as $key => $carga) {
            $chaveCarga[$carga['codCargaExterno']] = $key+1;
        }
        $this->chaveCargas = $chaveCarga;
    }

    public function imprimirReentrega($idExpedicao, $modelo){

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $status = \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA;
        
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $pendencias = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao, $status);
        $idEtiqueta = array();
        foreach ($pendencias as $pendencia) {
            $idEtiqueta[] = $pendencia['ETIQUETA'];
        }
        $idEtiqueta = implode(",",$idEtiqueta);
        $etiquetas      = $EtiquetaRepo->getEtiquetasByExpedicao(null, null, null, $idEtiqueta);

        foreach($etiquetas as $etiqueta) {
            $this->etqMae = false;
            $this->layoutEtiqueta($etiqueta, count($etiquetas), false, $modelo, true);
        }

        $this->Output('Etiquetas-reentrega-'.$idExpedicao.'-'.'.pdf','D');

    }

    public function imprimir(array $params = array(), $modelo)
    {
        $this->modelo = $modelo;
        $this->total= "";

        $idExpedicao            = $params['idExpedicao'];
        $centralEntregaPedido   = $params['central'];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetas      = $EtiquetaRepo->getEtiquetasByExpedicao($idExpedicao, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $centralEntregaPedido);

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->setChaveCargas($idExpedicao);

        $etiquetaMaeAnterior = 0;

        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => 'MODELO_SEPARACAO_PADRAO'));
        $idModeloSeparacao = $parametro->getValor();

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $em->getRepository("wms:Expedicao\ModeloSeparacao");

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = $modeloSeparacaoRepo->find($idModeloSeparacao);

        foreach($etiquetas as $etiqueta) {
            if ($modeloSeparacaoEn->getUtilizaEtiquetaMae() == 'S') {
                if ($etiquetaMaeAnterior != $etiqueta['codEtiquetaMae']) {
                    $this->etqMae = true;
                    $this->layoutEtiquetaMae($etiqueta['codEtiquetaMae']);
                    $etiquetaMaeAnterior = $etiqueta['codEtiquetaMae'];
                }
            }
            $this->etqMae = false;
            $this->layoutEtiqueta($etiqueta,count($etiquetas),false,$modelo);
        }
        $this->Output('Etiquetas-expedicao-'.$idExpedicao.'-'.$centralEntregaPedido.'.pdf','D');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo      = $em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
        $ExpedicaoEntity    = $ExpedicaoRepo->find($idExpedicao);
        $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
        $ExpedicaoEntity->setStatus($statusEntity);
        $em->persist($ExpedicaoEntity);

        foreach($etiquetas as $etiqueta) {
            try {
                $EtiquetaRepo->efetivaImpressao($etiqueta['codBarras'], $centralEntregaPedido);
            } catch(Exception $e) {
                echo $e->getMessage();
            }
        }

        $em->flush();
        $em->clear();
    }

    public function reimprimirFaixa($etiquetas,$motivo, $modelo){
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        foreach($etiquetas as $etiqueta) {
            $this->layoutEtiqueta($etiqueta,count($etiquetas),true, $modelo);
        }

        foreach($etiquetas as $etiqueta) {
            try {
                $etiquetaEntity = $EtiquetaRepo->find($etiqueta['codBarras']);
                $etiquetaEntity->setReimpressao($motivo);
                $em->persist($etiquetaEntity);

                if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoEmbalagem()->getCodigoBarras();
                } else {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoVolume()->getCodigoBarras();
                }

                $andamentoRepo  = $em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save('Reimpressão da etiqueta:'.$etiqueta['codBarras'], $etiqueta['codExpedicao'], false, true,$etiqueta['codBarras'], $codBarrasProdutos);

            } catch(Exception $e) {
                echo $e->getMessage();
            }
        }

        $em->flush();
        $em->clear();

        $this->Output('ReimpressaoEtiqueta.pdf','D');

    }

    public function reimprimir($etiquetaEntity, $motivo, $modelo) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiqueta      = $EtiquetaRepo->getEtiquetaById($etiquetaEntity->getId());

        $this->layoutEtiqueta($etiqueta,1,true, $modelo);

        $this->Output('etiqueta-'.$etiquetaEntity->getId().'.pdf','D');

        $etiquetaEntity->setReimpressao($motivo);
        $em->persist($etiquetaEntity);
        $em->flush();
    }

    public function jaImpressas($ExpedicaoEn) {

        $em =  \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $qtdImpressasPendentes = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $ExpedicaoEn);

        if ($qtdImpressasPendentes == 0) {
            return false;
        }

        return true;
    }

    public function jaReimpressa($etiquetaEntity) {
        if ($etiquetaEntity->getReimpressao() != null) {
            return true;
        }
        return false;
    }

    protected function layoutModelo1($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}
		
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;
        $this->SetFont('Arial', 'B', 8);

        switch ( $etiqueta['tipoCarga'] ) {
            case 'TRANSBORDO' :

                $this->SetFont('Arial', 'B', 8);
                $impressao  = utf8_decode("Exp:$etiqueta[codExpedicao] - Placa:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $impressao .= "$etiqueta[itinerario] \n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[linhaEntrega]"),0,50) . " \n";
                $impressao .= "$etiqueta[codProduto] - $etiqueta[produto] - $etiqueta[grade] \n";
                $impressao .= substr("$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - Fornecedor:$etiqueta[fornecedor]",0,50) . " \n";
                $impressao .= utf8_decode("$etiqueta[tipoComercializacao] - $etiqueta[endereco]\n");
                $this->MultiCell(100, 2.7, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 55, null, 50);
                }
                // font
                $this->SetFont('Arial','B',17);
                //Go to 1.5 cm from bottom
                $this->SetY(16.5);
                $this->Cell(20, 3, $etiqueta['sequencia'], 0, 1, "L");

                break;
            default:
                $impressao  = utf8_decode("Exp:$etiqueta[codExpedicao] - Placa:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno] - $etiqueta[tipoPedido]:$etiqueta[codEntrega] \n");
                $impressao .= "$etiqueta[itinerario] \n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,50). " \n";
                $impressao .= "$etiqueta[codProduto] - $etiqueta[produto] - $etiqueta[grade] \n";
                $impressao .= substr("$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - Fornecedor:$etiqueta[fornecedor] ",0,50) . " \n";
                $impressao .= utf8_decode("$etiqueta[tipoComercializacao] - $etiqueta[endereco]\n");
                $this->MultiCell(100, 2.7, $impressao, 0, 'L');
                if($reentrega == false) {
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 55, null, 50);
                }
                // font
                $this->SetFont('Arial','B',17);
                //Go to 1.5 cm from bottom
                $this->SetY(16.5);
                $this->Cell(20, 3, $etiqueta['sequencia'], 0, 1, "L");
                break;
        }
    }

    protected function layoutModelo2($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 11);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;
        $this->SetFont('Arial', 'B', 11);

        switch ( $etiqueta['tipoCarga'] ) {

            case 'TRANSBORDO' :
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $impressao .= substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
                $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
                $impressao .= substr(trim($etiqueta['produto']),0,40)."\n";
                $impressao .= substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $impressao .= utf8_decode("$etiqueta[endereco]\n");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                }
                break;

            default:
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $impressao .= substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
                $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
                $impressao .= substr(trim($etiqueta['produto']),0,40)."\n";
                $impressao .= substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $impressao .= utf8_decode("$etiqueta[endereco]\n");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                }
                break;
        }
    }

    protected function layoutModelo3($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;

        if ($etiqueta['tipoCarga'] == 'TRANSBORDO') {
            $etiqueta['tipoCarga'] = 'TRANSB.';
        }

        $this->SetFont('Arial', 'B', 11);
        $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $impressao  = substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 7);
        $impressao = substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,50) . "\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 13);
        $impressao = "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 13);

        $tamanhoSringProduto = strlen($etiqueta['produto']);
        if ($tamanhoSringProduto >= 35) {
            $this->SetFont('Arial', 'B', 11);
        } else {
            $this->SetFont('Arial', 'B', 13);
        }
        $impressao = substr(trim($etiqueta['produto']),0,70)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
        $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 11);
        $impressao = utf8_decode("$etiqueta[endereco]\n");
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        if ($reentrega == false) {
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
        }
        if ($reimpressao == true) {
            $this->SetFont('Arial','B',20);
            $this->SetY(34);
        }else {
            $this->SetFont('Arial','B',30);
            $this->SetY(36);
        }
        if (!isset($etiqueta['sequencia'])) $etiqueta['sequencia'] = "";
        $this->Cell(20, 3,  $etiqueta['sequencia'], 0, 1, "L");
    }

    protected function layoutModelo4($etiqueta,$countEtiquetas,$reimpressao,$modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $yImage = 33;

        $this->total = $countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;
        $this->SetFont('Arial', 'B', 9);

        $qtdEmbalagem = "";
        if (!is_null($etiqueta['codProdutoEmbalagem'])) {
            $qtdEmbalagem = "(". $etiqueta['qtdProduto'] . ")";
        }

        $this->SetFont('Arial', 'B', 18);
        $impressao  = utf8_decode("\n\nPEDIDO:$etiqueta[pedido]\n");
        $this->MultiCell(100, 1.7, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 18);
        if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 35) {
            $this->SetFont('Arial', 'B', 11);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 30) {
            $this->SetFont('Arial', 'B', 13);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 20) {
            $this->SetFont('Arial', 'B', 15);
        } else {
            $this->SetFont('Arial', 'B', 18);
        }
        $impressao = substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $this->MultiCell(100, 10, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = "CODIGO:$etiqueta[codProduto] - EXP:$etiqueta[codExpedicao] \n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(trim($etiqueta['produto']),0,70)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        if ($reentrega == false) {
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, $yImage, 68,17);
        }
        $this->Image(APPLICATION_PATH . '/../public/img/premium-etiqueta.gif', 90, 1.5, 20, 5);
        $this->SetFont('Arial', 'B', 13);
        $this->SetY(36);
        $this->Cell(20, 3,   utf8_decode($etiqueta['tipoComercializacao']). $qtdEmbalagem, 0, 1, "L");
    }

    protected function layoutModelo5($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;

        if ($etiqueta['tipoCarga'] == 'TRANSBORDO') {
            $etiqueta['tipoCarga'] = 'TRANSB.';
        }

        $this->SetFont('Arial', 'B', 10);
        $impressao  = utf8_decode("EXP: $etiqueta[codExpedicao] - $etiqueta[placaExpedicao] - $etiqueta[tipoCarga]: $etiqueta[codCargaExterno]\n");
        $this->MultiCell(100, 4, $impressao, 0, 'L');
        $impressao  = substr(utf8_decode("$etiqueta[cliente]"),0,40)."\n";
        $this->MultiCell(100, 4, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $impressao = "ENTREGA: $etiqueta[codEntrega]" . " - PRODUTO: $etiqueta[codProduto]" . "\n";
        $this->MultiCell(100, 4, $impressao, 0, 'L');
        $this->MultiCell(100, 1.5, "", 0, 'L');

        $tamanhoSringProduto = strlen($etiqueta['produto']);
        if ($tamanhoSringProduto >= 35) {
            $this->SetFont('Arial', 'B', 11);
        } else {
            $this->SetFont('Arial', 'B', 15);
        }
        $impressao = substr(trim($etiqueta['produto']),0,70)."\n";
        $this->MultiCell(100, 4, $impressao, 0, 'L');

        $this->SetFont('Arial', 'B', 10);
        $impressao = "FORNECEDOR: " . substr(utf8_decode("$etiqueta[fornecedor]"),0,40);
        $this->MultiCell(100, 4, $impressao, 0, 'L');

        $this->SetFont('Arial', 'B', 10);
        $this->MultiCell(100, 1.5, "", 0, 'L');
        $impressao = utf8_decode($etiqueta['tipoComercializacao'])."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $impressao = utf8_decode("$etiqueta[endereco]\n");
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        if ($reentrega == false) {
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
        }
        if ($reimpressao == true) {
            $this->SetFont('Arial','B',20);
            $this->SetY(34);
        }else {
            $this->SetFont('Arial','B',30);
            $this->SetY(36);
        }
        //$this->Cell(20, 3,  $etiqueta['sequencia'], 0, 1, "L");
    }
    
    protected function layoutEtiqueta($etiqueta,$countEtiquetas,$reimpressao = false, $modelo, $reentrega = false)
    {
        switch ($modelo) {
            case 6:
                $this->layoutModelo6($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 5:
                $this->layoutModelo5($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 4:
                $this->layoutModelo4($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 3:
                $this->layoutModelo3($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 2:
                $this->layoutModelo2($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            default:
                $this->layoutModelo1($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
        }
    }

    protected function layoutEtiquetaMae($codEtiquetaMae)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $this->etqMae= true;
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 11);

        $this->AddPage();
        $this->total="";
        $this->strReimpressao = "";
        $this->SetFont('Arial', 'B', 11);

        $etiquetaMae = $em->getRepository("wms:Expedicao\EtiquetaMae")->find($codEtiquetaMae);

        $this->Cell(20, 5, utf8_decode('Etiqueta Mãe - ' . $codEtiquetaMae), 0, 1, "L");
        $this->Cell(20, 5, utf8_decode('Expedição:' . $etiquetaMae->getExpedicao()->getId()), 0, 1, "L");
        $this->Cell(20, 5, utf8_decode('Quebras:' . $etiquetaMae->getDscQuebra()), 0, 1, "L");

        $this->Image(@CodigoBarras::gerarNovo($codEtiquetaMae), 25, 30, 60);
    }

    protected function layoutModelo6($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;
        $this->SetFont('Arial', 'B', 9);

        switch ( $etiqueta['tipoCarga'] ) {

            case 'TRANSBORDO' :
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $impressao .= substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
                $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
                $impressao .= substr(trim($etiqueta['produto']),0,40)."\n";
                $impressao .= substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $impressao .= utf8_decode("$etiqueta[endereco]\n");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                }
                break;

            default:
                $this->SetFont('Arial', 'B', 11);
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 9);
                $impressao = substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
                $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 11);
                $impressao = substr(trim($etiqueta['produto']),0,70)."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 9);
                $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 11);
                $impressao = utf8_decode("$etiqueta[endereco]\n");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                }
                break;
        }
    }


}
