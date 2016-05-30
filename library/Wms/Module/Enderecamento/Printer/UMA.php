<?php

namespace Wms\Module\Enderecamento\Printer;

use
    Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Enderecamento\Palete;
use Wms\Domain\Entity\NotaFiscal\Item;

class UMA extends Pdf
{

    public function imprimirPaletes($paletes, $modelo) {
        ini_set('max_execution_time', 3000);
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $paleteRepository = $em->getRepository('wms:Enderecamento\Palete');
        $embalagemRepo    = $em->getRepository("wms:Produto\Embalagem");
        $volumeRepo       = $em->getRepository("wms:Produto\Volume");

        $this->SetMargins(7, 7, 0);

        $palete = '';
        foreach ($paletes as $value) {
            if ($value != end($paletes)) {
                $palete .= $value.',';
            } else {
                $palete .= $value;
            }
        }

        $paletes = explode(',',$palete);
        foreach ($paletes as $codPalete){
            $paleteEn = $paleteRepository->findOneBy(array('id'=>$codPalete));

            $idRecebimento  = $paleteEn->getRecebimento()->getId();
            $produtos = $paleteEn->getProdutos();
            $produtoEn = $produtos[0]->getProduto();

            $dscEndereco = "";
            if ($paleteEn->getDepositoEndereco() != null) {
                $dscEndereco =  $paleteEn->getDepositoEndereco()->getDescricao();
            }
            ;

            $dadosPalete = array();
            $dadosPalete['endereco'] = $dscEndereco;
            $dadosPalete['idUma']    = $paleteEn->getId();
            $dadosPalete['qtd']      = $produtos[0]->getQtd();

            if (($produtos[0]->getCodProdutoEmbalagem() == NULL)) {
                $embalagemEn = $volumeRepo->findOneBy(array('id'=> $produtos[0]->getCodProdutoVolume()));
            } else {
                $embalagemEn = $embalagemRepo->findOneBy(array('id'=> $produtos[0]->getCodProdutoEmbalagem()));
            }
            if ($embalagemEn->getEndereco() != null) {
                $dadosPalete['picking'] = $embalagemEn->getEndereco()->getDescricao();
            }

            $paletesArray = array(0=>$dadosPalete);

            $param = array();
            $param['idRecebimento'] = $idRecebimento;
            $param['codProduto']    = $produtoEn->getId();
            $param['grade']         = $produtoEn->getGrade();
            $param['paletes']       = $paletesArray;
            $param['dataValidade']  = null;

            $this->layout($param['paletes'], $produtoEn,$modelo,$param);
        }
        $this->Output('UMA-'.$idRecebimento.'.pdf','D');

    }

    public function imprimir(array $params = array(),$modelo)
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 7, 0);
        $ProdutoRepository    = $em->getRepository('wms:Produto');
        $notaFiscalRepository = $em->getRepository('wms:NotaFiscal');
        $notaFiscalItemRepository = $em->getRepository('wms:NotaFiscal\Item');

        $codProduto     = $params['codProduto'];
        $grade          = $params['grade'];
        $idRecebimento  = $params['idRecebimento'];

//        $notaFiscalEn = $notaFiscalItemRepository->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade, 'notaFiscal' => array('recebimento' => 3932)));
//        $notaFiscalEn = $notaFiscalItemRepository->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade, 'notaFiscal' => array('id' => 16630, 'recebimento' => null)));
//        var_dump($notaFiscalEn); exit;
        $produtoEn  = $ProdutoRepository->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));

        if ($produtoEn == null) {
            $codProduto = '0'.$codProduto;
            $produtoEn  = $ProdutoRepository->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));
        }

        $this->layout($params['paletes'], $produtoEn, $modelo, $params);
        $this->Output('UMA-'.$idRecebimento.'-'.$codProduto.'.pdf','D');
    }

    protected function layout($paletes,$produtoEn,$modelo,$params = null)
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
                $this->layout01($palete,$produtoEn,$font_size,$line_width, $picking,$params);
            } else if ($modelo == 2) {
                $this->layout02($palete,$produtoEn,$font_size,$line_width, $picking,$params);
            } else {
                $this->layout03($palete,$produtoEn,$font_size,$line_width, $picking,$params);
            }
            $paleteEn = $PaleteRepository->find($palete['idUma']);
            if ($paleteEn != NULL ) {
                if ($modelo == 3) {
                    $this->Image(@CodigoBarras::gerarNovo($paleteEn->getId()), 50, 160,170,40);
                } else {
                    $this->Image(@CodigoBarras::gerarNovo($paleteEn->getId()), null, null,170,40);
                }

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

    public function layout03($palete, $produtoEn, $font_size, $line_width, $enderecoPicking,$params=null){
        $this->AddPage();

        $codigoProduto = $produtoEn->getId();
        $descricaoProduto = $produtoEn->getDescricao();
        $referencia = $produtoEn->getReferencia();

        if (strlen($descricaoProduto) >= 42) {
            $font_size = 36;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 40;
        }

        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 15, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35,40,"",0,0);

        if (isset($params['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->SetFont('Arial', 'B', 40);
            $this->Cell(75,40,utf8_decode("Validade "),0,1);
            $this->SetFont('Arial', 'B', 70);
            $this->Cell(75,-40,utf8_decode("               $dataValidade"),0,1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25,95,"Qtd",0,0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(75,95,$palete['qtd'],0,1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55,-35,utf8_decode("Endereço "),0,0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(105,-35,$palete['endereco'],0,1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55,90,utf8_decode("Prod/Ref.:"),0,0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(105,90,$codigoProduto . " / " .  $referencia,0,1);

    }

    public function layout02($palete, $produtoEn, $font_size, $line_width, $enderecoPicking,$params=null){
        $this->AddPage();

        $codigoProduto = $produtoEn->getId();
        $descricaoProduto = $produtoEn->getDescricao();

        if (strlen($descricaoProduto) >= 42) {
            $font_size = 36;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 40;
        }

        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 15, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35,40,"",0,0);

        $this->SetFont('Arial', 'B', 32);
        if (isset($params['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(75,20,utf8_decode("Picking $enderecoPicking - Validade $dataValidade"),0,1);
        } else {
            $this->Cell(75,20,utf8_decode("Picking $enderecoPicking"),0,1);
        }

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

    public function layout01($palete, $produtoEn, $font_size, $line_width, $enderecoPicking,$params=null){
        $this->AddPage();

        $descricaoProduto = $produtoEn->getId().'-'.$produtoEn->getDescricao();


        if (strlen($descricaoProduto) >= 42) {
            $font_size = 36;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 40;
        }

        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 20, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35,40,"",0,0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(165,40,$produtoEn->getGrade(),0,0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25,40,"Qtd",0,0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(75,40,$palete['qtd'],0,1);

        $this->SetFont('Arial', 'B', 32);

        if (isset($params['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(75,20,utf8_decode("Picking $enderecoPicking - Validade $dataValidade"),0,1);
        } else {
            $this->Cell(75,20,utf8_decode("Picking $enderecoPicking"),0,1);
        }

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
