<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao;

class EtiquetaSeparacao extends Pdf
{
    private $total;
    private $strReimpressao;
    private $modelo;
    private $etqMae;
    private $footerPosition = -22;
    protected $chaveCargas;

    public function Footer()
    {
        if ($this->etqMae ==false) {

            switch($this->modelo) {
                case 2:
                        // font
                        $this->SetFont('Arial','B',7);
                        //Go to 1.5 cm from bottom
                        $this->SetY($this->footerPosition);
                        $this->Cell(20, 3, "", 0, 1, "L");
                        $this->Cell(20, 3, "", 0, 1, "L");
                        $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                break;
                case 7:
                case 4:
                case 3:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;
                case 6:
                case 10:
                case 11:
                case 12:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
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

    public function imprimirReentrega($idExpedicao, $status, $modelo, $reimpressao = false, $IdEtiquetas = null){

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoReentregaRepository $etiquetaSeparacaoReentregaRepo */
        $etiquetaSeparacaoReentregaRepo = $em->getRepository('wms:Expedicao\EtiquetaSeparacaoReentrega');

        $pendencias = $EtiquetaRepo->getEtiquetasReentrega($idExpedicao, $status, null, $IdEtiquetas);

        if (count($pendencias) <= 0) {
            throw new \Exception('Não Existe Etiquetas de Reentrega com pendência de impressão!');
        }
        $idEtiqueta = array();
        foreach ($pendencias as $pendencia) {
            $idEtiqueta[] = $pendencia['ETIQUETA'];
        }
        $idEtiqueta = implode(",",$idEtiqueta);
        $etiquetas      = $EtiquetaRepo->getEtiquetasByExpedicao(null, null, null, $idEtiqueta,null, true);

        foreach($etiquetas as $etiqueta) {
            $this->etqMae = false;
            $this->layoutEtiqueta($etiqueta, count($etiquetas), $reimpressao, $modelo, true);
        }

        if ($reimpressao == false) {
            foreach ($pendencias as $pendencia) {
                $etiquetaSeparacaoReentregaEn = $etiquetaSeparacaoReentregaRepo->find($pendencia['COD_ES_REENTREGA']);
                $siglaEn = $em->find("wms:Util\Sigla",Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA);
                $etiquetaSeparacaoReentregaEn->setStatus($siglaEn);
                $em->persist($etiquetaSeparacaoReentregaEn);
            }
        }

        $em->flush();
        $this->Output('Etiquetas-reentrega-'.$idExpedicao.'.pdf','D');
    }


    public function imprimir(array $params = array(), $modelo, $idBox = null)
    {
        $this->modelo = $modelo;
        $this->total= "";

        $idExpedicao            = $params['idExpedicao'];
        $idEtiquetaMae          = $params['idEtiquetaMae'];
        $centralEntregaPedido   = $params['central'];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $em->getRepository("wms:Expedicao\ModeloSeparacao");
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Sistema\ParametroRepository $parametroRepo */
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');

        $etiquetas      = $EtiquetaRepo->getEtiquetasByExpedicao($idExpedicao, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $centralEntregaPedido, null, $idEtiquetaMae);

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->setChaveCargas($idExpedicao);

        $etiquetaMaeAnterior = 0;

        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);

        $codProdutoAnterior = null;
        $dscGradeAnterior = null;
        $codCargaExternoAnterior = null;

        $boxEntity = null;
        $dscBox = '';
        if (!is_null($idBox)) {
            $boxEntity = $em->getReference('wms:Deposito\Box', $idBox);
            $dscBox = $boxEntity->getDescricao();
        }
        $contadorCarga = array();
        $contadorProduto = array();
        foreach($etiquetas as $etiqueta) {
            if ($modeloSeparacaoEn->getUtilizaEtiquetaMae() == 'S') {
                if ($etiquetaMaeAnterior != $etiqueta['codEtiquetaMae']) {
                    $this->etqMae = true;
                    $this->layoutEtiquetaMae($etiqueta['codEtiquetaMae']);
                    $etiquetaMaeAnterior = $etiqueta['codEtiquetaMae'];
                }
            }
            $this->etqMae = false;

            if (!isset($contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']]))
                $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] = 0;

            $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] = $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] + 1;

            if (!isset($contadorCarga[$etiqueta['codCargaExterno']])) {
                $contadorCarga[$etiqueta['codCargaExterno']] = 0;
            }
            $contadorCarga[$etiqueta['codCargaExterno']] = $contadorCarga[$etiqueta['codCargaExterno']] + 1;

            $etiqueta['dscBox'] = $dscBox;
            $etiqueta['contadorProdutos'] = $contadorProduto;
            $etiqueta['contadorCargas'] = $contadorCarga;
            $this->layoutEtiqueta($etiqueta,count($etiquetas),false, $modelo,false);

        }
        $this->Output('Etiquetas-expedicao-'.$idExpedicao.'-'.$centralEntregaPedido.'.pdf','D');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo      = $em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoEntity */
        $ExpedicaoEntity    = $ExpedicaoRepo->find($idExpedicao);

        if ($ExpedicaoEntity->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_INTEGRADO) {
            $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
            $ExpedicaoEntity->setStatus($statusEntity);
            $ExpedicaoEntity->setBox($boxEntity);

            $em->persist($ExpedicaoEntity);
        }

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

    public function reimprimir($etiquetas, $motivo, $modelo) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $contadorCarga = array();
        $contadorProduto = array();
        foreach($etiquetas as $etiquetaEntity) {
            $etiqueta      = $EtiquetaRepo->getEtiquetaById($etiquetaEntity->getId());

            if (!isset($contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']]))
                $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] = 0;

            $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] = $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] + 1;

            if (!isset($contadorCarga[$etiqueta['codCargaExterno']]))
                $contadorCarga[$etiqueta['codCargaExterno']] = 0;

            $contadorCarga[$etiqueta['codCargaExterno']] = $contadorCarga[$etiqueta['codCargaExterno']] + 1;

            $dscBox = '';
            $cargaEntity = $em->getRepository('wms:Expedicao\Carga')->findOneBy(array('codCargaExterno' => $etiqueta['codCargaExterno']));
            if (isset($cargaEntity->getExpedicao()->getBox()))
                $dscBox = $cargaEntity->getExpedicao()->getBox()->getDescricao();
            $etiqueta['dscBox'] = $dscBox;
            $etiqueta['contadorProdutos'] = $contadorProduto;
            $etiqueta['contadorCargas'] = $contadorCarga;
            $this->layoutEtiqueta($etiqueta,count($etiquetas),false, $modelo,false);
            $etiquetaEntity->setReimpressao($motivo);
            $em->persist($etiquetaEntity);
            $codProdutoAnterior = $etiqueta['codProduto'];
            $dscGradeAnterior = $etiqueta['grade'];

        }

        $this->Output('etiqueta-reimpressao.pdf','D');

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
                } else {
                    $this->SetFont('Arial', 'B', 20);
                    $this->MultiCell(100, 0.8, "                    REENTREGA", 0, 'L');
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
                } else {
                    $this->SetFont('Arial', 'B', 20);
                    $this->MultiCell(100, 0.8, "                    REENTREGA", 0, 'L');
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
                $impressao .= substr(trim($etiqueta['produto']),0,37)."\n";
                $impressao .= substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $impressao = utf8_decode("$etiqueta[endereco]\n");
                    $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                } else {
                    $this->SetFont('Arial', 'B', 20);
                    $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
                }
                break;

