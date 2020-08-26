<?php

namespace Wms\Module\Enderecamento\Printer;

use Core\Pdf,
    Wms\Util\CodigoBarras,
    Wms\Domain\Entity\Enderecamento\Palete;
use Wms\Domain\Entity\Deposito;
use Wms\Domain\Entity\NotaFiscal\Item;
use Wms\Math;

class UMA extends Pdf {

    public $_modelo = "";

    public function imprimirPaletes($paletes, $modelo) {
        ini_set('max_execution_time', 3000);
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $paleteRepository = $em->getRepository('wms:Enderecamento\Palete');
        $embalagemRepo = $em->getRepository("wms:Produto\Embalagem");
        $volumeRepo = $em->getRepository("wms:Produto\Volume");

        $this->SetMargins(7, 7, 0);

        $palete = '';
        foreach ($paletes as $value) {
            if ($value != end($paletes)) {
                $palete .= $value . ',';
            } else {
                $palete .= $value;
            }
        }

        $paletes = explode(',', $palete);
        foreach ($paletes as $codPalete) {
            $result = $paleteRepository->getPaletesAndVolumes(null,null,null,null,null,null,null,null,null,$codPalete);
            if (empty($result) || $result[0]['QTD_VOL_TOTAL'] > $result[0]['QTD_VOL_CONFERIDO']) {
                continue;
            }
            $paleteEn = $paleteRepository->findOneBy(array('id' => $codPalete));

            $idRecebimento = $paleteEn->getRecebimento()->getId();
            $produtos = $paleteEn->getProdutos();
            $produtoEn = $produtos[0]->getProduto();

            $dscEndereco = "";
            if ($paleteEn->getDepositoEndereco() != null) {
                $dscEndereco = $paleteEn->getDepositoEndereco()->getDescricao();
            }

            $dadosPalete = array();
            $dadosPalete['endereco'] = $dscEndereco;
            $dadosPalete['idUma'] = $paleteEn->getId();
            $dadosPalete['qtd'] = $produtos[0]->getQtd();

            if (($produtos[0]->getCodProdutoEmbalagem() == NULL)) {
                $embalagemEn = $volumeRepo->findOneBy(array('id' => $produtos[0]->getCodProdutoVolume()));
            } else {
                $embalagemEn = $embalagemRepo->findOneBy(array('id' => $produtos[0]->getCodProdutoEmbalagem()));
            }
            if ($embalagemEn->getEndereco() != null) {
                $dadosPalete['picking'] = $embalagemEn->getEndereco()->getDescricao();
            }

            $paletesArray = array(0 => $dadosPalete);

            $param = array();
            $param['idRecebimento'] = $idRecebimento;
            $param['codProduto'] = $produtoEn->getId();
            $param['grade'] = $produtoEn->getGrade();
            $param['paletes'] = $paletesArray;
            $param['dataValidade'] = null;

            $this->layout($param['paletes'], $produtoEn, $modelo, $param);
        }

        $this->Output('UMA-' . $idRecebimento . '.pdf', 'D');
    }

    public function imprimir(array $params = array(), $modelo) {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->SetMargins(7, 7, 0);
        $ProdutoRepository = $em->getRepository('wms:Produto');

        $codProduto = $params['codProduto'];
        $grade = $params['grade'];
        $idRecebimento = $params['idRecebimento'];

        $produtoEn = $ProdutoRepository->findOneBy(array('id' => $codProduto, 'grade' => $grade));

        if ($produtoEn == null) {
            $codProduto = '0' . $codProduto;
            $produtoEn = $ProdutoRepository->findOneBy(array('id' => $codProduto, 'grade' => $grade));
        }

        if (empty($produtoEn)) throw new \Exception("Produto de código $codProduto e grade $grade não foi encontrado!");

        $this->layout($params['paletes'], $produtoEn, $modelo, $params);
        $this->Output('UMA-' . $idRecebimento . '-' . $codProduto . '.pdf', 'D');
    }

    protected function layout($paletes, $produtoEn, $modelo, $params = null) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $PaleteRepository */
        $PaleteRepository = $em->getRepository('wms:Enderecamento\Palete');
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $PaleteProdutoRepository */
        $PaleteProdutoRepository = $em->getRepository('wms:Enderecamento\PaleteProduto');
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepository */
        $estoqueRepository = $em->getRepository('wms:Enderecamento\Estoque');
        /** @var Deposito\EnderecoRepository $depositoEnderecoRepository */
        $depositoEnderecoRepository = $em->getRepository('wms:Deposito\Endereco');

