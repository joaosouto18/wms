<?php

namespace Wms\Module\Expedicao\Report;

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
                self::bodyExpedicaoModelo6($volumePatrimonio);
                break;
            case 7:
                //LAYOUT MBLED
                self::bodyExpedicaoModelo7($volumePatrimonio, $mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal);
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
        $totalEtiquetas = count($volumes);

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

            $this->SetxY(84,1);
            $this->SetFont('Arial', '', 13);
            $this->MultiCell(25, 8, 'VOLUME', 0, 'L');
            $this->SetxY(90,6);
            $this->SetFont('Arial', 'B', 17);
            $this->MultiCell(40, 10, "$volume[NUM_SEQUENCIA]/$totalEtiquetas", 0, 'L');

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

    private function bodyExpedicaoModelo7($volumes,$mapaSeparacaoEmbaladoRepo, $fechaEmbaladosNoFinal)
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
            $this->SetFont('Arial', 'B', 18);
            $impressao = utf8_decode(substr($pessoaEntity->getNome()."\n",0,50));
            $this->MultiCell(110, 9, $impressao, 0, 'L');
            $this->Line(0,10,130,10);
            $impressao = str_replace(array('0','1','2','3','4','5','6','7','8','9','-'),'',substr($volume['DSC_PLACA_CARGA'],0,16))."\n";
            $this->MultiCell(110, 9, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 13);
            $impressao = utf8_decode($volume['NOM_PESSOA']."\n");
            $this->MultiCell(110, 9, $impressao, 0, 'L');

            $impressao = utf8_decode(substr($volume['DSC_ENDERECO'].', '.$volume['NUM_ENDERECO'] ."\n",0,50));
            $this->MultiCell(110, 7.5, $impressao, 0, 'L');
            $impressao = utf8_decode($volume['NOM_BAIRRO'].'  -  '.$volume['NOM_LOCALIDADE'].'  -  '.$volume['COD_REFERENCIA_SIGLA']);
            $this->MultiCell(110, 7.5, $impressao, 0, 'L');

            $this->Line(0,42.5,130,42.5);
            $impressao = utf8_decode('PEDIDO: '.$volume['COD_CARGA_EXTERNO']) . ' - EXP.:' . $volume['COD_EXPEDICAO'];
            $this->MultiCell(110, 7.5, $impressao, 0, 'L');
            $this->Line(0,50,130,50);

            $this->SetFont('Arial', '', 14);
            if ($fechaEmbaladosNoFinal)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$totalEtiquetas;
            else if ($existeItensPendentes == false)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];
            else
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];

            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 14);
            $impressao = utf8_decode($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']."\n");
            $this->MultiCell(110, 6, $impressao, 0, 'L');


            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 35, 52.5 , 60, 20);

        }
    }
}
