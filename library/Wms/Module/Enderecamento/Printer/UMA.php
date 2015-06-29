<?php

namespace Wms\Module\Enderecamento\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Enderecamento\Palete;

class UMA extends Pdf
{

    public function imprimir(array $params = array(),$modelo)
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 7, 0);
        $ProdutoRepository   = $em->getRepository('wms:Produto');

        $codProduto     = $params['codProduto'];
        $grade          = $params['grade'];
        $idRecebimento  = $params['idRecebimento'];

        $produtoEn  = $ProdutoRepository->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));

        if ($produtoEn == null) {
            $codProduto = '0'.$codProduto;
            $produtoEn  = $ProdutoRepository->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));
        }

        $this->layout($params['paletes'], $produtoEn,$modelo);
        $this->Output('UMA-'.$idRecebimento.'-'.$codProduto.'.pdf','D');
    }

    protected function layout($paletes,$produtoEn,$modelo)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $PaleteRepository */
        $PaleteRepository   = $em->getRepository('wms:Enderecamento\Palete');

        $font_size = 55;
        $line_width = 300;

        foreach($paletes as $palete) {
            if (isset($palete['picking'])) {
                $picking = $palete['picking'];
            } else {
                $picking = $this->getPicking($produtoEn);
            }

            if ($modelo == 1) {
                $this->layout01($palete,$produtoEn,$font_size,$line_width, $picking);
            }else{
                $this->layout02($palete,$produtoEn,$font_size,$line_width, $picking);
            }
            $paleteEn = $PaleteRepository->find($palete['idUma']);
            if ($paleteEn != NULL ) {
                $this->Image(@CodigoBarras::gerarNovo($paleteEn->getId()), null, null,170,40);
                if ($paleteEn->getDepositoEndereco() != null && $paleteEn->getCodStatus() == Palete::STATUS_RECEBIDO) {
                    $paleteEn->setCodStatus(Palete::STATUS_EM_ENDERECAMENTO);
                }
                $paleteEn->setImpresso('S');
                $em->persist($paleteEn);
            }
        }
        $em->flush();
    }

    function Footer()
    {
        // Go to 1.5 cm from bottom
        $this->SetY(-15);
        // Select Arial italic 8
        $this->SetFont('Arial','I',8);
        // Print centered page number
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo(),0,0,'C');
        $this->Cell(-30,0,utf8_decode(date('d/m/Y')." às ".date('H:i')),0,0,'C');
    }

    public function layout02($palete, $produtoEn, $font_size, $line_width, $enderecoPicking){
        $this->AddPage();

        $codigoProduto = $produtoEn->getId();
        $descricaoProduto = $produtoEn->getDescricao();

        //$descricaoProduto = "PANELA ELETRICA DE ARROZ 5X BR EPV892 PROMOCI 220V";

        if (strlen($descricaoProduto) >= 20) {
            $font_size = 48;
        }
        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 15, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35,40,"",0,0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(165,20,utf8_decode("Picking $enderecoPicking"),0,0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25,20,"Qtd",0,0);

        $this->SetFont('Arial', 'B', 72);
        $this->Cell(75,20,$palete['qtd'],0,1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55,20,utf8_decode("Endereço"),0,0);

        $this->SetFont('Arial', 'B', 72);
        $this->Cell(95,25,$palete['endereco'],0,1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55,45,utf8_decode("Prod"),0,0);

        $this->SetFont('Arial', 'B', 132);
        $this->Cell(95,45,$codigoProduto,0,1);

    }

    public function layout01($palete, $produtoEn, $font_size, $line_width, $enderecoPicking){
        $this->AddPage();

        $descricaoProduto = $produtoEn->getId().'-'.$produtoEn->getDescricao();


        if (strlen($descricaoProduto) >= 42) {
            $font_size = 38;
        }
        
        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 20, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35,40,"",0,0);

        $this->SetFont('Arial', 'B', 72);
        $this->Cell(165,40,$produtoEn->getGrade(),0,0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25,40,"Qtd",0,0);

        $this->SetFont('Arial', 'B', 72);
        $this->Cell(75,40,$palete['qtd'],0,1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(75,20,utf8_decode("Picking $enderecoPicking"),0,1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55,40,utf8_decode("Endereço"),0,0);

        $this->SetFont('Arial', 'B', 72);
        $this->Cell(95,40,$palete['endereco'],0,1);
    }

    private function getPicking($produtoEn){
        $enderecoPicking = null;

        if (count($produtoEn->getVolumes()) > 0) {
            $volumes = $produtoEn->getVolumes();
            if (isset($volumes[0])) {
                if ($volumes[0]->getEndereco() != null) {
                    $enderecoPicking = $volumes[0]->getEndereco()->getDescricao();
                }
            }
        }
        if (count($produtoEn->getEmbalagens()) > 0) {
            $embalagens = $produtoEn->getEmbalagens();
            if ($embalagens[0]->getEndereco() != null) {
                $enderecoPicking = $embalagens[0]->getEndereco()->getDescricao();
            }
        }

        if ($enderecoPicking == NULL) {
            $enderecoPicking = "Não Cadastrado";
        }
        return $enderecoPicking;
    }

}