            default:
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $impressao .= substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
                $impressao .= substr(utf8_encode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
                $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
                $impressao .= substr(trim($etiqueta['produto']),0,37)."\n";
                $impressao .= substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $impressao = utf8_decode("$etiqueta[endereco]\n");
                    $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                } else {
                    $this->SetFont('Arial', 'B', 20);
                    $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
                }
                break;
        }
    }

    protected function layoutModelo3($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);
        $sequencePosition = 36;
        $positionCodBarra = 33;

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;

        if ($etiqueta['tipoCarga'] == 'TRANSBORDO') {
            $etiqueta['tipoCarga'] = 'TRANSB.';
        }

        if (!$reimpressao && isset($etiqueta['tipoSaida']) && !empty($etiqueta['tipoSaida'])) {
            $this->InFooter = true;
            $this->SetFont('Arial', 'B', 10);
            $tipoSaida = ($etiqueta['tipoSaida'] == 1) ? "PICKING": "PULMÃO";
            $this->MultiCell(100, 8, utf8_decode("SAÍDA: $tipoSaida"), 0, 'C');
            $this->footerPosition = -15;
            $sequencePosition = 43;
            $positionCodBarra += 5;
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
        $impressao = substr(trim($etiqueta['produto']),0,37)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
        $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 11);
        if ($reentrega == false) {
            $impressao = utf8_decode("$etiqueta[endereco]\n");
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, $positionCodBarra, 68,17);
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
        }
        $this->SetFont('Arial','B',20);
        $this->SetY($sequencePosition);
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

        if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 35) {
            $this->SetFont('Arial', 'B', 11);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 30) {
            $this->SetFont('Arial', 'B', 13);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 20) {
            $this->SetFont('Arial', 'B', 15);
        } else {
            $this->SetFont('Arial', 'B', 18);
        }
        $this->SetFont('Arial', 'B', 18);
        $impressao = substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $this->MultiCell(100, 5.5, $impressao, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $impressao  = utf8_decode("\n\nPEDIDO:$etiqueta[pedido]\n");
        $this->MultiCell(100, 2, $impressao, 0, 'L');


        $this->SetFont('Arial', 'B', 9);
        $impressao = "CODIGO:$etiqueta[codProduto] - EXP:$etiqueta[codExpedicao] \n";
        $this->MultiCell(100, 5.9, $impressao, 0, 'L');
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

        if ($reentrega == true) {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 0.8, "                    REENTREGA", 0, 'L');
        }

    }

    protected function layoutModelo8($etiqueta,$countEtiquetas,$reimpressao,$modelo, $reentrega = false)
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

        if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 35) {
            $this->SetFont('Arial', 'B', 11);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 30) {
            $this->SetFont('Arial', 'B', 13);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 20) {
            $this->SetFont('Arial', 'B', 15);
        } else {
            $this->SetFont('Arial', 'B', 18);
        }
        $this->SetFont('Arial', 'B', 18);
        $impressao = substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $this->MultiCell(100, 5.5, $impressao, 0, 'L');

        $this->SetFont('Arial', 'B', 11);
        $impressao  = utf8_decode("\n\nPEDIDO:$etiqueta[pedido]\n");
        $this->MultiCell(100, 2, $impressao, 0, 'L');


        $this->SetFont('Arial', 'B', 9);
        $impressao = "CODIGO:$etiqueta[codProduto] - EXP:$etiqueta[codExpedicao] \n";
        $this->MultiCell(100, 5.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(trim($etiqueta['produto']),0,70)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        if ($reentrega == false) {
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, $yImage, 68,17);
        }
