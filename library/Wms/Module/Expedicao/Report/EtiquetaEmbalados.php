<?php

namespace Wms\Module\Expedicao\Report;

use Wms\Util\Barcode\Barcode;
use Wms\Util\Barcode\eFPDF,
    Wms\Util\CodigoBarras;

class EtiquetaEmbalados extends eFPDF
{

    public function imprimirExpedicaoModelo($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $modeloEtiqueta, $fechaEmbaladosNoFinal = false)
    {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 1.5, 0);
        $this->SetAutoPageBreak(0,0);

        switch ($modeloEtiqueta) {
            case 1:
                //LAYOUT CASA DO CONFEITEIRO
                self::bodyExpedicaoModelo1($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal);
                break;
            case 2:
                //LAYOUT WILSO
                self::bodyExpedicaoModelo2($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal);
                break;
            case 3:
                //LAYOUT ABRAFER
                self::bodyExpedicaoModelo3($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal);
                break;
            case 4:
                //LAYOUT HIDRAU
                self::bodyExpedicaoModelo4($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal);
                break;
            case 5:
                //LAYOUT ETIQUETAS AGRUPADAS BASEADO MODELO 1
                self::bodyExpedicaoModelo5($volumePatrimonio);
                break;
            case 6:
                //LAYOUT PLANETA
                self::bodyExpedicaoModelo6($volumePatrimonio, $mapaSeparacaoEmbaladoRepo);
                break;
            case 7:
                //LAYOUT MBLED
                self::bodyExpedicaoModelo7($volumePatrimonio);
                break;
            case 8:
                self::bodyExpedicaoModelo8($volumePatrimonio);
                break;
            case 9:
                //LAYOUT VETSS
                self::bodyExpedicaoModelo9($volumePatrimonio);
                break;
            case 10:
                //LAYOUT MOTOARTE
                self::bodyExpedicaoModelo10($volumePatrimonio, $mapaSeparacaoEmbaladoRepo);
                break;
            case 11:
                self::bodyExpedicaoModelo11($volumePatrimonio, $mapaSeparacaoEmbaladoRepo);
                break;
            default:
                self::bodyExpedicaoModelo1($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal);
                break;

        }
        $this->Output('Volume-Embalado.pdf','I');
        exit;
    }

    private function bodyExpedicaoModelo1($volumes,$mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal)
    {

        $totalEtiquetas = count($volumes);

        foreach ($volumes as $volume) {

            $existeItensPendentes = true;
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('id' => $volume['COD_MAPA_SEPARACAO_EMB_CLIENTE'], 'ultimoVolume' => 'S'));
            if (isset($mapaSeparacaoEmbaladoEn) && !empty($mapaSeparacaoEmbaladoEn)) {
                $existeItensPendentes = false;
            }
            $this->AddPage();
            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr($volume['NOM_PESSOA']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('ROTA: '.$volume['DSC_ITINERARIO']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('PLACA: '.$volume['DSC_PLACA_CARGA']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('CARGA: '.$volume['COD_CARGA_EXTERNO']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 7);

            if ($fechaEmbaladosNoFinal)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$totalEtiquetas;
            else if ($existeItensPendentes == false)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];
            else
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];

            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 6, 20 , 33, 9.5);
        }
    }

    private function bodyExpedicaoModelo2($volumes,$mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filialEntity = $em->getReference('wms:Filial', (int) $deposito->getFilial()->getId());
        $pessoaEntity = $em->getReference('wms:Pessoa', (int) $filialEntity->getId());

        $totalEtiquetas = count($volumes);

        foreach ($volumes as $volume) {

            $existeItensPendentes = true;
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('id' => $volume['COD_MAPA_SEPARACAO_EMB_CLIENTE'], 'ultimoVolume' => 'S'));
            if (isset($mapaSeparacaoEmbaladoEn) && !empty($mapaSeparacaoEmbaladoEn)) {
                $existeItensPendentes = false;
            }

            $this->AddPage();
            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 15);
            $impressao = utf8_decode(substr($pessoaEntity->getNome()."\n",0,20));
            $this->MultiCell(110, 10, $impressao, 0, 'L');
            $this->Line(0,10,80,10);

            $this->SetFont('Arial', 'B', 12.5);
            $impressao = utf8_decode('CLIENTE: '."\n");
            $this->MultiCell(110, 6, '', 0, 'L');
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 11.3);
            $impressao = utf8_decode(substr($volume['NOM_PESSOA']."\n",0,30));
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr($volume['DSC_ENDERECO'].', '.$volume['NUM_ENDERECO'] ."\n",0,50));
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $impressao = utf8_decode($volume['NOM_BAIRRO'].'  -  '.$volume['NOM_LOCALIDADE'].'  -  '.$volume['COD_REFERENCIA_SIGLA']);
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $impressao = utf8_decode('CARGA: '.$volume['COD_CARGA_EXTERNO']);
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $this->Line(0,45,110,45);

            $this->SetFont('Arial', '', 20);
            $this->MultiCell(110, 6, '', 0, 'L');

            if ($fechaEmbaladosNoFinal)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$totalEtiquetas;
            else if ($existeItensPendentes == false)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];
            else
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];

            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 22);
            $impressao = utf8_decode(substr($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']."\n",0,30));
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetXY(60,62.5);
            $this->SetFont('Arial', null, 9);
            $this->MultiCell(100, 4, $volume['DTH_FECHAMENTO']);


            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 50, 47 , 45, 13);
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 71, 0, 35, 30);

        }
    }

    private function bodyExpedicaoModelo3($volumes,$mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filialEntity = $em->getReference('wms:Filial', (int) $deposito->getFilial()->getId());
        $pessoaEntity = $em->getReference('wms:Pessoa', (int) $filialEntity->getId());

        $totalEtiquetas = count($volumes);

        foreach ($volumes as $volume) {

            $existeItensPendentes = true;
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('id' => $volume['COD_MAPA_SEPARACAO_EMB_CLIENTE'], 'ultimoVolume' => 'S'));
            if (isset($mapaSeparacaoEmbaladoEn) && !empty($mapaSeparacaoEmbaladoEn)) {
                $existeItensPendentes = false;
            }

            $this->AddPage();
            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 15);
            $impressao = utf8_decode(substr($pessoaEntity->getNome()."\n",0,20));
            $this->MultiCell(110, 6, $impressao, 0, 'L');
            $this->Line(0,7,80,7);

            $this->SetFont('Arial', 'B', 12.5);
            $impressao = utf8_decode('CLIENTE: '."\n");
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 11.3);
            $impressao = utf8_decode(substr($volume['NOM_PESSOA']."\n",0,30));
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr($volume['DSC_ENDERECO'].', '.$volume['NUM_ENDERECO'] ."\n",0,50));
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $impressao = utf8_decode($volume['NOM_BAIRRO'].'  -  '.$volume['NOM_LOCALIDADE'].'  -  '.$volume['COD_REFERENCIA_SIGLA']);
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $this->Line(0,30,110,30);

            $this->SetFont('Arial', '', 10);

            if ($fechaEmbaladosNoFinal)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$totalEtiquetas;
            else if ($existeItensPendentes == false)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];
            else
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];

            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 20);
            $impressao = utf8_decode(substr($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']."\n",0,30));
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 20);
            $impressao = utf8_decode(substr('PEDIDO: '.$volume['COD_PEDIDO']."\n",0,30));
            $this->MultiCell(110, 20, $impressao, 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 45, 35 , 40, 13);
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 75, 0, 23, 12);

        }
    }

    private function bodyExpedicaoModelo4($volumes,$mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filialEntity = $em->getReference('wms:Filial', (int) $deposito->getFilial()->getId());
        $pessoaEntity = $em->getReference('wms:Pessoa', (int) $filialEntity->getId());

        $totalEtiquetas = count($volumes);

        foreach ($volumes as $volume) {

            $existeItensPendentes = true;
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('id' => $volume['COD_MAPA_SEPARACAO_EMB_CLIENTE'], 'ultimoVolume' => 'S'));
            if (isset($mapaSeparacaoEmbaladoEn) && !empty($mapaSeparacaoEmbaladoEn)) {
                $existeItensPendentes = false;
            }

            $this->AddPage();
            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 15);
            $impressao = utf8_decode(substr($pessoaEntity->getNome()."\n",0,20));
            $this->MultiCell(110, 6, $impressao, 0, 'L');
            $this->Line(0,7,80,7);

            $this->SetFont('Arial', 'B', 16);
            $impressao = utf8_decode(substr($volume['DSC_PLACA_CARGA'],0,18)."\n");
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 11.3);
            $impressao = utf8_decode(substr($volume['NOM_PESSOA']."\n",0,30));
            $this->MultiCell(110, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 10);
            $impressao = utf8_decode(substr($volume['DSC_ENDERECO'].', '.$volume['NUM_ENDERECO'] ."\n",0,50));
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $impressao = utf8_decode($volume['NOM_BAIRRO'].'  -  '.$volume['NOM_LOCALIDADE'].'  -  '.$volume['COD_REFERENCIA_SIGLA']);
            $this->MultiCell(110, 5, $impressao, 0, 'L');
            $this->Line(0,30,110,30);

            $this->SetFont('Arial', 'B', 10);

            if ($fechaEmbaladosNoFinal)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$totalEtiquetas;
            else if ($existeItensPendentes == false)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];
            else
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];

            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->SetXY(2,39);
            $this->SetFont('Arial', 'B', 20);
            $impressao = utf8_decode(substr("PEDIDO: \n".$volume['COD_PEDIDO']."\n",0,30));
            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 40, 42 , 60, 16);
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 75, 0, 23, 12);

        }
    }

    private function bodyExpedicaoModelo5($volumes)
    {

        foreach ($volumes as $volume) {
            $this->AddPage();
            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr($volume['NOM_PESSOA']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('ROTA: '.$volume['DSC_ITINERARIO']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('PLACA: '.$volume['DSC_PLACA_CARGA']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 10);
            $impressao = utf8_decode(substr('CARGA: '.$volume['COD_CARGA_EXTERNO']."\n",0,20));
            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 7);
            $this->MultiCell(110, 3.9, "VOLUME: $volume[POS_VOLUME]/$volume[COUNT_VOLUMES]", 0, 'L');

            $this->SetFont('Arial', '', 7);
            $this->MultiCell(110, 3.9, "VOL. ENTREGA: $volume[POS_ENTREGA] de $volume[TOTAL_ENTREGA]", 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 35, 20 , 33, 9.5);
        }
    }

    private function bodyExpedicaoModelo6($volumes)
    {
        foreach ($volumes as $volume) {

            $imgW = 45;
            $imgH = 17;
            $this->AddPage();
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 3, 1, $imgW-1, $imgH);
            $this->Cell($imgW, $imgH+1, '', 1);

            $this->Cell(59, 18, '',1,1);

            $this->SetXY(48.5,3);
            $this->SetFont('Arial', null, 10);
            $this->Cell(15, 5, 'PEDIDO',0,1);
            $this->SetXY(48.5,9);
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(15, 4, utf8_decode($volume['COD_PEDIDO']));

            $this->SetXY(48.5,15);
            $this->SetFont('Arial', null, 12);
            $this->Cell(27, 4, "SEQUENCIA:");
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(15, 4, "$volume[SEQ_ROTA]-$volume[SEQ_PRACA]");

            $this->SetxY(75,1);
            $this->SetFont('Arial', '', 13);
            $this->MultiCell(25, 8, 'VOLUME', 0, 'L');
            $this->SetxY(80,6);
            $this->SetFont('Arial', 'B', 17);
            $impressao = ($volume['IND_ULTIMO_VOLUME'] === 'S') ? "$volume[NUM_SEQUENCIA] de $volume[NUM_SEQUENCIA]" : $volume['NUM_SEQUENCIA'];
            $this->MultiCell(30, 10, $impressao, 0, 'L');

            $this->SetXY(88,14);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(20, 5, $volume['DSC_BOX']);

            $this->SetY(20);
            $this->Cell(104, 14, '',1);

            $this->SetXY(5,22);
            $this->SetFont('Arial', null, 11);
            $this->Cell(15, 4, utf8_decode('CLIENTE:'));

            $this->SetXY(5,28);
            $this->SetFont('Arial', 'B', 13);
            $this->MultiCell(100, 4, $this->SetStringByMaxWidth($volume['NOM_PESSOA'], 100), 0, 'L');

            $this->SetY(34.5);
            $this->Cell(104, 16, '',1);

            $this->SetXY(5,36.5);
            $this->SetFont('Arial', null, 9);
            $this->Cell(15, 4, utf8_decode('ENDEREÇO:'));

            $this->SetXY(5,41);
            $this->SetFont('Arial', 'B', 11);
            $this->MultiCell(100, 4, $this->SetStringByMaxWidth(utf8_decode("$volume[COD_REFERENCIA_SIGLA] - $volume[NOM_LOCALIDADE]"),100), 0, 'L');
            $this->SetXY(5,45);
            $this->MultiCell(100, 4, $this->SetStringByMaxWidth(utf8_decode("$volume[DSC_ENDERECO] nº: $volume[NUM_ENDERECO] "), 100), 0, 'L');

            $this->SetY(51);
            $this->Cell(104, 22, '',1);
            $this->Line(55,51,55,73);

            $this->SetXY(8,52);
            $this->SetFont('Arial', "B", 10);
            $this->MultiCell(100, 4, utf8_decode("VOLUME FECHADO EM:"));
            $this->SetXY(8,56.5);
            $this->SetFont('Arial', null, 12);
            $this->MultiCell(100, 4, $volume['DTH_FECHAMENTO']);
            $this->SetXY(15,64);
            $this->SetFont('Arial', "B", 10);
            $this->MultiCell(100, 5, "CONFERENTE", 0, 'L');
            $this->SetXY(3,68);
            $this->SetFont('Arial', null, 10);
            $this->MultiCell(52, 5, utf8_decode($volume['CONFERENTE']), 0, 'C');

            $this->SetXY(57,53);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(12, 4, "ROTA:");
            $this->SetFont('Arial', null, 10);
            $this->Cell(38, 4, $this->SetStringByMaxWidth($volume["NOME_ROTA"], 38));

            $this->SetXY(57,58);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(14, 4, utf8_decode("PRAÇA:"));
            $this->SetFont('Arial', null, 10);
            $this->Cell(36, 4, $this->SetStringByMaxWidth($volume["NOME_PRACA"], 36));


            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 56, 63 , 50, 12);

        }
    }	

    private function bodyExpedicaoModelo7($volumes)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        foreach ($volumes as $volume) {

            $this->AddPage();
            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 15);
            $impressao = str_replace(array('0','1','2','3','4','5','6','7','8','9','-'),'',utf8_decode($volume['DSC_PLACA_CARGA']));
            $this->MultiCell(70, 9, 'TRANSP.: '.$impressao, 0, 'L');

            $this->SetY(15);
            $this->SetFont('Arial', 'B', 13);
            $impressao = utf8_decode($volume['NOM_PESSOA']);
            $this->MultiCell(70, 5, 'CLIENTE: '.$impressao, 0, 'L');

            $imgW = 22;
            $imgH = 22;
            $posX = 73;
            $posY = 5;
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', $posX, $posY, $imgW, $imgH);
            $this->SetXY($posX,$posY);
            $this->MultiCell($imgW, $imgH+1, '', 1);

            $this->SetY(32);
            $impressao = "$volume[NOM_LOCALIDADE] - ($volume[COD_REFERENCIA_SIGLA])";
            if ($this->GetStringWidth($impressao) <= 95) {
                $this->Cell(95, 5, $this->SetStringByMaxWidth(utf8_decode($impressao), 95));
            } else {
                $this->Cell(80, 5, $this->SetStringByMaxWidth(utf8_decode("$volume[NOM_LOCALIDADE]"), 80));
                $this->Cell(20, 5, $this->SetStringByMaxWidth("($volume[COD_REFERENCIA_SIGLA])" , 20));
            }

            $this->SetY(37);
            $x = $this->getX();
            $this->SetFont('Arial', 'B', 13);
            $impressao = 'PEDIDO: ';
            $this->MultiCell(110, 7.5, $impressao, 0, 'L');

            $this->SetY(36);
            $this->setX($x + 20);
            $this->SetFont('Arial', 'B', 19);
            $impressao = $volume['COD_CARGA_EXTERNO'];
            $this->MultiCell(110, 7.5, $impressao, 0, 'L');

            $this->SetY(36);
            $this->setX($x + 42);
            $this->SetFont('Arial', 'B', 13);
            $impressao = ' - EXP.:' . $volume['COD_EXPEDICAO'];
            $this->MultiCell(110, 7.5, $impressao, 0, 'L');

            $this->SetFont('Arial', '', 14);
            $dscSeq = ($volume['IND_ULTIMO_VOLUME'] === 'S') ? "$volume[POS_ENTREGA] de $volume[POS_ENTREGA]" : $volume['POS_ENTREGA'];
            $this->MultiCell(110, 10, "VOLUME: $dscSeq", 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 35, 52 , 60, 16);

            if ($this->h == 150) {
                $this->SetFont('Arial', 'B', 9);

                $volume['produtos'] = $em->getRepository('wms:Expedicao\MapaSeparacaoEmbalado')->getProdutosByMapaEmbalado($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']);
                $this->SetY(70);
                $i = 0;

                foreach ($volume['produtos'] as $produto) {
                    if ($i == 20) break;
                    $i++;
                    $this->Cell(16, 3.7, utf8_decode($produto['codProduto']));
                    $this->Cell(71, 3.7, $this->SetStringByMaxWidth(utf8_decode($produto['descricao']), 70));
                    $this->Cell(11, 3.7, $produto['quantidade'], 0, 1);
                }
            }
        }
    }

    private function bodyExpedicaoModelo8($volumes)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        foreach ($volumes as $volume) {


            $this->SetFont('Arial', 'B', 20);
            //coloca o cod barras
            $this->AddPage();

            //monta o restante dos dados da etiqueta
            $this->SetFont('Arial', 'B', 15);
    //            $impressao = utf8_decode("EXP: $volume[expedicao] CLI: $volume[quebra]\n");
    //            $volume['quebra'] = "TOMAZ GOMIDE NUNES - PREÇO REVENDA";
            $impressao = utf8_decode(substr("$volume[NOM_PESSOA]\n",0,50));
            $this->MultiCell(110, 4.1, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 13);
            $impressao = utf8_decode("Pedido:");
            $this->SetY(15);
            $this->SetX(82);
            $this->MultiCell(100, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 16);
            $impressao = utf8_decode("\n$volume[COD_PEDIDO]");
            $this->SetY(17);
            $this->SetX(82);
            $this->MultiCell(100, 6, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 7);
            $impressao = utf8_decode("Código                          Produto                                                    Qtd.\n");
            $this->SetX(5);
            $this->SetY(10);
            $this->MultiCell(100, 3.9, $impressao, 0, 'L');

            //linha horizontal entre codigo produto quantidade e a descricao dos dados
            $this->Line(0,14,150,14);
            //linha vertical entre o codigo e a descrição do produto
            $this->Line(19,14,19,100);
            //linha vertical entre a descrição do produto e a quantidade
            $this->Line(73,14,73,100);
            //linha vertical entre a quantidade e o numero do pedido
            $this->Line(82,14,82,80);
            //linha horizontal entre o numero do pedido e o cod de barras
            $this->Line(82,30,150,30);

            $y = 12;
            $this->SetFont('Arial', 'B', 7);

            $volume['produtos'] = $em->getRepository('wms:Expedicao\MapaSeparacaoEmbalado')->getProdutosByMapaEmbalado($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']);

            foreach ($volume['produtos'] as $produtos) {

                $impressao = utf8_decode($produtos['codProduto']);
                $this->SetX(3);
                $this->SetY($y);
                $this->MultiCell(150, $y, $impressao, 0, 'L');

                $impressao = utf8_decode(substr($produtos['descricao'], 0, 33));
                $this->SetXY(19,$y);
                $this->MultiCell(150, $y, $impressao, 0, 'L');

                $impressao = $produtos['quantidade'];
                $this->SetXY(75,$y);
                $this->Cell(75,$y, $impressao, 0, 'L');

                $y = $y + 2;
            }
            $this->Image(APPLICATION_PATH . '/../public/img/premium-etiqueta.gif', 83, 35, 20,5);

            $angle    = 0;
            $x        = 94;
            $y        = 46;

            $type     = 'code128';
            $black    = '000000';
            $codBarras =
            Barcode::fpdf($this,$black,$x,$y,$angle,$type,array('code'=>"$volume[COD_MAPA_SEPARACAO_EMB_CLIENTE]" . 0),0.40,7);
        }
    }

    private function bodyExpedicaoModelo9($volumes)
    {
        foreach ($volumes as $volume) {

            $imgW = 45;
            $imgH = 12;
            $this->AddPage();
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 3, 2, $imgW - 1, $imgH);
            $this->Cell($imgW, $imgH + 1, '', 1);

            $this->Cell(59, $imgH + 1, '', 1, 1);

            $this->SetXY(48.5, 3);
            $this->SetFont('Arial', null, 10);
            $this->Cell(15, 5, 'PEDIDO', 0, 1);
            $this->SetXY(48.5, 9);
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(15, 4, utf8_decode($volume['COD_PEDIDO']));

            $this->SetxY(81, 1);
            $this->SetFont('Arial', '', 13);
            $this->MultiCell(25, 8, 'VOLUME', 0, 'L');

            $this->SetxY(72, 6);
            $this->SetFont('Arial', 'B', 17);
            $dscSeq = ($volume['IND_ULTIMO_VOLUME'] === 'S') ? "$volume[POS_ENTREGA] de $volume[POS_ENTREGA]" : $volume['POS_ENTREGA'];
            $this->MultiCell(40, 10, $dscSeq, 0, 'C');

            $this->SetY(15);
            $this->Cell(104, 12, '', 1);

            $this->SetXY(5, 17);
            $this->SetFont('Arial', null, 11);
            $this->Cell(15, 4, utf8_decode('CLIENTE:'));

            $this->SetXY(5, 21.5);
            $this->SetFont('Arial', 'B', 12);
            $this->MultiCell(100, 4, $this->SetStringByMaxWidth($volume['NOM_PESSOA'], 100), 0, 'L');

            $this->SetY(27.5);
            $this->Cell(104, 14, '', 1);

            $this->SetXY(5, 28.5);
            $this->SetFont('Arial', null, 9);
            $this->Cell(20.5, 4, utf8_decode("EXPEDIÇÃO:"));
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(35, 4, utf8_decode("$volume[COD_EXPEDICAO]"));
            $this->SetFont('Arial', null, 9);
            $this->Cell(13, 4, utf8_decode("CARGA:"));
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(35, 4, utf8_decode("$volume[COD_CARGA_EXTERNO]"));

            $this->SetXY(5, 35);
            $this->SetFont('Arial', 'B', 13);
            $this->MultiCell(100, 4, $this->SetStringByMaxWidth(utf8_decode("TRANSP: $volume[DSC_PLACA_CARGA]"), 100), 0, 'L');

            $this->SetY(42);
            $this->Cell(104, 11, '', 1);

            $this->SetXY(5, 43.5);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(12, 4, "CIDADE:");
            $this->SetXY(5, 48);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(50, 4, $this->SetStringByMaxWidth(utf8_decode($volume['NOM_LOCALIDADE']), 50));

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 56, 42.5, 50, 11);
        }
    }

    private function bodyExpedicaoModelo10($volumes, $mapaSeparacaoEmbaladoRepo)
    {
        foreach ($volumes as $volume) {

            $existeItensPendentes = empty($mapaSeparacaoEmbaladoRepo->findOneBy(array('id' => $volume['COD_MAPA_SEPARACAO_EMB_CLIENTE'], 'ultimoVolume' => 'S')));

            $imgW = 18;
            $imgH = 17;
            $this->AddPage();
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 8, 1, $imgW-1, $imgH);
            $this->Cell($imgW + 10, $imgH+1, '', 1);

            $this->Cell(66.5, 18, '',1,1);

            $xBox1 = 33.5;

            $this->SetXY($xBox1,3);
            $this->SetFont('Arial', null, 10);
            $this->Cell(15, 5, 'PEDIDO',0,1);
            $this->SetXY($xBox1,9);
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(15, 4, utf8_decode($volume['COD_PEDIDO']));

            $this->SetXY($xBox1,15);
            $this->SetFont('Arial', null, 12);
            $this->Cell(27, 4, "SEQUENCIA:");
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(15, 4, "$volume[SEQ_ROTA]-$volume[SEQ_PRACA]");

            $this->SetXY(88,14);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(20, 5, $volume['DSC_BOX']);

            $this->SetY(20);
            $this->Cell(94.5, 14, '',1);

            $this->SetXY(4,22);
            $this->SetFont('Arial', null, 11);
            $this->Cell(15, 4, utf8_decode('CLIENTE:'));

            $this->SetXY(4,28);
            $this->SetFont('Arial', 'B', 13);
            $this->MultiCell(90, 4, $this->SetStringByMaxWidth($volume['NOM_PESSOA'], 90), 0, 'L');

            $this->SetY(34.5);
            $this->Cell(94.5, 20, '',1);

            $this->SetXY(4,36.5);
            $this->SetFont('Arial', null, 9);
            $this->Cell(15, 4, utf8_decode('ENDEREÇO:'));

            $this->SetXY(4,42);
            $this->SetFont('Arial', 'B', 11);
            $this->MultiCell(100, 4, $this->SetStringByMaxWidth(utf8_decode("$volume[COD_REFERENCIA_SIGLA] - $volume[NOM_LOCALIDADE]"),100), 0, 'L');
            $this->SetXY(4,48);
            $this->MultiCell(90, 4, $this->SetStringByMaxWidth(utf8_decode("$volume[DSC_ENDERECO] nº: $volume[NUM_ENDERECO] "), 90), 0, 'L');

            $this->SetY(55);
            $this->Cell(94.5, 42, '',1);

            $this->SetXY(2,56);
            $this->SetFont('Arial', "B", 10);
            $this->MultiCell(95, 5, "CONFERENTE", 0, 'C');
            $this->SetXY(1,61);
            $this->SetFont('Arial', null, 10);
            $this->MultiCell(95, 5, utf8_decode($volume['CONFERENTE']), 0, 'C');

            $this->SetXY(5,71);
            $this->SetFont('Arial', 'B', 13);
            $this->Cell(19, 4, "PLACA:");
            $this->SetFont('Arial', null, 14);
            $this->Cell(38, 4, $volume["DSC_PLACA_CARGA"]);

            $this->SetXY(54,69);
            $this->SetFont('Arial', "B", 10);
            $this->MultiCell(100, 4, utf8_decode("VOLUME FECHADO EM:"));
            $this->SetXY(55,74.5);
            $this->SetFont('Arial', null, 12);
            $this->MultiCell(100, 4, $volume['DTH_FECHAMENTO']);

            $this->Line(3,81,97.5,81);

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 5, 83 , 50, 12);

            $this->SetxY(65,81);
            $this->SetFont('Arial', '', 14);
            $this->MultiCell(25, 8, 'VOLUME', 0, 'L');

            if (!$existeItensPendentes)
                $impressao = $volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];
            else
                $impressao = $volume['NUM_SEQUENCIA'];
            $this->SetxY(67,87);
            $this->SetFont('Arial', 'B', 22);
            $this->MultiCell(20, 10, $impressao, 0, 'C');
        }
    }

    private function bodyExpedicaoModelo11($volumes)
    {

        foreach ($volumes as $volume) {

            $imgW = 14;
            $imgH = 5.5;
            $this->AddPage();
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 3, 2, $imgW - 1, $imgH);

            $this->SetFont('Arial', 'B', 8);
            $impressao = "CARGA: $volume[COD_CARGA_EXTERNO]";
            $this->Cell(92, 3, $impressao, 0, 1,'R');
            $this->setX(17.5);
            $impressao  = utf8_decode("TRANSP.: $volume[DSC_PLACA_CARGA]");
            $this->Cell(50, 3, $impressao, 0, 1,'L');
            $impressao = utf8_decode("CLIENTE: $volume[NOM_PESSOA]");
            $this->Cell(50, 3, $impressao, 0, 1,'L');

            $this->SetFont('Arial', '', 7);
            $impressao  = utf8_decode("ENTREGA: $volume[COD_PEDIDO] - $volume[DSC_ENDERECO], N $volume[NUM_ENDERECO], $volume[NOM_LOCALIDADE] - $volume[COD_REFERENCIA_SIGLA]");
            $this->Cell(60, 3, $impressao, 0, 1,'L');

            $this->SetY(-22);
            $this->Cell(20, 10, '', 0, 1, "L");
            $this->SetFont('Arial','B',8);
            $dscSeq = ($volume['IND_ULTIMO_VOLUME'] === 'S') ? "$volume[POS_ENTREGA] de $volume[POS_ENTREGA]" : $volume['POS_ENTREGA'];
            $this->Cell(20, 3, "Volume: $dscSeq", 0, 1,'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 25, 22,50,12);
        }
    }
}
