<?php

namespace Wms\Module\Enderecamento\Report;

use Core\Pdf;
use Wms\Domain\EntityRepository;

class ReabastecimentoManual extends Pdf
{

    protected $usuario;
    protected $codOs;

    public function Header()
    {
        //Select Arial bold 8
        $this->SetFont('Arial','B',10);
        $this->Cell(20, 20, utf8_decode("RELATÓRIO DE REABASTECIMENTO - ".$this->codOs ), 0, 1);

        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        $utilizaGrade = $parametroRepo->findOneBy(array('constante' => 'UTILIZA_GRADE'));

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(15,  5, utf8_decode("Código")  ,1, 0);
        if ($utilizaGrade == 'S') {
            $this->Cell(20,  5, "Grade"   ,1, 0);
        } else {
            $this->Cell(20,  5, "Ref"   ,1, 0);
        }
        $this->Cell(85, 5, "Produto" ,1, 0);
        $this->Cell(25, 5, "Qtd Solicitada" ,1, 0);
        $this->Cell(25, 5, "Qtd Estoque" ,1, 0);
        $this->Cell(25,  5, "End.Picking" ,1, 1);
    }

    public function Footer()
    {
        // font
        $this->SetFont('Arial','B',7);

        //Go to 1.5 cm from bottom
        $this->SetY(-20);

        $this->Cell(270, 10, utf8_decode("Relatório gerado em ".date('d/m/Y')." às ".date('H:i:s'). ' - '.$this->usuario), 0, 0, "L");
        // font
        $this->SetFont('Arial','',8);
        $this->Cell(0,15,utf8_decode('Página ').$this->PageNo(),0,0,'R');
    }

    public function imprimir($codOs)
    {
        /** @var \Wms\Domain\Entity\Expedicao\VRelProdutosRepository $RelProdutos */
        \Zend_Layout::getMvcInstance()->disableLayout(true);
        \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        $this->_codOS = $codOs;

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $this->SetMargins(7, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->codOs = $codOs;
        $this->AddPage();

        /** @var \Wms\Domain\Entity\Enderecamento\ReabastecimentoManualRepository $reabasteRepo */
        $reabasteRepo = $em->getRepository("wms:Enderecamento\ReabastecimentoManual");
        $reabasteEn  = $reabasteRepo->findOneBy(array('os' => $codOs));
        $this->usuario = $reabasteEn->getOs()->getPessoa()->getNome();

        $produtos = $reabasteRepo->getProdutos($codOs);

        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        $utilizaGrade = $parametroRepo->findOneBy(array('constante' => 'UTILIZA_GRADE'));

        $limite = 73;
        $codProdutoAnterior = null;
        $gradeAnterior = null;

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $em->getRepository("wms:Enderecamento\Estoque");

        $dscPicking = null;
        if (isset($produtos[0]['endereco'])) {
            $dscPicking = $produtos[0]['endereco'];
        }
        $dscVolume = "";

        foreach ($produtos as $produto) {
            if ($dscVolume != "") $dscVolume .= "; ";
            $dscVolume .= $produto['descricao'];
        }

        $config = \Zend_Registry::get('config');
        $viewErp = $config->database->viewErp->habilitado;

        foreach ($produtos as $produto) {

            $codProduto = $produto['codProduto'];
            $grade = $produto['grade'];
            $referencia = $produto['referencia'];

            if ($codProduto != $codProdutoAnterior || $grade != $gradeAnterior) {
                $dscProduto = $produto['produto'];

                $params = array();
                $params['idProduto'] = $codProduto;
                $params['grade'] = $grade;
                $params['volume'] = $produto['codVolume'];

                $enderecosPulmao = $estoqueRepo->getEstoqueAndVolumeByParams ($params,5,false);
                $c = count($enderecosPulmao);

                if ((($limite - $c) - 2 ) <= 0 )
                {
                    $this->AddPage();
                    $limite = 73;
                }

                $qtdEstoque = null;
                if ($viewErp) {
                    $conexao = EntityRepository::conexaoViewERP();
                    $query = "select QTEST from FN_GET_PROD_IMPERIUM where CODPROD = $codProduto";
                    $saldoProduto = EntityRepository::nativeQuery($query, 'all', $conexao);
                    if ($saldoProduto) {
                        $qtdEstoque = $saldoProduto[0]['QTEST'];
                    }
                }

                $this->SetFont('Arial', 'B', 8);
                $this->Cell(15, 5, utf8_decode($codProduto) ,1, 0);
                if ($utilizaGrade == 'S') {
                    $this->Cell(20, 5, utf8_decode($grade)      ,1, 0);
                } else {
                    $this->Cell(20, 5, utf8_decode($referencia)      ,1, 0);
                }
                $this->Cell(85, 5, utf8_decode(substr($dscProduto,0,47)) ,1, 0);
                $this->Cell(25, 5, $produto['qtd'] ,1, 0);
                $this->Cell(25, 5, $qtdEstoque ,1, 0);
                $this->Cell(25, 5, utf8_decode($produto['endereco']) ,1, 1);

                $limite = $limite -1;

                if ($enderecosPulmao) {

                    $this->Cell(10, 5, "", 0);
                    $this->Cell(30, 5, "Dth Armazenagem", "TB");
                    $this->Cell(30, 5, utf8_decode("End.Pulmão"), "TB");
                    $this->Cell(15, 5, "Res. Ent.", "TB");
                    $this->Cell(15, 5, "Res. Sai.", "TB");
                    $this->Cell(15, 5, "Qtd", "TB");
                    $this->Cell(75, 5, utf8_decode("Volume"), "TB", 1);

                    $limite = $limite - 1;

                    foreach ($enderecosPulmao as $pulmao) {
                        $this->SetFont('Arial', '', 8);
                        $qtdReservaEntrada = $pulmao["RESERVA_ENTRADA"];
                        $qtdReservaSaida = $pulmao["RESERVA_SAIDA"];
                        $qtdEndereco = $pulmao["QTD"];
                        $dscEndereco = $pulmao['ENDERECO'];
                        $dthUltimaEntrada = $pulmao['DTH_PRIMEIRA_MOVIMENTACAO'];

                        $this->Cell(10, 5, "", 0, 0);
                        $this->Cell(30, 5, $dthUltimaEntrada, 0, 0);
                        $this->Cell(30, 5, utf8_decode($dscEndereco), 0, 0);
                        $this->Cell(15, 5, utf8_decode($qtdReservaEntrada), 0, 0);
                        $this->Cell(15, 5, utf8_decode($qtdReservaSaida), 0, 0);
                        $this->Cell(15, 5, utf8_decode($qtdEndereco), 0, 0);
                        $this->Cell(75, 5, utf8_decode($dscVolume), 0, 1);

                        $limite = $limite - 1;
                    }
                }

                $codProdutoAnterior = $codProduto;
                $gradeAnterior = $grade;
                $limite = $limite -1;
            } else {
                $codProdutoAnterior = $codProduto;
                $gradeAnterior = $grade;
            }
        }

        $this->Output('Reabastecimento-'.$codOs.'.pdf','D');
    }
}
