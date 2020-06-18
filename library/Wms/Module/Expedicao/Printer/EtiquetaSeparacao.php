<?php

namespace Wms\Module\Expedicao\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\CaixaEmbalado;
use Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository;
use Wms\Domain\Entity\Expedicao\PedidoEndereco;
use Wms\Domain\Entity\ExpedicaoRepository;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao;

class EtiquetaSeparacao extends Pdf
{
    private $total;
    private $volEntrega;
    private $posVolume;
    private $strReimpressao;
    private $modelo;
    private $etqMae;
    private $footerPosition = -22;
    private $etiqueta;
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
                case 3:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;
                case 10:
                    $this->SetY($this->footerPosition + 4);
                    if ($this->etiqueta['dscBox'] != 'N/D') {
                        $this->SetFont('Arial', 'B', 13);
                        $this->Cell(40, 4, $this->etiqueta['dscBox']);
                    }
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->Cell(20, 7, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;
                case 12:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;
                case 13:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 3, 'Etiqueta ' .  $this->posVolume  . '/' . $this->total, 0, 1, "L");
                    $this->Cell(20, 3, 'Volume de Entrega', 0, 1, "L");
                    $this->Cell(20, 3, $this->etiqueta['posEntrega']  . ' de ' . $this->etiqueta['totalEntrega'], 0, 1, "C");
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    $this->posVolume--;
                    break;
                case 14:
                    // font
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition + 11);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->SetFont('Arial','B',10);
                    $this->Cell(20, 3, 'Etiqueta ' . (($this->PageNo() - 1 - $this->total)*-1) . '/' . $this->total, 0, 1, "L");
                    $this->SetFont('Arial','B',7);
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    break;
                case 16:
                    $this->SetFont('Arial','B',7);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition + 6);
                    $this->SetX(0);
                    $this->Cell(20, 3,"", 0, 1, "L");
                    $this->SetFont('Arial','B',12);
                    $this->Cell(20, 4, 'VOLUME ' . $this->etiqueta['posEntrega'], 0, 1, "L");
                    $this->SetFont('Arial','B',7);
                    $this->Cell(20, 4, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    $this->SetFont('Arial','B',7);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    break;
                case 17:
                    // font
                    $this->SetFont('Arial','B',18);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
                    $this->Cell(20, 3, utf8_decode($this->strReimpressao), 0, 1, "L");
                    $this->Cell(20, 10, 'Volume: ' .  $this->etiqueta['posEntrega'], 0, 1, "L");
                    $this->SetFont('Arial','B',7);
                    $this->Cell(20, 3, utf8_decode(date('d/m/Y')." às ".date('H:i')), 0, 1, "L");
                    $this->posVolume--;
                    break;
                case 18:
                    // font
                    $this->SetFont('Arial','B',8);
                    //Go to 1.5 cm from bottom
                    $this->SetY($this->footerPosition);
                    $this->Cell(20, 10, '', 0, 1, "L");
                    $this->SetFont('Arial','B',9);
                    $this->Cell(20, 3, 'Volume: ' . $this->etiqueta['posEntrega'], 0, 1, "L");
                    $this->posVolume--;
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
        $this->total = "";

        $idExpedicao            = $params['idExpedicao'];
        $idEtiquetaMae          = $params['idEtiquetaMae'];
        $centralEntregaPedido   = $params['central'];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $em->getRepository("wms:Expedicao\ModeloSeparacao");
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $etiquetas = $EtiquetaRepo->getEtiquetasByExpedicao($idExpedicao, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $centralEntregaPedido, null, $idEtiquetaMae);

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->setChaveCargas($idExpedicao);

        $etiquetaMaeAnterior = 0;

        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);

        $codProdutoAnterior = null;
        $dscGradeAnterior = null;
        $codCargaExternoAnterior = null;

        $boxEntity = null;
        $dscBox = '';
        if (!is_null($idBox)) {
            $boxEntity = $em->find('wms:Deposito\Box', trim($idBox));
            $dscBox = $boxEntity->getDescricao();
        }
        $contadorCarga = array();
        $contadorProduto = array();
        $contadorCliente = array();

        $agroupEtiquetas = ($modeloSeparacaoEn->getAgrupContEtiquetas() == "S");
        $usaCaixaEmbPadrao = ($modeloSeparacaoEn->getUsaCaixaPadrao() == "S");

        /** @var Expedicao $expedicaoEn */
        $expedicaoEn = $em->find("wms:Expedicao", $idExpedicao);

        $this->posVolume = count($etiquetas);
        $arrVolsEntrega = [];
        if ($agroupEtiquetas) {

            $preCountVolCliente = [];
            if ($usaCaixaEmbPadrao) {
                /** @var CaixaEmbalado $caixaEn */
                $caixaEn = $em->getRepository('wms:Expedicao\CaixaEmbalado')->findOneBy(['isAtiva' => true, 'isDefault' => true]);
                if (empty($caixaEn))
                    throw new \Exception("O modelo de separação está configurado para sequenciamento único dos volumes<br/>com base na caixa de embalagem padrão, para isso é obrigatório o cadastro de uma caixa de embalado padrão e que esteja ativa!");

                $preCountVolCliente = CaixaEmbalado::calculaExpedicao(
                    $caixaEn,
                    $em->getRepository(Expedicao\MapaSeparacaoProduto::class)->getMaximosConsolidadoByCliente($idExpedicao)
                );
            }

            $numEtiquetas = $expedicaoEn->getCountVolumes();
            $countEtiquetasCliente = $em->getRepository('wms:Expedicao\VEtiquetaSeparacao')->getCountEtiquetasByCliente($idExpedicao);

            while(!empty($preCountVolCliente) || !empty($countEtiquetasCliente)) {
                if (!empty($countEtiquetasCliente)) {
                    reset($countEtiquetasCliente);
                    $idCliente = key($countEtiquetasCliente);
                } else {
                    reset($preCountVolCliente);
                    $idCliente = key($preCountVolCliente);
                }
                $qtdVolEmb = (!empty($preCountVolCliente[$idCliente])) ? $preCountVolCliente[$idCliente] : 0;
                $qtdEtiq = (!empty($countEtiquetasCliente[$idCliente])) ? $countEtiquetasCliente[$idCliente] : 0;
                $arrVolsEntrega[$idCliente]['total'] = $qtdVolEmb + $qtdEtiq;
                $arrVolsEntrega[$idCliente]['pos'] = $qtdEtiq;
                unset($countEtiquetasCliente[$idCliente]);
                unset($preCountVolCliente[$idCliente]);
            }
        } else {
            $numEtiquetas = count($etiquetas);
        }

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

            if (!isset($contadorCliente[$etiqueta['codClienteExterno']])) {
                $contadorCliente[$etiqueta['codClienteExterno']] = 0;
            }
            $contadorCliente[$etiqueta['codClienteExterno']] = $contadorCliente[$etiqueta['codClienteExterno']] + 1;


            if (!empty($dscBox)) $etiqueta['dscBox'] = $dscBox;
            $etiqueta['contadorProdutos'] = $contadorProduto;
            $etiqueta['contadorCargas'] = $contadorCarga;
            $etiqueta['contadorClientes'] = $contadorCliente;

            if ($agroupEtiquetas && empty($etiqueta['posVolume'])) {

                $etiqueta['posEntrega'] = $arrVolsEntrega[$etiqueta['codCliente']]['pos'];
                $etiqueta['totalEntrega'] = $arrVolsEntrega[$etiqueta['codCliente']]['total'];
                $EtiquetaRepo->savePosVolumeImpresso($etiqueta['id'], $this->posVolume, $etiqueta['posEntrega'], $etiqueta['totalEntrega']);
                $arrVolsEntrega[$etiqueta['codCliente']]['pos']--;
            }

            $this->layoutEtiqueta($etiqueta, $numEtiquetas,false, $modelo,false);
        }
        $this->Output('Etiquetas-expedicao-'.$idExpedicao.'-'.$centralEntregaPedido.'.pdf','D');

        if ($expedicaoEn->getStatus()->getId() == Expedicao::STATUS_INTEGRADO) {
            $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_EM_SEPARACAO);
            $expedicaoEn->setStatus($statusEntity);
            $expedicaoEn->setBox($boxEntity);

            $em->persist($expedicaoEn);
        }

        foreach($etiquetas as $etiqueta) {
            try {
                $EtiquetaRepo->efetivaImpressao($etiqueta['codBarras'], $centralEntregaPedido);
            } catch(\Exception $e) {
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

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $em->getRepository("wms:Expedicao\ModeloSeparacao");
        /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($etiquetas[0]['codExpedicao']);
        $agroupEtiquetas = ($modeloSeparacaoEn->getAgrupContEtiquetas() == "S");

        if ($agroupEtiquetas) {
            /** @var Expedicao $expedicaoEn */
            $expedicaoEn = $em->find("wms:Expedicao", $etiquetas[0]['codExpedicao']);
            $numEtiquetas = $expedicaoEn->getCountVolumes();
        } else {
            $numEtiquetas = count($etiquetas);
        }

        foreach($etiquetas as $etiqueta) {
            $this->posVolume = $etiqueta['posVolume'];
            $this->layoutEtiqueta($etiqueta, $numEtiquetas,true, $modelo);
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

    public function reimprimir($etiquetas, $motivo, $modelo, $idExpedicao) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $em->getRepository("wms:Expedicao\ModeloSeparacao");
        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);
        $agroupEtiquetas = ($modeloSeparacaoEn->getAgrupContEtiquetas() == "S");

        if ($agroupEtiquetas) {
            /** @var Expedicao $expedicaoEn */
            $expedicaoEn = $em->find("wms:Expedicao", $idExpedicao);
            $numEtiquetas = $expedicaoEn->getCountVolumes();
            $this->posVolume = count($etiquetas);
        } else {
            $numEtiquetas = count($etiquetas);
        }

        $contadorCarga = array();
        $contadorProduto = array();
        $contadorCliente = array();
        /** @var Expedicao\EtiquetaSeparacao $etiquetaEntity */
        foreach($etiquetas as $etiquetaEntity) {
            $etiqueta      = $EtiquetaRepo->getEtiquetaById($etiquetaEntity->getId());

            if (!isset($contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']]))
                $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] = 0;

            $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] = $contadorProduto[$etiqueta['codProduto']][$etiqueta['idCaracteristica']] + 1;

            if (!isset($contadorCarga[$etiqueta['codCargaExterno']]))
                $contadorCarga[$etiqueta['codCargaExterno']] = 0;

            $contadorCarga[$etiqueta['codCargaExterno']] = $contadorCarga[$etiqueta['codCargaExterno']] + 1;

            if (!isset($contadorCliente[$etiqueta['codClienteExterno']])) {
                $contadorCliente[$etiqueta['codClienteExterno']] = 0;
            }
            $contadorCliente[$etiqueta['codClienteExterno']] = $contadorCliente[$etiqueta['codClienteExterno']] + 1;


            $cargaEntity = $em->getRepository('wms:Expedicao\Carga')->findOneBy(array('codCargaExterno' => $etiqueta['codCargaExterno']));
            $boxEntity = $cargaEntity->getExpedicao()->getBox();
            $dscBox = '';
            if (isset($boxEntity))
                $dscBox = $cargaEntity->getExpedicao()->getBox()->getDescricao();
            $etiqueta['dscBox'] = $dscBox;
            $etiqueta['contadorProdutos'] = $contadorProduto;
            $etiqueta['contadorCargas'] = $contadorCarga;
            $etiqueta['contadorClientes'] = $contadorCliente;
            $this->posVolume = $etiqueta['posVolume'];
            $this->layoutEtiqueta($etiqueta, $numEtiquetas,true, $modelo,false);
            $etiquetaEntity->setReimpressao($motivo);
            $em->persist($etiquetaEntity);

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
    
    protected function layoutEtiqueta($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        switch ($modelo) {
            case 18: //LAYOUT MACROLUB
                $this->layoutModelo18($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega);
                break;
            case 17: //LAYOUT VETSS
                $this->layoutModelo17($etiqueta,$countEtiquetas,$reimpressao,$modelo,$reentrega);
                break;
            case 16: //LAYOUT MBLED
                $this->layoutModelo16($etiqueta,$countEtiquetas,$reimpressao,$modelo,$reentrega);
                break;
            case 15:
                $this->layoutModelo15($etiqueta,$countEtiquetas,$reimpressao,$modelo,$reentrega);
                break;
            case 14: // LAYOUT PLANETA
                $this->layoutModelo14($etiqueta,$countEtiquetas,$reimpressao,$modelo,$reentrega);
                break;
            case 13:
                $this->layoutModelo13($etiqueta,$countEtiquetas,$reimpressao,$modelo,$reentrega);
                break;
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
        $this->etiqueta = $etiqueta;

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
        $this->InFooter = true;

        $this->SetX(30);
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(78, 4.3, $this->SetStringByMaxWidth("$etiqueta[codClienteExterno] - $etiqueta[cliente]",115), 1, 'L');
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
        $this->InFooter = false;
    }


    protected function layoutModelo13($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->etiqueta = $etiqueta;
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

    protected function layoutModelo14($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
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
        $impressao  = utf8_decode("EXP: $etiqueta[codExpedicao] - $etiqueta[placaExpedicao] - $etiqueta[tipoCarga]: $etiqueta[codCargaExterno]");
        $this->MultiCell(85, 3.5, $impressao, 0, 'L');
        $this->MultiCell(85, 3, $this->SetStringByMaxWidth($etiqueta['cliente'], 85), 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->MultiCell(85, 3.5, "ENTREGA: $etiqueta[codEntrega]", 0, 'L');

        $this->SetFont('Arial', 'B', 10);
        $this->MultiCell(85, 1, "", 0, 'L');
        $this->MultiCell(85, 3, $this->SetStringByMaxWidth("$etiqueta[codProduto] - $etiqueta[produto]", 80), 0, 'L');
        $this->MultiCell(85, 1, "", 0, 'L');

        $this->SetFont('Arial', 'B', 10);
        $this->MultiCell(85, 2.8, utf8_decode($etiqueta['tipoComercializacao']), 0, 'L');


        $this->SetXY(3,40);
        $this->SetFont('Arial', null, 11);
        $this->Cell(10, 4, "BOX:");
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(40, 4, $etiqueta['dscBox']);

        $this->InFooter = true;

        if (!empty($etiqueta['tipoSaida'])) {
            $this->SetFont('Arial', 'B', 8);
            $this->SetXY(40,17);
            $this->Cell(20, 3, utf8_decode("SAÍDA:". ReservaEstoqueExpedicao::$tipoSaidaTxt[$etiqueta['tipoSaida']]));
        }
        $this->SetXY(65,16.5);
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(30, 4, $etiqueta["endereco"]);

        $this->SetXY(52,22);
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(30, 4, utf8_decode("Sequência: $etiqueta[seqRota]-$etiqueta[seqPraca]"));

        $this->SetXY(49,28);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(10, 4, "Rota:");
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(30, 4.5, $this->SetStringByMaxWidth($etiqueta["nomeRota"],30));

        $this->SetXY(49,34);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(12, 4, utf8_decode("Praça:"));
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(30, 4.5, $this->SetStringByMaxWidth($etiqueta["nomePraca"],30));

        $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 3, 21, 45,10);

        $this->InFooter = false;
    }

    protected function layoutModelo15($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 11);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;
        $this->SetFont('Arial', 'B', 17);

        $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]\n");
        $impressao .= "$etiqueta[placaExpedicao]\n";
        $this->MultiCell(100, 6, $impressao, 0, 'L');
        $this->SetFont('Arial', 'B', 11);
        $impressao = substr(utf8_encode("$etiqueta[codClienteExterno] - $etiqueta[cliente]"),0,40)."\n";
        $impressao .= "CODIGO:$etiqueta[codProduto] - GRADE:$etiqueta[grade]\n";
        $impressao .= substr(trim($etiqueta['produto']),0,37)."\n";
        $this->MultiCell(100, 3.9, $impressao, 0, 'L');
        $this->InFooter = true;
        if ($reentrega == false) {
            $impressao = utf8_decode("$etiqueta[endereco] - $etiqueta[tipoComercializacao]\n");
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 29, 33, 68,17);
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
        }

    }

    protected function layoutModelo16($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 11);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->etiqueta = $etiqueta;
        $this->strReimpressao = $strReimpressao;
        $this->SetY(0.5);
        $this->SetFont('Arial', 'B', 15);
        $impressao = utf8_decode("EXP:$etiqueta[codExpedicao]");
        $this->MultiCell(100, 3.5, $impressao, 0, 'L');

        $impressao = "PEDIDO: $etiqueta[codCargaExterno]";
        $this->MultiCell(100, 7, $impressao, 0, 'L');

        $this->SetY(16);
        $impressao = 'TRANSP.: '.str_replace(array('0','1','2','3','4','5','6','7','8','9','-'),'',substr($etiqueta['placaExpedicao'],0,16))."\n";
        $this->MultiCell(100, 7, $impressao, 0, 'L');

        $this->Line(0,24,100,24);
        $this->SetY(25.5);
        $this->SetFont('Arial', 'B', 13);
        if (strlen(utf8_encode("$etiqueta[cliente]")) > 55) {
            $this->SetFont('Arial', 'B', 10);
        }
        $impressao = utf8_encode("$etiqueta[cliente] - $etiqueta[cidadeEntrega] - $etiqueta[siglaEstado]")."\n";
        $this->MultiCell(100, 5, $impressao, 0, 'L');

        $this->Line(0,42,100,42);
        $this->SetY(44);
        $this->SetFont('Arial', 'B', 13);
        if ($etiqueta['codProduto'] . ' - ' . utf8_decode(trim($etiqueta['produto'])) > 55) {
            $this->SetFont('Arial', 'B', 10);
        }
        $impressao = 'PRODUTO: '.$etiqueta['codProduto'] . ' - ' . utf8_decode(trim(substr($etiqueta['produto'],0,35)))."\n";
        $this->MultiCell(100, 5, $impressao, 0, 'L');

        $this->InFooter = true;
        $this->etiqueta = $etiqueta;
        if ($reentrega == false) {
            $this->Line(0,55,100,55);
            $this->SetY(57);
            $this->SetFont('Arial', 'B', 13);
            $impressao = utf8_decode("$etiqueta[endereco] - $etiqueta[quantidade] PÇS/CX\n");
            $this->MultiCell(100, 5, $impressao, 0, 'L');
            $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 53,57,47,17);
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->MultiCell(100, 6.5, "                    REENTREGA", 0, 'L');
        }

    }

    protected function layoutModelo17($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->etiqueta = $etiqueta;
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
                $this->SetFont('Arial', 'B', 11);
                $impressao  = utf8_decode("EXP:$etiqueta[codExpedicao] - $etiqueta[placaExpedicao] - $etiqueta[tipoCarga]:$etiqueta[codCargaExterno]");
                $this->MultiCell(100, 5, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 14);
                $impressao = utf8_decode("$etiqueta[tipoPedido]:$etiqueta[codEntrega] - $etiqueta[cidadeEntrega]");
                $this->MultiCell(100, 6, $this->SetStringByMaxWidth($impressao, 95), 0, 'L');
                $this->SetFont('Arial', 'B', 14);
                $impressao = utf8_decode("$etiqueta[codClienteExterno] - $etiqueta[cliente]");
                $this->MultiCell(100, 7, $this->SetStringByMaxWidth($impressao, 95), 0, 'L');
                $this->SetFont('Arial', 'B', 10);
                $impressao = "CODIGO: $etiqueta[codProduto]";
                $this->MultiCell(100, 5, $impressao, 0, 'L');
                $this->SetFont('Arial', 'B', 10);
                $impressao = utf8_decode($this->SetStringByMaxWidth($etiqueta['produto'], 83) . " $etiqueta[tipoComercializacao] ($etiqueta[quantidade])");
                $this->MultiCell(100, 3.9, $impressao, 0, 'L');

                if (!isset($etiqueta['quantidade'])) {
                    $etiqueta['quantidade'] = '';
                }

                $this->SetFont('Arial', 'B', 10);
                if ($reentrega == false) {
                    $this->MultiCell(90, 3.9, utf8_decode("$etiqueta[endereco]"), 0, 'L');
                    $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 50, 38, 50, 12);

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

    protected function layoutModelo18($etiqueta,$countEtiquetas,$reimpressao, $modelo, $reentrega = false)
    {
        $this->SetMargins(3, 1.5, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->etiqueta = $etiqueta;

        $imgW = 14;
        $imgH = 5.5;
        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->InFooter = true;
        $this->total=$countEtiquetas;
        $this->modelo = $modelo;
        $this->strReimpressao = $strReimpressao;
        $this->SetFont('Arial', 'B', 8);
        $impressao = "CARGA: $etiqueta[codCargaExterno]";
        $this->Cell(92, 3, $impressao, 0, 1,'R');
        $this->setX(17.5);
        $impressao  = utf8_decode("TRANSP.: $etiqueta[placaExpedicao]");
        $this->Cell(50, 2.8, $impressao, 0, 1,'L');
        $impressao = utf8_decode("CLIENTE: $etiqueta[codClienteExterno] - $etiqueta[cliente]");
        $this->Cell(50, 2.8, $impressao, 0, 1,'L');
        $this->SetFont('Arial', '', 7);
        $impressao  = utf8_decode("$etiqueta[tipoPedido]: $etiqueta[codEntrega] - $etiqueta[ruaEntrega], N $etiqueta[numeroEntrega], $etiqueta[cidadeEntrega] - $etiqueta[siglaEstado]");
        $this->Cell(60, 2.8, $impressao, 0, 1,'L');
        $impressao = "PROD.: $etiqueta[codProduto] - ".utf8_decode(substr(trim($etiqueta['produto']),0,70));
        $this->Cell(60, 2.8, $impressao, 0, 1,'L');
        $this->SetFont('Arial', 'B', 8.5);
        $impressao = "COD BARRAS: $etiqueta[codBarras] - END. SEP.: $etiqueta[endereco]";
        $this->Cell(60, 2.8, $impressao, 0, 1,'L');
        $this->Image(@CodigoBarras::gerarNovo($etiqueta['codBarras']), 25, 18,50,12);
        $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 3, 2, $imgW - 1, $imgH);
    }
}
