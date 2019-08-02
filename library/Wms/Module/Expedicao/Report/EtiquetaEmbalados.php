<?php

namespace Wms\Module\Expedicao\Report;

use Wms\Util\Barcode\eFPDF,
    Wms\Util\CodigoBarras;

class EtiquetaEmbalados extends eFPDF
{

    public function imprimirExpedicaoModelo($volumePatrimonio,$mapaSeparacaoEmbaladoRepo,$modeloEtiqueta)
    {

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(3, 1.5, 0);
        $this->SetAutoPageBreak(0,0);

        switch ($modeloEtiqueta) {
            case 1:
                //LAYOUT CASA DO CONFEITEIRO
                self::bodyExpedicaoModelo1($volumePatrimonio,$mapaSeparacaoEmbaladoRepo);
                break;
            case 2:
                //LAYOUT WILSO
                self::bodyExpedicaoModelo2($volumePatrimonio,$mapaSeparacaoEmbaladoRepo);
                break;
            case 3:
                //LAYOUT ABRAFER
                self::bodyExpedicaoModelo3($volumePatrimonio,$mapaSeparacaoEmbaladoRepo);
                break;
            case 4:
                //LAYOUT HIDRAU
                self::bodyExpedicaoModelo4($volumePatrimonio,$mapaSeparacaoEmbaladoRepo);
                break;
            case 5:
                //LAYOUT ETIQUETAS AGRUPADAS BASEADO MODELO 1
                self::bodyExpedicaoModelo5($volumePatrimonio);
                break;
            default:
                self::bodyExpedicaoModelo1($volumePatrimonio,$mapaSeparacaoEmbaladoRepo);
                break;

        }
        $this->Output('Volume-Embalado.pdf','I');
        exit;
    }

    private function bodyExpedicaoModelo1($volumes,$mapaSeparacaoEmbaladoRepo)
    {

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

            $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'];
            if ($existeItensPendentes == false)
                $impressao = 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'];

            $this->MultiCell(110, 3.9, $impressao, 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 6, 20 , 33, 9.5);
        }
    }

    private function bodyExpedicaoModelo2($volumes,$mapaSeparacaoEmbaladoRepo)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filialEntity = $em->getReference('wms:Filial', (int) $deposito->getFilial()->getId());
        $pessoaEntity = $em->getReference('wms:Pessoa', (int) $filialEntity->getId());

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
            $impressao = $existeItensPendentes == false ? 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'] : 'VOLUME: '.$volume['NUM_SEQUENCIA'];
            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->SetFont('Arial', 'B', 22);
            $impressao = utf8_decode(substr($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']."\n",0,30));
            $this->MultiCell(110, 6, $impressao, 0, 'L');


            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 50, 47 , 45, 13);
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 71, 0, 35, 30);

        }
    }

    private function bodyExpedicaoModelo3($volumes,$mapaSeparacaoEmbaladoRepo)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filialEntity = $em->getReference('wms:Filial', (int) $deposito->getFilial()->getId());
        $pessoaEntity = $em->getReference('wms:Pessoa', (int) $filialEntity->getId());

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
            $impressao = $existeItensPendentes == false ? 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'] : 'VOLUME: '.$volume['NUM_SEQUENCIA'];
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

    private function bodyExpedicaoModelo4($volumes,$mapaSeparacaoEmbaladoRepo)
    {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filialEntity = $em->getReference('wms:Filial', (int) $deposito->getFilial()->getId());
        $pessoaEntity = $em->getReference('wms:Pessoa', (int) $filialEntity->getId());

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
            $impressao = $existeItensPendentes == false ? 'VOLUME: '.$volume['NUM_SEQUENCIA'].'/'.$volume['NUM_SEQUENCIA'] : 'VOLUME: '.$volume['NUM_SEQUENCIA'];
            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->SetXY(2,39);
            $this->SetFont('Arial', 'B', 20);
            $impressao = utf8_decode(substr("PEDIDO: \n".$volume['COD_PEDIDO']."\n",0,30));
            $this->MultiCell(110, 10, $impressao, 0, 'L');

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 40, 42 , 60, 16);
            $this->Image(APPLICATION_PATH . '/../public/img/logo_cliente.jpg', 75, 0, 23, 12);

        }
    }

    /*
    protected function layoutModelo12($volumes)
    {
        $this->SetMargins(3, 1.5, 0);

        $strReimpressao = "";
        if ($reimpressao == true) {$strReimpressao = "Reimpressão";}

        $this->AddPage();
        $this->currentEtq = $posEtiqueta;
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
        $impressao = $posEtiqueta . '/' . $this->total;
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
    */

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

            $this->Image(@CodigoBarras::gerarNovo($volume['COD_MAPA_SEPARACAO_EMB_CLIENTE']), 6, 20 , 33, 9.5);
        }
    }
}
