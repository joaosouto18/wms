<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\NullOutput;

class ReservaEstoqueRepository extends EntityRepository
{
    /**
     * $tipoReserva (E = Entrada, S = Saida)
     * $origemReserva (E = Expedição, U = Uma, O = Onda)
     * $idOrigem = id da Origem da Reserva de Estoque
     * produtos = array();
     * produtos[0]['codProdutoEmbalagem'] = NULL
     * produtos[0]['codProdutoVolume'] = 1
     * produtos[0]['codProduto'] = 'Nome do Produto'
     * produtos[0]['grade'] = 'Grade do Produto'
     * produtos[0]['qtd'] = '10' ou '-10'
    */
    public function adicionaReservaEstoque ($idEndereco, $produtos = array(), $tipoReserva, $origemReserva, $idOrigem, $Os = null, $idUsuario = null, $observacao = "" )
    {
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");

        if ($idUsuario == null) {
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        }

        $enderecoEn = $enderecoRepo->findOneBy(array('id'=>$idEndereco));
        $usuarioEn = $usuarioRepo->find($idUsuario);

        if ($enderecoEn == NULL) throw new \Exception("Endereço não encontrado");
        if ($usuarioEn == NULL) throw new \Exception("Usuário não encontrado");
        if (count($produtos) == 0) throw new \Exception("Nenhum volume informado");

        foreach ($produtos as $key => $produto) {
            if (($tipoReserva == "S") && ($produto['qtd'] > 0)) $produtos[$key]['qtd'] = $produto['qtd'] * -1;
            if (($tipoReserva == "E") && ($produto['qtd'] < 0)) $produtos[$key]['qtd'] = $produto['qtd'] * -1;
        }

        if ($origemReserva == "O") {
            return $this->addReservaEstoqueOnda($enderecoEn,$produtos,$tipoReserva,$idOrigem,$Os,$usuarioEn,$observacao);
        } else if ($origemReserva == "U") {
            return $this->addReservaEstoqueUma($enderecoEn,$produtos,$tipoReserva,$idOrigem,$usuarioEn,$observacao);
        } else if ($origemReserva == "E") {
            return $this->addReservaEstoqueExpedicao($enderecoEn,$produtos,$idOrigem,$usuarioEn,$observacao);
        }
    }

    public function findReservaEstoque ($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $idOs = NULL )
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


        foreach ($produtos as $key => $produto) {
            if (($tipoReserva == "S") && ($produto['qtd'] > 0)) $produtos[$key]['qtd'] = $produto['qtd'] * -1;
            if (($tipoReserva == "E") && ($produto['qtd'] < 0)) $produtos[$key]['qtd'] = $produto['qtd'] * -1;
        }

        if (count($reservaEstoqueArray) == 0) {
            return null;
        }