        $font_size = 55;
        $line_width = 300;
        foreach ($paletes as $palete) {
            $PaleteProdutoEntity = $PaleteProdutoRepository->findOneBy(array('uma' => $palete['idUma']));

            $picking = null;
            if (!empty($PaleteProdutoEntity)) {
                $produtoEn = $PaleteProdutoEntity->getProduto();
                $dataValidade = $PaleteProdutoEntity->getValidade();
                if (!is_null($dataValidade)) {
                    $params['dataValidade']['dataValidade'] = $dataValidade->format('Y-m-d H:i:s');
                }
            } else {
                $enderecoEntity = $depositoEnderecoRepository->findOneBy(array('descricao' => $palete['endereco']));
                $estoqueEntity = $estoqueRepository->findOneBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade(), 'depositoEndereco' => $enderecoEntity));
                $dataValidade = $estoqueEntity->getValidade();
                if (!is_null($dataValidade)) {
                    $params['dataValidade']['dataValidade'] = $dataValidade->format('Y-m-d H:i:s');
                }
            }

            $params['codProduto'] = $produtoEn->getId();
            $params['grade'] = $produtoEn->getGrade();
            $palete['conferente'] = $PaleteRepository->findConferente($palete['idUma'], $params['codProduto'], $params['grade']);

            if (isset($palete['picking'])) {
                $picking = $palete['picking'];
            } else {
                $picking = $this->getPicking($produtoEn);
            }

            if ($modelo == 1) {
                $this->layout01($palete, $produtoEn, $font_size, $line_width, $picking, $params);
            } else if ($modelo == 2) {
                $this->layout02($palete, $produtoEn, $font_size, $line_width, $picking, $params);
            } else if ($modelo == 4) {
                $this->layout04($palete, $produtoEn, $font_size, $line_width, $picking);
            } elseif ($modelo == 5) {
                $this->layout05($palete, $produtoEn, $font_size, $line_width, $params);
            } elseif ($modelo == 6) {
                $this->layout06($palete, $produtoEn, $font_size, $line_width, $picking, $params);
            } else if ($modelo == 7) {
                $this->layout07($palete, $produtoEn, $font_size, $line_width, $picking, $params);
            } else {
                $this->layout03($palete, $produtoEn, $font_size, $line_width, $picking, $params);
            }
            $paleteEn = $PaleteRepository->find($palete['idUma']);
            if ($paleteEn != NULL) {
                if ($modelo == 3) {
                    $this->Image(@CodigoBarras::gerarNovo($paleteEn->getId()), 50, 140, 170, 35);
                } elseif ($modelo != 5) {
                    $this->Image(@CodigoBarras::gerarNovo($paleteEn->getId()), null, null, 170, 40);
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

    function Footer() {

        // Go to 1.5 cm from bottom
        $this->SetY(-15);
        // Select Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Print centered page number
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');



        if ($this->_modelo == "1") {
            $this->SetY(-30);
            $this->SetFont('Arial', 'I', 28);
            $this->Cell(0, 1, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 0, 'R');
        } else {
            $this->Cell(-30, 0, utf8_decode(date('d/m/Y') . " às " . date('H:i')), 0, 0, 'C');
        }

    }

    public function layout05($palete, $produtoEn, $font_size, $line_width, $params) {
        $this->AddPage();

        $descricaoProduto = $produtoEn->getDescricao();
        $codigoProduto = $produtoEn->getId();
        $font_size = 30;

        $this->Image(@CodigoBarras::gerarNovo($palete['idUma']), 50, 73, 170, 40);

        $this->SetFont('Arial', 'B', 75);
        $this->Cell($line_width, 15, '             ' . $codigoProduto, 0, 5);

        $this->SetFont('Arial', 'B', $font_size);
        $this->Cell($line_width, 40, $descricaoProduto, 0, 5, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35, 40, "", 0, 0);

        if (isset($params['dataValidade']) && !is_null($params['dataValidade']['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->SetFont('Arial', 'B', 40);
            $this->Cell(75, 80, '', 0, 1);
            $this->Cell(75, -40, "Validade ", 0, 1);
            $this->SetFont('Arial', 'B', 70);
            $this->Cell(75, 50, utf8_decode("               $dataValidade"), 0, 1);
        } else {
            $this->Cell(75, 75, '', 0, 1);
            $this->Cell(75, -40, '', 0, 1);
            $this->SetFont('Arial', 'B', 70);
            $this->Cell(75, 40, '', 0, 1);
        }

        $this->Cell($line_width, 30, '', 0, 25);
        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25, -60, "Qtd", 0, 0);
        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");

        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        $qtd = (is_array($vetQtd)) ? implode(' - ', $vetQtd) : $palete['qtd'];
        $this->SetFont('Arial', 'B', 35);
        $this->Cell(75,-60, $qtd .' - '.$palete['conferente']['NOM_PESSOA'], 0, 40);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55, -115, utf8_decode("End.: "), 0, 0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(105, -115, '    ' . $palete['endereco'], 0, 1);
    }

    public function layout03($palete, $produtoEn, $font_size, $line_width, $picking, $params) {
        $this->AddPage();

        $codigoProduto = $produtoEn->getId();
        $descricaoProduto = $produtoEn->getDescricao();
        $referencia = $produtoEn->getReferencia();
        if (!empty($referencia) && null !== $referencia) {
            $referencia = ' / ' . $produtoEn->getReferencia();
        }

        if (strlen($descricaoProduto) >= 42) {
            $font_size = 36;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 40;
        }

        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 15, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35, 40, "", 0, 0);

        if (isset($params['dataValidade']) && !is_null($params['dataValidade']['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->SetFont('Arial', 'B', 40);
            $this->Cell(75, 40, utf8_decode("Validade "), 0, 1);
            $this->SetFont('Arial', 'B', 70);
            $this->Cell(75, -40, utf8_decode("               $dataValidade"), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25, 55, "Qtd", 0, 0);

        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        $qtd = (is_array($vetQtd)) ? implode(' - ', $vetQtd) : $palete['qtd'];

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(75, 55, $qtd, 0, 1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55, 15, utf8_decode("Endereço "), 0, 0);

        if (isset($palete['endereco']) && !is_null($palete['endereco']) && !empty($palete['endereco'])) {
            $endereco = $palete['endereco'];
        } else {
            $endereco = $picking;
        }
        $this->SetFont('Arial', 'B', 60);
        $this->Cell(105, 15, $endereco, 0, 1);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55, 90, utf8_decode("Prod/Ref.:"), 0, 0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(105, 90, $codigoProduto . $referencia, 0, 1);
    }

    public function layout02($palete, $produtoEn, $font_size, $line_width, $enderecoPicking, $params = null) {
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
        $this->Cell(35, 40, "", 0, 0);

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(30, 35);
        if (isset($params['dataValidade']) && !is_null($params['dataValidade']['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking - Validade $dataValidade"), 0, 1);
        } else {
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking"), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(10, 55);
        $this->Cell(55, 20, utf8_decode("Endereço"), 0, 0);

        $this->SetFont('Arial', 'B', 55);
        $this->SetXY(10, 70);
        if (isset($palete['endereco']) && !empty($palete['endereco'])) {
            $this->Cell(95, 27, $palete['endereco'], 0, 1);
        } else {
            $this->Cell(95, 27, '--.---.--.--', 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(145, 55);
        $this->Cell(25, 20, 'Nota', 0, 1);

        if ((isset($params['notaFiscal'])) && ($params['notaFiscal'] != null)) {
            $this->SetFont('Arial', 'B', 55);
            $this->SetXY(173, 55);
            $this->Cell(25, 20, $params['notaFiscal']->getNumero(), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(145, 77);
        $this->Cell(25, 20, 'Entrada da Nota', 0, 1);

        if ((isset($params['notaFiscal'])) && ($params['notaFiscal'] != null)) {
            $this->SetFont('Arial', 'B', 32);
            $this->SetXY(235, 77);
            $this->Cell(25, 20, $params['notaFiscal']->getDataEntrada()->format('d/m/Y'), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(210, 110);
        $this->Cell(-15, 30, "", 0, 0);

        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        if(is_array($vetQtd)) {
            $qtd = implode(' - ', $vetQtd);
        }else{
            $qtd = $vetQtd;
        }
        $size = 60;
        if(strlen ($qtd) > 15){
            $size = 50;
        }
        if(strlen ($qtd) >= 18){
            $size = 40;
        }
        if(strlen ($qtd) >= 25){
            $size = 30;
        }

        $this->SetFont('Arial', 'B', $size);
        $this->SetXY(145, 110);
        $this->Cell(-15, 30, $qtd, 0, 1);

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(10, 110);
        $this->Cell(35, 30, utf8_decode("Prod"), 0, 0);

        $this->SetFont('Arial', 'B', 70);
        $this->Cell(40, 30, $codigoProduto, 0, 1);
    }

    public function layout01($palete, $produtoEn, $font_size, $line_width, $enderecoPicking, $params = null) {
        $this->AddPage();
        $this->_modelo = "1";

        $descricaoProduto = $produtoEn->getId() . '-' . $produtoEn->getDescricao();


        if (strlen($descricaoProduto) >= 42) {
            $font_size = 36;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 40;
        }

        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 20, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35, 40, "", 0, 0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(165, 40, $produtoEn->getGrade(), 0, 0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25, 40, '', 0, 0);

        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        if(is_array($vetQtd)) {
            $qtd = implode(' - ', $vetQtd);
        }else{
            $qtd = $vetQtd;
        }


        $this->SetFont('Arial', 'B', 60);
        $this->Cell(75, 40, '', 0, 1);

        $this->SetFont('Arial', 'B', 32);

        if (isset($params['dataValidade']) && !is_null($params['dataValidade']['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking - Validade $dataValidade - Qtd: $qtd"), 0, 1);
        } else {
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking - Qtd: $qtd"), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(55, 40, utf8_decode("Endereço"), 0, 0);

        $this->SetFont('Arial', 'B', 72);
        $this->Cell(95, 40, $palete['endereco'], 0, 1);

        $this->SetFont('Arial', 'B', 35);
        $this->setXY(200,140);
        $this->Cell(0, 1, "Receb.: $params[idRecebimento]", 0, 1, 'R');

    }

    private function getPicking($produtoEn) {
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

    public function layout04($palete, $produtoEn, $font_size, $line_width, $enderecoPicking) {
        $this->AddPage();

        $descricaoProduto = $produtoEn->getId() . '-' . $produtoEn->getDescricao();

        if (strlen($descricaoProduto) >= 42) {
            $font_size = 56;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 66;
        }

        $this->SetFont('Arial', 'B', $font_size);

        $this->MultiCell($line_width, 20, $descricaoProduto, 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35, 40, "", 0, 0);

        $this->SetFont('Arial', 'B', 60);
        $this->Cell(165, 40, $produtoEn->getGrade(), 0, 0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(25, 40, "Qtd", 0, 0);

        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        $qtd = (is_array($vetQtd)) ? implode(' - ', $vetQtd) : $palete['qtd'];
        $this->SetFont('Arial', 'B', 60);
        $this->Cell(75, 40, $qtd, 0, 1);

        $this->SetFont('Arial', 'B', 32);

        $this->Cell(150, 20, utf8_decode("Picking $enderecoPicking"), 0, 0);

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(1, 20, utf8_decode("Endereço 01.001.00.01"), 0, 1);
    }

    // Layout etiqueta com lote (Cliente que utiliza: Hidrau)
    public function layout06($palete, $produtoEn, $font_size, $line_width, $enderecoPicking, $params = null) {
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
        $this->Cell(35, 40, "", 0, 0);

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(30, 35);
        if (isset($params['dataValidade']) && !is_null($params['dataValidade']['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking - Validade $dataValidade"), 0, 1);
        } else {
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking"), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(10, 55);
        $this->Cell(55, 20, utf8_decode("Endereço"), 0, 0);

        $this->SetFont('Arial', 'B', 55);
        $this->SetXY(10, 70);
        if (isset($palete['endereco']) && !empty($palete['endereco'])) {
            $this->Cell(95, 27, $palete['endereco'], 0, 1);
        } else {
            $this->Cell(95, 27, '--.---.--.--', 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(145, 55);
        $this->Cell(25, 20, 'Nota', 0, 1);

        if ((isset($params['notaFiscal'])) && ($params['notaFiscal'] != null)) {
            $this->SetFont('Arial', 'B', 55);
            $this->SetXY(173, 55);
            $this->Cell(25, 20, $params['notaFiscal']->getNumero(), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(145, 77);
        $this->Cell(25, 20, 'Entrada da Nota', 0, 1);

        if ((isset($params['notaFiscal'])) && ($params['notaFiscal'] != null)) {
            $this->SetFont('Arial', 'B', 32);
            $this->SetXY(235, 77);
            $this->Cell(25, 20, $params['notaFiscal']->getDataEntrada()->format('d/m/Y'), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(210, 110);
        $this->Cell(-15, 30, "", 0, 0);

        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        if(is_array($vetQtd)) {
            $qtd = implode(' - ', $vetQtd);
        }else{
            $qtd = $vetQtd;
        }
        $size = 60;
        if(strlen ($qtd) > 15){
            $size = 50;
        }
        if(strlen ($qtd) >= 18){
            $size = 40;
        }
        if(strlen ($qtd) >= 25){
            $size = 30;
        }

        $this->SetFont('Arial', 'B', $size);
        $this->SetXY(145, 95);
        $this->Cell(-15, 30, $qtd, 0, 1);

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(10, 95);
        $this->Cell(35, 30, utf8_decode("Prod"), 0, 0);

        $this->SetFont('Arial', 'B', 70);
        $this->Cell(40, 30, $codigoProduto, 0, 1);

        if (!empty($palete["lotes"])) {
            $this->SetFont('Arial', 'B', 40);
            $this->SetXY(10, 125);
            $this->Cell(55, 20, utf8_decode("LOTES:"), 0, 0);
            $strLotesWidth = 220;
            while (!empty($palete["lotes"])) {
                $strLotes = "";
                foreach ($palete["lotes"] as $key => $lote) {
                    if (Math::compare(Math::adicionar($this->GetStringWidth($strLotes), $this->GetStringWidth($lote)), $strLotesWidth, "<=")) {
                        $strLotes = (empty($strLotes)) ? $lote : "$strLotes, $lote";
                        unset($palete["lotes"][$key]);
                    } else {
                        break;
                    }
                }
                $this->SetX(65);
                $this->Cell($strLotesWidth, 20, $strLotes,0,1);
            }
        }
    }

    public function layout07($palete, $produtoEn, $font_size, $line_width, $enderecoPicking, $params = null) {
        $this->AddPage();

        $codigoProduto = $produtoEn->getId();
        $descricaoProduto = $produtoEn->getDescricao();

        if (strlen($descricaoProduto) >= 42) {
            $font_size = 36;
        } else if (strlen($descricaoProduto) >= 20) {
            $font_size = 40;
        }

        $this->SetFont('Arial', 'B', 50);
        $this->MultiCell($line_width, 15, $codigoProduto, 0, 'C');


        $this->SetFont('Arial', 'B', $font_size);
        $this->MultiCell($line_width, 15, wordwrap($descricaoProduto, 35), 0, 'C');

        $this->SetFont('Arial', 'B', 32);
        $this->Cell(35, 40, "", 0, 0);

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(30, 65);
        if (isset($params['dataValidade']) && !is_null($params['dataValidade']['dataValidade'])) {
            $dataValidade = new \DateTime($params['dataValidade']['dataValidade']);
            $dataValidade = $dataValidade->format('d/m/Y');
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking - Validade $dataValidade"), 0, 1);
        } else {
            $this->Cell(75, 20, utf8_decode("Picking $enderecoPicking"), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(10, 80);
        $this->Cell(55, 20, utf8_decode("Endereço"), 0, 0);

        $this->SetFont('Arial', 'B', 55);
        $this->SetXY(10, 95);
        if (isset($palete['endereco']) && !empty($palete['endereco'])) {
            $this->Cell(100, 27, $palete['endereco'], 0, 1);
        } else {
            $this->Cell(100, 27, '--.---.--.--', 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(145, 65);
        $this->Cell(25, 20, 'Nota', 0, 1);

        if ((isset($params['notaFiscal'])) && ($params['notaFiscal'] != null)) {
            $this->SetFont('Arial', 'B', 55);
            $this->SetXY(173, 65);
            $this->Cell(25, 20, $params['notaFiscal']->getNumero(), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(145, 80);
        $this->Cell(25, 20, 'Entrada da Nota', 0, 1);

        if ((isset($params['notaFiscal'])) && ($params['notaFiscal'] != null)) {
            $this->SetFont('Arial', 'B', 32);
            $this->SetXY(235, 80);
            $this->Cell(25, 20, $params['notaFiscal']->getDataEntrada()->format('d/m/Y'), 0, 1);
        }

        $this->SetFont('Arial', 'B', 32);
        $this->SetXY(210, 110);
        $this->Cell(-15, 30, "", 0, 0);

        $embalagemRepo = \Zend_Registry::get('doctrine')->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $vetQtd = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $palete['qtd']);
        if(is_array($vetQtd)) {
            $qtd = implode(' - ', $vetQtd);
        }else{
            $qtd = $vetQtd;
        }
        $size = 60;
        if(strlen ($qtd) > 15){
            $size = 50;
        }
        if(strlen ($qtd) >= 18){
            $size = 40;
        }
        if(strlen ($qtd) >= 25){
            $size = 30;
        }

        $this->SetFont('Arial', 'B', $size);
        $this->SetXY(145, 115);
        $this->Cell(-15, 30, $qtd, 0, 1, 'C');

    }

}
