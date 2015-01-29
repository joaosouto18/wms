<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\NullOutput;

class ReservaEstoqueRepository extends EntityRepository
{
    /*$tipoReserva (E = Entrada, S = Saida)
      $origemReserva (E = Expedição, U = Uma, O = Onda)
      $idOrigem = id da Origem da Reserva de Estoque*/
    public function adicionaReservaEstoque ($idEndereco,$codProduto, $grade,$qtdReservar, $tipoReserva, $origemReserva, $idOrigem, $idOs = null, $idUsuario = null, $observacao = "" )
    {
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");

        if ($idUsuario == null) {
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        }

        $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto,'grade' => $grade));
        $enderecoEn = $enderecoRepo->findOneBy(array('id'=>$idEndereco));
        $usuarioEn = $usuarioRepo->find($idUsuario);

        if (($tipoReserva == "S") && ($qtdReservar >0)) $qtdReservar = $qtdReservar * -1;
        if (($tipoReserva == "E") && ($qtdReservar <0)) $qtdReservar = $qtdReservar * -1;
        if ($produtoEn == NULL) throw new \Exception("Produto não encontrado");
        if ($enderecoEn == NULL) throw new \Exception("Endereço não encontrado");
        if ($usuarioEn == NULL) throw new \Exception("Usuário não encontrado");

        if ($origemReserva == "O") {
            return $this->addReservaEstoqueOnda($enderecoEn,$produtoEn,$qtdReservar,$tipoReserva,$idOrigem,$idOs,$usuarioEn,$observacao);
        } else if ($origemReserva == "U") {
            return $this->addReservaEstoqueUma($enderecoEn,$produtoEn,$qtdReservar,$tipoReserva,$idOrigem,$usuarioEn,$observacao);
        } else if ($origemReserva == "E") {
            return $this->addReservaEstoqueExpedicao($enderecoEn,$produtoEn,$qtdReservar,$idOrigem,$usuarioEn,$observacao);
        }
    }

    public function findReservaEstoque ($idEndereco,$codProduto, $grade,$qtdReservada, $tipoReserva, $origemReserva, $idOrigem, $idOs = NULL )
    {
        if ($origemReserva == "O") {
            $reservaEstoqueOndaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueOnda");
            $reservaEstoqueArray = $reservaEstoqueOndaRepo->findBy(array('ondaRessuprimentoOs'=> $idOrigem, 'os'=>$idOs));
        } else if ($origemReserva == "U") {
            $reservaEstoqueUmaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");
            $reservaEstoqueArray = $reservaEstoqueUmaRepo->findBy(array('palete'=> $idOrigem));
        } else if ($origemReserva == "E") {
            $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
            $reservaEstoqueArray = $reservaEstoqueExpedicaoRepo->findBy(array('expedicao'=> $idOrigem));
        }

        if (($tipoReserva == "S") && ($qtdReservada >0)) $qtdReservada = $qtdReservada * -1;
        if (($tipoReserva == "E") && ($qtdReservada <0)) $qtdReservada = $qtdReservada * -1;

        if (count($reservaEstoqueArray) == 0) {
            return null;
        }

        $pos = count($reservaEstoqueArray) -1;
        while ($pos >= 0) {
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoque $reservaEstoqueEn */
            $reservaEstoqueEn = $reservaEstoqueArray[$pos]->getReservaEstoque();
            if (($reservaEstoqueEn->getProduto()->getId() == $codProduto) &&
                ($reservaEstoqueEn->getProduto()->getGrade() == $grade)){
                if ($origemReserva == "U") {
                    if (($reservaEstoqueEn->getEndereco()->getId() == $idEndereco) &&
                        ($reservaEstoqueEn->getQtd() == $qtdReservada) &&
                        ($reservaEstoqueEn->getTipoReserva() == $tipoReserva)) {
                        return $reservaEstoqueEn;
                    }
                } elseif ($origemReserva == "O") {
                    if (($reservaEstoqueEn->getTipoReserva() == $tipoReserva)) {
                        return $reservaEstoqueEn;
                    }
                } elseif ($origemReserva == "E") {
                    return $reservaEstoqueEn;
                }
            }
            $pos = $pos -1;
        }
        return null;
    }

    public function efetivaReservaEstoque ($idEndereco,$codProduto, $grade,$qtdReservada, $tipoReserva, $origemReserva, $idOrigem, $idUsuario = NULL, $idOs = NULL, $unitizador = Null, $throwException = false)
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$codProduto,$grade,$qtdReservada,$tipoReserva,$origemReserva,$idOrigem, $idOs);
        if ($reservaEstoqueEn == NULL)  {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            } else {
                return false;
            }
        }

        $idEndereco = $reservaEstoqueEn->getEndereco()->getId();
        $codProduto = $reservaEstoqueEn->getProduto()->getId();
        $grade = $reservaEstoqueEn->getProduto()->getGrade();
        $qtdReservada = $reservaEstoqueEn->getQtd();

        if ($idUsuario == null) {
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        }
        $idUma = Null;
        $observacoes = "";
        if ($origemReserva == "U") {
            $observacoes = "Mov. ref. endereçamento do Palete " . $idOrigem;
        }
        if ($origemReserva == "O") {
            $observacoes = "Mov. ref. onda " . $idOrigem . ", OS: " . $idOs;
        }
        if ($origemReserva == "E") {
            $observacoes = "Mov. ref. expedicao " . $idOrigem;
        }

        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioEn = $usuarioRepo->find($idUsuario);
        if ($usuarioEn == NULL) throw new \Exception("Usuário não encontrado");

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $estoqueRepo->movimentaEstoque($codProduto,$grade,$idEndereco,$qtdReservada,$idUsuario,$observacoes,"S",$idOs,$unitizador,$idUma);

        if ($reservaEstoqueEn != NULL) {
            $reservaEstoqueEn->setAtendida("S");
            $reservaEstoqueEn->setDataAtendimento(new \DateTime());
            $reservaEstoqueEn->setUsuarioAtendimento($usuarioEn);
            $this->getEntityManager()->persist($reservaEstoqueEn);
        }
        $this->getEntityManager()->flush();

        return true;
    }

    public function reabrirReservaEstoque($idEndereco,$codProduto, $grade,$qtdReservada, $tipoReserva, $origemReserva, $idOrigem, $throwException = false )
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$codProduto,$grade,$qtdReservada,$tipoReserva,$origemReserva,$idOrigem);
        if ($reservaEstoqueEn == NULL) {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            }
        }


        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();

        if ($origemReserva == "U")
            $observacoes = "Mov.Ref. Reabertura Reserva de Estoque - Palete " . $idOrigem;
        if ($origemReserva == "E")
            $observacoes = "Mov.Ref. Reabertura Reserva de Estoque - Expedição " . $idOrigem;
        if ($origemReserva == "O")
            $observacoes = "Mov.Ref. Reabertura Reserva de Estoque - Onda OS " . $idOrigem;

        if (($reservaEstoqueEn == NULL) || ($reservaEstoqueEn->getDataAtendimento() != NULL)) {
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
            $estoqueRepo->movimentaEstoque($codProduto,$grade,$idEndereco,$qtdReservada * -1,$idUsuario,$observacoes,"S");
        }

        if ($reservaEstoqueEn != NULL) {
            $reservaEstoqueEn->setAtendida("N");
            $reservaEstoqueEn->setDataAtendimento(null);
            $reservaEstoqueEn->setDscObservacao("RESERVA DE ESTOQUE REABERTA POR ". $idUsuario);
            $reservaEstoqueEn->setUsuarioAtendimento(null);
            $this->getEntityManager()->persist($reservaEstoqueEn);
        }
    }

    public function cancelaReservaEstoque($idEndereco,$codProduto, $grade,$qtdReservada, $tipoReserva, $origemReserva, $idOrigem, $throwException = false )
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$codProduto,$grade,$qtdReservada,$tipoReserva,$origemReserva,$idOrigem);
        if ($reservaEstoqueEn == NULL) {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            } else {
                return true;
            }
        }

        if ($origemReserva == "O") {
            throw new \Exception("Não é possível cancelar uma reserva de estoque de Onda de Ressuprimento");
        } else if ($origemReserva == "U") {
            $reservaEstoqueUmaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");
            $reservaEstoqueUmaEn = $reservaEstoqueUmaRepo->findOneBy(array('palete'=> $idOrigem, 'reservaEstoque'=> $reservaEstoqueEn));

            $this->getEntityManager()->remove($reservaEstoqueUmaEn);
            $this->getEntityManager()->remove($reservaEstoqueEn);
            $this->getEntityManager()->flush();
        } else if ($origemReserva == "E") {
            $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
            $reservaEstoqueExpedicaoEn = $reservaEstoqueExpedicaoRepo->findOneBy(array('expedicao'=> $idOrigem, 'reservaEstoque'=> $reservaEstoqueEn));

            $this->getEntityManager()->remove($reservaEstoqueExpedicaoEn);
            $this->getEntityManager()->remove($reservaEstoqueEn);
            $this->getEntityManager()->flush();
        }
        return true;
    }

    private function addReservaEstoque($enderecoEn, $produtoEn,$qtdReservar,$tipoReserva, $usuario,$observacoes)
    {
        $reservaEstoque = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoque();
        $reservaEstoque->setUsuario($usuario);
        $reservaEstoque->setAtendida("N");
        $reservaEstoque->setDataAtendimento(Null);
        $reservaEstoque->setDataReserva(new \DateTime());
        $reservaEstoque->setDscObservacao($observacoes);
        $reservaEstoque->setQtd($qtdReservar);
        $reservaEstoque->setTipoReserva($tipoReserva);
        $reservaEstoque->setEndereco($enderecoEn);
        $reservaEstoque->setProduto($produtoEn);
        $reservaEstoque->setUsuarioAtendimento(null);
        $this->getEntityManager()->persist($reservaEstoque);
        return $reservaEstoque;
    }

    private function addReservaEstoqueUma ($enderecoEn, $produtoEn, $qtdReservar, $tipoReserva, $idUMA, $usuarioReserva, $observacoes){
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");
        $reservaEstoqueUmaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

        $paleteEn = $paleteRepo->findOneBy(array('id'=>$idUMA));
        if ($paleteEn == NULL) {throw new \Exception("UMA $idUMA não encontrada"); }

        $reservaEstoqueUma = $reservaEstoqueUmaRepo->findOneBy(array('palete' => $idUMA));
        if ($reservaEstoqueUma != NULL) {throw new \Exception("UMA $idUMA já possui uma reserva de entrada");}

        $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtoEn,$qtdReservar,$tipoReserva,$usuarioReserva,$observacoes);
        $reservaEstoqueUma = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamento();
            $reservaEstoqueUma->setPalete($paleteEn);
            $reservaEstoqueUma->setReservaEstoque($reservaEstoqueEn);
        $this->getEntityManager()->persist($reservaEstoqueUma);

        return $reservaEstoqueEn;
    }

    private function addReservaEstoqueExpedicao ($enderecoEn, $produtoEn, $qtdReservar, $idExpedicao, $usuarioReserva, $observacoes){

        $reservaEstoqueEn = $this->findReservaEstoque($enderecoEn->getId(),$produtoEn->getId(),$produtoEn->getGrade(),$qtdReservar,"S","E",$idExpedicao);

        if ($reservaEstoqueEn != NULL) {
            $reservaEstoqueEn->setQtd($reservaEstoqueEn->getQtd() - $qtdReservar);
            $this->getEntityManager()->persist($reservaEstoqueEn);
        } else {
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
            $expedicaoEn = $expedicaoRepo->findOneBy(array('id'=>$idExpedicao));

            $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtoEn,$qtdReservar,"S",$usuarioReserva,$observacoes);
            $reservaEstoqueExpedicao = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao();
            $reservaEstoqueExpedicao->setExpedicao($expedicaoEn);
            $reservaEstoqueExpedicao->setReservaEstoque($reservaEstoqueEn);
            $this->getEntityManager()->persist($reservaEstoqueExpedicao);
        }
        return $reservaEstoqueEn;
    }

    private function addReservaEstoqueOnda ($enderecoEn, $produtoEn, $qtdReservar, $tipoReserva, $idOndaOs,$idOs, $usuarioReserva, $observacoes)
    {
        $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtoEn,$qtdReservar,$tipoReserva,$usuarioReserva,$observacoes);

        $ordemServicoRepo = $this->getEntityManager()->getRepository("wms:OrdemServico");
        $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");

        $osEn = $ordemServicoRepo->findOneBy(array('id'=>$idOs));
        $ondaOsEn = $ondaOsRepo->findOneBy(array('id'=>$idOndaOs));

        $reservaEstoqueOnda = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueOnda();
            $reservaEstoqueOnda->setReservaEstoque($reservaEstoqueEn);
            $reservaEstoqueOnda->setOs($osEn);
            $reservaEstoqueOnda->setOndaRessuprimentoOs($ondaOsEn);

        $this->getEntityManager()->persist($reservaEstoqueOnda);

        return $reservaEstoqueEn;
    }

    public function getQtdReservadaByProduto($codProduto, $grade, $idEndereco, $tipo = "E")
    {
        $SQL = "SELECT CASE WHEN SUM(QTD_RESERVADA)IS NULL THEN 0 ELSE SUM(QTD_RESERVADA) END AS QTD
                  FROM RESERVA_ESTOQUE
                 WHERE COD_PRODUTO = '$codProduto'
                   AND DSC_GRADE = '$grade'
                   AND COD_DEPOSITO_ENDERECO = '$idEndereco'
                   AND TIPO_RESERVA = '$tipo'
                   AND DTH_ATENDIMENTO IS NULL";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result[0]['QTD'];
    }

}