//        $this->Image(APPLICATION_PATH . '/../public/img/premium-etiqueta.gif', 90, 1.5, 20, 5);
        $this->SetFont('Arial', 'B', 13);
        $this->SetY(36);
        $this->Cell(20, 3,   utf8_decode($etiqueta['tipoComercializacao']). $qtdEmbalagem, 0, 1, "L");

        if ($reentrega == true) {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 0.8, "                    REENTREGA", 0, 'L');
        }

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
            $impressao = utf8_decode("$etiqueta[endereco]\n");
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
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
            case 11:
                $this->layoutModelo11($etiqueta,$countEtiquetas,$reimpressao,$modelo,$reentrega);
                break;
            case 12:
                $this->layoutModelo12($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 10:
                $this->layoutModelo10($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 9:
                $this->layoutModelo9($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 8:
                $this->layoutModelo8($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 7:
                $this->layoutModelo7($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
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
                $impressao .= utf8_decode(substr(trim($etiqueta['produto']),0,40))."\n";
                $impressao .= substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                if ($reentrega == false) {
                    $impressao = utf8_decode("$etiqueta[endereco]\n");
                    $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
                } else {
                    $this->SetFont('Arial', 'B', 20);
                    $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
                }
                break;

            default:
                $this->SetFont('Arial', 'B', 10);
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 9);
                $impressao = substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
                $impressao .= substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
                $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 10);
                $impressao = utf8_decode(substr(trim($etiqueta['produto']),0,70))."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 8);
                $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";

                if (!isset($etiqueta['quantidade'])) {
                    $etiqueta['quantidade'] = '';
                }

                $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] -  $etiqueta[tipoComercializacao] ($etiqueta[quantidade])"."\n";
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 10);
                if ($reentrega == false) {
                    $impressao = utf8_decode("$etiqueta[endereco]\n");
                    $this->MultiCell(90, 3.9, $impressao, 0, 'L');
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68, 17);

                    if (isset($etiqueta['sequenciaPedido']) && ($etiqueta['sequenciaPedido'] != null)) {
                        $this->SetY(8);
                        $this->SetX(85);

                        $this->SetFont('Arial', 'B', 50);
                        $this->Cell(10,5,$etiqueta['sequenciaPedido']);
                    }

                } else {
                    $this->SetFont('Arial', 'B', 20);
                    $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
                }
                break;
        }
    }

    protected function layoutModelo7($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->InFooter = true;

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
        if ($tamanhoSringProduto >= 45) {
            $this->SetFont('Arial', 'B', 9);
        } else if ($tamanhoSringProduto >= 35) {
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
        $impressao = utf8_decode("$etiqueta[endereco]");
        $this->Cell(40, 4, $impressao);

        if (isset($etiqueta['tipoSaida']) && !empty($etiqueta['tipoSaida'])) {
            $this->SetFont('Arial', 'B', 10);
            $impressao = utf8_decode("SAÍDA: " . ReservaEstoqueExpedicao::$tipoSaidaTxt[$etiqueta['tipoSaida']]);
            $this->Cell(50, 4, $impressao, 0, '1');
        }

        $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 40, 68,17);
        if ($reimpressao == true) {
            $this->SetFont('Arial','B',20);
            $this->SetY(34);
        }else {
            $this->SetFont('Arial','B',30);
            $this->SetY(36);
        }


        //$this->Cell(20, 3,  $etiqueta['sequencia'], 0, 1, "L");
    }

    protected function layoutModelo9($etiqueta,$countEtiquetas,$reimpressao,$modelo, $reentrega = false)
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

        if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 35) {
            $this->SetFont('Arial', 'B', 11);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 30) {
            $this->SetFont('Arial', 'B', 13);
        } else if (strlen("$etiqueta[codClienteExterno] - $etiqueta[cliente]") > 20) {
            $this->SetFont('Arial', 'B', 15);
        } else {
            $this->SetFont('Arial', 'B', 18);
        }
        $this->SetFont('Arial', 'B', 18);
        $impressao = substr(utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $this->MultiCell(100, 5.5, $impressao, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $impressao  = utf8_decode("\n\nPEDIDO:$etiqueta[pedido] - $etiqueta[placaExpedicao]\n");
        $this->MultiCell(100, 2, $impressao, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $impressao = "CODIGO:$etiqueta[codProduto] - EXP:$etiqueta[codExpedicao] \n";
        $this->MultiCell(100, 5.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(trim($etiqueta['produto']),0,70)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        if ($reentrega == false) {
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, $yImage, 68,17);
        }

        $this->SetFont('Arial', 'B', 13);
        $this->SetY(36);
        $this->Cell(20, 3,   utf8_decode($etiqueta['tipoComercializacao']). $qtdEmbalagem, 0, 1, "L");

        if ($reentrega == true) {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 0.8, "                    REENTREGA", 0, 'L');
        }

    }

    protected function layoutModelo10($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
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
        $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
        $this->MultiCell(100, 4.5, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 8);
        if (strlen("$etiqueta[cliente]") <= 30) {
            $this->SetFont('Arial', 'B', 10);
        }
        $this->SetFont('Arial', 'B', 8);
        $impressao = substr(utf8_decode("$etiqueta[cliente]"),0,40)."\n";

        if (strlen("COD.: $etiqueta[codProduto] - GRD.: $etiqueta[grade]") <= 33) {
            $this->SetFont('Arial', 'B', 13);
        } elseif (strlen("COD.: $etiqueta[codProduto] - GRD.: $etiqueta[grade]") <= 40) {
            $this->SetFont('Arial', 'B', 10);
        } else {
            $this->SetFont('Arial', 'B', 8);
        }
        $this->SetFont('Arial', 'B', 8);
        $impressao .= "COD.: $etiqueta[codProduto] - GRD.: $etiqueta[grade]\n";
        $this->MultiCell(100, 4.5, $impressao, 0, 'L');

        if (strlen(trim($etiqueta['produto'])) <= 33) {
            $this->SetFont('Arial', 'B', 13);
        } elseif (strlen(trim($etiqueta['produto'])) <= 40) {
            $this->SetFont('Arial', 'B', 10);
        } else {
            $this->SetFont('Arial', 'B', 8);
        }
        $impressao = utf8_decode(substr(trim($etiqueta['produto']),0,70))."\n";
        $this->MultiCell(100, 4.5, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 8);
        $impressao = substr(utf8_decode("FORNECEDOR:$etiqueta[fornecedor]"),0,40) . "\n";

        if (!isset($etiqueta['quantidade'])) {
            $etiqueta['quantidade'] = '';
        }

        if (strlen("$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] -  $etiqueta[tipoComercializacao] ($etiqueta[quantidade]) - Pedido: $etiqueta[codEntrega]") <= 33) {
            $this->SetFont('Arial', 'B', 13);
        } elseif (strlen("$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] -  $etiqueta[tipoComercializacao] ($etiqueta[quantidade]) - Pedido: $etiqueta[codEntrega]") <= 40) {
            $this->SetFont('Arial', 'B', 10);
        } else {
            $this->SetFont('Arial', 'B', 8);
        }

        $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] -  $etiqueta[tipoComercializacao] ($etiqueta[quantidade]) - Pedido: $etiqueta[codEntrega]"."\n";
        $this->MultiCell(100, 4.5, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 11);
        if ($reentrega == false) {
            $endereco = '';
            if (!is_null($etiqueta['endereco'])) {
                $endereco = "$etiqueta[endereco] -";
            }
            $impressao = utf8_decode("$endereco Nº Etiqueta: $etiqueta[codBarras]\n");
            $this->MultiCell(90, 3.9, $impressao, 0, 'L');
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 41, 68, 17);

            if (isset($etiqueta['sequenciaPedido']) && ($etiqueta['sequenciaPedido'] != null)) {
                $this->SetY(8);
                $this->SetX(85);

                $this->SetFont('Arial', 'B', 50);
                $this->Cell(10,5,$etiqueta['sequenciaPedido']);
            }

        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
        }

    }

    protected function layoutModelo11($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
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

        $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - PLACA:$etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
        $impressao .= substr(utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[itinerario]"),0,40) . "\n";
        $impressao .= substr(utf8_encode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
        $impressao .= substr(trim($etiqueta['produto']),0,37)."\n";
        $impressao .= "$etiqueta[linhaSeparacao] - ESTOQUE:$etiqueta[codEstoque] - ". utf8_decode($etiqueta['tipoComercializacao'])."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        if ($reentrega == false) {
            $impressao = utf8_decode("$etiqueta[endereco]\n");
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
        }
    }

    protected function layoutModelo12($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;

        $this->SetX(30);
        $this->SetFont('Arial', 'B', 11);
        $impressao = utf8_decode(substr("$etiqueta[codClienteExterno] - $etiqueta[cliente] \n",0,50));
        $this->MultiCell(78, 4.3, $impressao, 1, 'L');
        $this->SetX(30);
        $y1 = $this->getY();
        $impressao = "EXP: $etiqueta[codExpedicao]";
        $this->MultiCell(40, 5, $impressao, 1, 'L');
        $this->SetY($y1);
        $impressao = (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total;
        $this->SetX(70);
        $this->MultiCell(38, 5, $impressao, 1, 'L');
        $this->SetX(3);
        $y2 = $this->getY();
        $this->SetFont('Arial', 'B', 17);
        $impressao = "CARGA: $etiqueta[codCargaExterno] ";
        $this->SetY($y2 + 1.5);
        $this->MultiCell(105, 6.5, $impressao, 1, 'L');
        $this->SetY($y2 + 1.5);
        $impressao = $etiqueta['contadorCargas'][$etiqueta['codCargaExterno']] . '/' . $etiqueta['qtdCargaDist'];
        $this->SetX(70);
        $this->MultiCell(38, 6.5, $impressao, 1, 'L');
        $this->SetFont('Arial', 'B', 17);
        $impressao = utf8_decode("CODIGO: $etiqueta[codProduto]");
        $this->MultiCell(105, 6, $impressao, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $impressao = substr("$etiqueta[produto]",0,45);
        $this->MultiCell(105, 4, $impressao, 1, 'L');
        $this->SetFont('Arial', 'B', 17);
        $y3 = $this->getY();
        $impressao = str_replace('.','-',"$etiqueta[endereco]");
        $this->MultiCell(50, 6, $impressao, 1, 'C');
        $this->SetY($y3);
        $impressao = $etiqueta['contadorProdutos'][$etiqueta['codProduto']][$etiqueta['idCaracteristica']] . '/' . $etiqueta['qtdProdDist'] . '-' . $etiqueta['dscBox'];
        $this->SetX(53);
        $this->MultiCell(55, 6, $impressao, 1, 'L');
        $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 40, 41, 65, 17);
    }

}
