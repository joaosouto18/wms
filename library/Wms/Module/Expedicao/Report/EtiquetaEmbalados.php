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
}