        foreach ($reservaEstoqueArray as $key =>$reserva) {
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoque $reservaEstoqueEn */
            $reservaEstoqueEn = $reserva->getReservaEstoque();
            $reservaProdutos = $reservaEstoqueEn->getProdutos();
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto $reservaProduto */
            foreach ($reservaProdutos as $reservaProduto) {
                foreach ($produtos as $produto) {
                    if (($produto['codProduto'] == $reservaProduto->getProduto()->getId()) &&
                        ($produto['grade'] == $reservaProduto->getProduto()->getGrade()) ) {
                        if ($origemReserva == "U") {
                            if (($reservaEstoqueEn->getEndereco()->getId() == $idEndereco) &&
                                ($reservaProduto->getQtd() == $produto['qtd']) &&
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
                }
            }
        }
    }

    /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
    public function efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn, $origemReserva, $idOrigem, $usuarioEn = null, $osEn = null, $unitizadorEn = null){

        if ($usuarioEn == NULL)  {
            $auth = \Zend_Auth::getInstance();
            $usuarioSessao = $auth->getIdentity();
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            $usuarioEn = $pessoaRepo->find($usuarioSessao->getId());
        }

        $observacoes = "";
        $idUma = null;
        if ($origemReserva == "U") {
            $observacoes = "Mov. ref. endereçamento do Palete " . $idOrigem;
            $idUma = $idOrigem;
        }

        if ($origemReserva == "O") {
            $observacoes = "Mov. ref. onda " . $idOrigem . ", OS: " . $osEn->getId();
        }
        if ($origemReserva == "E") {
            $observacoes = "Mov. ref. expedicao " . $idOrigem;
        }

        $reservaProdutos = $reservaEstoqueEn->getProdutos();
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto $reservaProduto */
        foreach ($reservaProdutos as $reservaProduto) {
            $params = array();
            $params['produto'] = $reservaProduto->getProduto();
            $params['endereco'] = $reservaEstoqueEn->getEndereco();
            $params['qtd'] =  $reservaProduto->getQtd();
            $params['volume'] = $reservaProduto->getProdutoVolume();
            $params['embalagem'] = $reservaProduto->getProdutoEmbalagem();
            $params['observacoes'] = $observacoes;
            $params['unitizador'] = $unitizadorEn;
            $params['os'] = $osEn;
            $params['uma'] = $idUma;
            $params['usuario'] = $usuarioEn;
            $params['estoqueRepo'] = $estoqueRepo;
            $estoqueRepo->movimentaEstoque($params, false);
        }

        if ($reservaEstoqueEn != NULL) {
            $reservaEstoqueEn->setAtendida("S");
            $reservaEstoqueEn->setDataAtendimento(new \DateTime());
            $reservaEstoqueEn->setUsuarioAtendimento($usuarioEn);
            $this->getEntityManager()->persist($reservaEstoqueEn);
        }
        return true;
    }

    public function efetivaReservaEstoque ($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $idUsuario = NULL, $idOs = NULL, $unitizador = Null, $throwException = false)
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$produtos,$tipoReserva,$origemReserva,$idOrigem, $idOs);
        if ($reservaEstoqueEn == NULL)  {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            } else {
                return false;
            }
        }

        $usuarioEn = null;
        if ($idUsuario != null){
            $auth = \Zend_Auth::getInstance();
            $usuarioSessao = $auth->getIdentity();
            $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            $usuarioEn = $usuarioRepo->find($usuarioSessao->getId());
        }

        if ($idOs != NULL) {
            $osEn = $this->getEntityManager()->getRepository("wms:OrdemServico")->find($idOs);
        }
        if ($unitizador != NULL) {
            $unitizadorEn = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador")->find($unitizador);
        }

        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        return $this->efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn,$origemReserva,$idOrigem,$usuarioEn ,$osEn,$unitizadorEn);
    }

    public function reabrirReservaEstoque($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $throwException = false )
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$produtos,$tipoReserva,$origemReserva,$idOrigem);
        if ($reservaEstoqueEn == NULL) {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            }
        }

        if ($origemReserva == "U")
            $observacoes = "Mov. ref. estorno endereçamento - palete " . $idOrigem;
        if ($origemReserva == "E")
            $observacoes = "Mov. ref. estorno - expedição " . $idOrigem;
        if ($origemReserva == "O")
            $observacoes = "Mov. ref. estorno - onda OS " . $idOrigem;

        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();

        if (($reservaEstoqueEn == NULL) || ($reservaEstoqueEn->getDataAtendimento() != NULL)) {
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

            $params = array();
            $params['endereco'] = $reservaEstoqueEn->getEndereco();
            $params['observacoes'] = $observacoes;
            $params['estoqueRepo'] = $estoqueRepo;

            foreach ($produtos as $produto) {
                $embalagenEn = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findOneBy(array('id'=>$produto['codProdutoEmbalagem']));
                $volumeEn = $this->getEntityManager()->getRepository("wms:Produto\Volume")->findOneBy(array('id'=>$produto['codProdutoVolume']));
                $produtoEn = $this->getEntityManager()->getRepository("wms:Produto")->findOneBy(array('id'=>$produto['codProduto'], 'grade'=>$produto['grade']));

                $params['qtd'] = $produto['qtd'] * -1;
                $params['produto'] = $produtoEn;
                $params['volume'] = $volumeEn;
                $params['embalagem'] = $embalagenEn;
                $estoqueRepo->movimentaEstoque($params);
            }
        }

        if ($reservaEstoqueEn != NULL) {
            $reservaEstoqueEn->setAtendida("N");
            $reservaEstoqueEn->setDataAtendimento(null);
            $reservaEstoqueEn->setDscObservacao("RESERVA DE ESTOQUE REABERTA POR ". $idUsuario);
            $reservaEstoqueEn->setUsuarioAtendimento(null);
            $this->getEntityManager()->persist($reservaEstoqueEn);
        }
    }

    public function cancelaReservaEstoque($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $throwException = false )
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$produtos,$tipoReserva,$origemReserva,$idOrigem);
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
        } else if ($origemReserva == "E") {
            $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
            $reservaEstoqueExpedicaoEn = $reservaEstoqueExpedicaoRepo->findOneBy(array('expedicao'=> $idOrigem, 'reservaEstoque'=> $reservaEstoqueEn));
            $this->getEntityManager()->remove($reservaEstoqueExpedicaoEn);
        }

        $reservaProdutos = $reservaEstoqueEn->getProdutos();
        foreach($reservaProdutos as $reservaProduto){
            $this->getEntityManager()->remove($reservaProduto);
        }
        $this->getEntityManager()->remove($reservaEstoqueEn);
        $this->getEntityManager()->flush();

        return true;
    }

    private function addReservaEstoque($enderecoEn, $produtos,$tipoReserva, $usuario,$observacoes)
    {
        $reservaEstoque = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoque();
        $reservaEstoque->setUsuario($usuario);
        $reservaEstoque->setAtendida("N");
        $reservaEstoque->setDataAtendimento(Null);
        $reservaEstoque->setDataReserva(new \DateTime());
        $reservaEstoque->setDscObservacao($observacoes);
        $reservaEstoque->setTipoReserva($tipoReserva);
        $reservaEstoque->setEndereco($enderecoEn);
        $reservaEstoque->setUsuarioAtendimento(null);
        $this->getEntityManager()->persist($reservaEstoque);

        foreach ($produtos as $produto) {
            $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=>$produto['codProduto'], 'grade'=>$produto['grade']));
            $reservaEstoqueProduto = new ReservaEstoqueProduto();
            $reservaEstoqueProduto->setProduto($produtoEn);
            if ($produto['codProdutoEmbalagem'] != null) {
                $reservaEstoqueProduto->setCodProdutoEmbalagem($produto['codProdutoEmbalagem']);
                $reservaEstoqueProduto->setProdutoEmbalagem($this->getEntityManager()->getReference("wms:Produto\Embalagem", $produto['codProdutoEmbalagem']));
            }
            if ($produto['codProdutoVolume']!= null)  {
                $reservaEstoqueProduto->setProdutoVolume($this->getEntityManager()->getReference("wms:Produto\Volume",$produto['codProdutoVolume']));
                $reservaEstoqueProduto->setCodProdutoVolume($produto['codProdutoVolume']);
            }
            $reservaEstoqueProduto->setQtd($produto['qtd']);
            $reservaEstoqueProduto->setReservaEstoque($reservaEstoque);
            $this->getEntityManager()->persist($reservaEstoqueProduto);
        }

        return $reservaEstoque;
    }

    private function addReservaEstoqueUma ($enderecoEn, $produtos, $tipoReserva, $idUMA, $usuarioReserva, $observacoes){
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");
        $reservaEstoqueUmaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");

        $paleteEn = $paleteRepo->findOneBy(array('id'=>$idUMA));
        if ($paleteEn == NULL) {throw new \Exception("UMA $idUMA não encontrada"); }

        $reservaEstoqueUma = $reservaEstoqueUmaRepo->findOneBy(array('palete' => $idUMA));
        if ($reservaEstoqueUma != NULL) {throw new \Exception("UMA $idUMA já possui uma reserva de entrada");}

        $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtos,$tipoReserva,$usuarioReserva,$observacoes);
        $reservaEstoqueUma = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamento();
            $reservaEstoqueUma->setPalete($paleteEn);
            $reservaEstoqueUma->setReservaEstoque($reservaEstoqueEn);
        $this->getEntityManager()->persist($reservaEstoqueUma);

        return $reservaEstoqueEn;
    }

    private function addReservaEstoqueExpedicao ($enderecoEn, $produtos, $idExpedicao, $usuarioReserva, $observacoes){

        $reservaEstoqueEn = $this->findReservaEstoque($enderecoEn->getId(),$produtos,"S","E",$idExpedicao);

        if ($reservaEstoqueEn != NULL) {
            $reservaProdutos = $reservaEstoqueEn->getProdutos();
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto $reservaProduto */
            foreach ($reservaProdutos as $reservaProduto) {
                foreach ($produtos as $produto){
                    if (($produto['codProdutoVolume'] == $reservaProduto->getCodProdutoVolume()) &&
                        ($produto['codProdutoEmbalagem'] == $reservaProduto->getCodProdutoEmbalagem())) {
                        $reservaProduto->setQtd($reservaProduto->getQtd() - $produto['qtd']);
                        $this->getEntityManager()->persist($reservaProduto);
                    }
                }
            }
        } else {
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
            $expedicaoEn = $expedicaoRepo->findOneBy(array('id'=>$idExpedicao));

            $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtos,"S",$usuarioReserva,$observacoes);
            $reservaEstoqueExpedicao = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao();
            $reservaEstoqueExpedicao->setExpedicao($expedicaoEn);
            $reservaEstoqueExpedicao->setReservaEstoque($reservaEstoqueEn);
            $this->getEntityManager()->persist($reservaEstoqueExpedicao);
        }
        return $reservaEstoqueEn;
    }

    private function addReservaEstoqueOnda ($enderecoEn, $produtos, $tipoReserva, $ondaOsEn,$osEn, $usuarioReserva, $observacoes)
    {
        $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtos,$tipoReserva,$usuarioReserva,$observacoes);

        $reservaEstoqueOnda = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueOnda();
            $reservaEstoqueOnda->setReservaEstoque($reservaEstoqueEn);
            $reservaEstoqueOnda->setOs($osEn);
            $reservaEstoqueOnda->setOndaRessuprimentoOs($ondaOsEn);

        $this->getEntityManager()->persist($reservaEstoqueOnda);

        return $reservaEstoqueEn;
    }

    public function getQtdReservadaByProduto($codProduto, $grade, $volume, $idEndereco, $tipo = "E")
    {
        $SQL = "SELECT CASE WHEN SUM(QTD_RESERVADA)IS NULL THEN 0 ELSE SUM(QTD_RESERVADA) END AS QTD
                  FROM RESERVA_ESTOQUE RE
                 INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                 WHERE REP.COD_PRODUTO = '$codProduto'
                   AND REP.DSC_GRADE = '$grade'
                   AND RE.COD_DEPOSITO_ENDERECO = '$idEndereco'
                   AND RE.TIPO_RESERVA = '$tipo'
                   AND RE.DTH_ATENDIMENTO IS NULL";
        if ($volume != NULL) {
            $SQL .= " AND REP.COD_PRODUTO_VOLUME = '$volume' ";
        }
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result[0]['QTD'];
    }

}
