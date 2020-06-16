<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Enderecamento\HistoricoEstoque;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Produto;
use Wms\Math;

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
    public function adicionaReservaEstoque ($endereco, $produtos, $tipoReserva, $origemReserva, $idOrigem, $Os = null, $idUsuario = null, $observacao = "", $repositorios = null)
    {
        if ($repositorios == null) {
            $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
            $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        } else {
            $enderecoRepo = $repositorios['enderecoRepo'];
            $usuarioRepo = $repositorios['usuarioRepo'];
        }

        if ($idUsuario == null) {
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        }

        /** @var Endereco $enderecoEn */
        if (!is_object($endereco)) {
            $enderecoEn = $enderecoRepo->findOneBy(array('id' => $endereco));
        } else {
            $enderecoEn = $endereco;
        }
        $usuarioEn = $usuarioRepo->find($idUsuario);

        if ($enderecoEn == NULL) throw new \Exception("Endereço não encontrado");
        if ($usuarioEn == NULL) throw new \Exception("Usuário não encontrado");
        if (count($produtos) == 0) throw new \Exception("Nenhum volume informado");

        if (($tipoReserva == "S") && $enderecoEn->isBloqueadaSaida()) throw new \Exception("Este endereço '".$enderecoEn->getDescricao()."' está bloqueado para movimentações de saída, por esse motivo não pode receber essa reserva!");
        if (($tipoReserva == "E") && $enderecoEn->isBloqueadaEntrada()) throw new \Exception("Este endereço '".$enderecoEn->getDescricao()."' está bloqueado para movimentações de entrada, por esse motivo não pode receber essa reserva!");

        foreach ($produtos as $key => $produto) {
            if (($tipoReserva == "S") && ($produto['qtd'] > 0)) $produtos[$key]['qtd'] = $produto['qtd'] * -1;
            if (($tipoReserva == "E") && ($produto['qtd'] < 0)) $produtos[$key]['qtd'] = $produto['qtd'] * -1;
        }

        if ($origemReserva == "O") {
            return $this->addReservaEstoqueOnda($enderecoEn,$produtos,$tipoReserva,$idOrigem,$Os,$usuarioEn,$observacao,$repositorios);
        } else if ($origemReserva == "U") {
            return $this->addReservaEstoqueUma($enderecoEn,$produtos,$tipoReserva,$idOrigem,$usuarioEn,$observacao);
        } else if ($origemReserva == "E") {
            return $this->addReservaEstoqueExpedicao($enderecoEn, $produtos, $idOrigem, $usuarioEn, $observacao, $repositorios);
        }
    }

    public function findReservaEstoque ($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $idOs = NULL, $repositorios = null)
    {
        if ($origemReserva == "O") {
            if ($repositorios == null) {
                $reservaEstoqueOndaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueOnda");
            } else {
                $reservaEstoqueOndaRepo = $repositorios['reservaEstoqueOndaRepo'];
            }
            $reservaEstoqueArray = $reservaEstoqueOndaRepo->findBy(array('ondaRessuprimentoOs'=> $idOrigem, 'os'=>$idOs));
        } else if ($origemReserva == "U") {
            $reservaEstoqueUmaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueEnderecamento");
            $reservaEstoqueArray = $reservaEstoqueUmaRepo->findBy(array('palete'=> $idOrigem));
        } else if ($origemReserva == "E") {
            if ($repositorios == null) {
                $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
            } else {
                $reservaEstoqueExpedicaoRepo = $repositorios['reservaEstoqueExpRepo'];
            }
            $reservaEstoqueArray = $reservaEstoqueExpedicaoRepo->findBy($idOrigem);
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

            if ($reservaEstoqueEn->getAtendida() == 'C' || empty($reservaProdutos)) {
                continue;
            }

            if (($idEndereco != null) && ($reservaEstoqueEn->getEndereco()->getId() != $idEndereco)) {
                continue;
            }

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto $reservaProduto */
            foreach ($reservaProdutos as $reservaProduto) {
                foreach ($produtos as $produto) {
                    if (($produto['codProduto'] == $reservaProduto->getProduto()->getId()) &&
                        ($produto['grade'] == $reservaProduto->getProduto()->getGrade()) &&
                        ((isset($produto['lote']) && $produto['lote'] == $reservaProduto->getLote()) || empty($reservaProduto->getLote()))) {
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

        return null;
    }

    /** @param \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
    public function efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn, $origemReserva, $idOrigem, $usuarioEn = null, $osEn = null, $unitizadorEn = null, $dataValidade=null, $pedido = null, $arrayFlush = array())
    {
        if ($usuarioEn == NULL)  {
            $auth = \Zend_Auth::getInstance();
            $usuarioSessao = $auth->getIdentity();
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            $usuarioEn = $pessoaRepo->find($usuarioSessao->getId());
        }
        $tipo = "";
        $observacoes = "";
        $idUma = null;
        if ($origemReserva == "U") {
            $observacoes = "Mov. ref. endereçamento do Palete " . $idOrigem;
            $tipo = HistoricoEstoque::TIPO_ENDERECAMENTO;
            $idUma = $idOrigem;
        }
        elseif ($origemReserva == "O") {
            $observacoes = "Mov. ref. onda " . $idOrigem . ", OS: " . $osEn->getId();
            $tipo = HistoricoEstoque::TIPO_RESSUPRIMENTO;
        }
        elseif ($origemReserva == "E") {
            $observacoes = "Mov. ref. expedicao " . $idOrigem;
            $tipo = HistoricoEstoque::TIPO_EXPEDICAO;
        }

        $reservaProdutos = $reservaEstoqueEn->getProdutos();
        $controleProprietario = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'CONTROLE_PROPRIETARIO'))->getValor();
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto $reservaProduto */
        $dthEntrada = new \DateTime();
        foreach ($reservaProdutos as $reservaProduto) {
            $params = array();
            $params['produto'] = $reservaProduto->getProduto();
            $params['endereco'] = $reservaEstoqueEn->getEndereco();
            $params['qtd'] =  $reservaProduto->getQtd();
            $params['volume'] = $reservaProduto->getProdutoVolume();
            $params['embalagem'] = $reservaProduto->getProdutoEmbalagem();
            $params['observacoes'] = $observacoes;
            $params['unitizador'] = $unitizadorEn;
            $params['dthEntrada'] = $dthEntrada;
            $params['os'] = $osEn;
            $params['uma'] = $idUma;
            $params['usuario'] = $usuarioEn;
            $params['tipo'] = $tipo;
            $params['lote'] = $reservaProduto->getLote();
            $dataValidade['dataValidade'] = $reservaProduto->getValidade();
            if($controleProprietario == 'S') {
                $params['codPedido'] = $pedido['codPedido'];
                $params['codProprietario'] = $pedido['codProprietario'];
                $codProduto = $reservaProduto->getProduto()->getId();
                $grade = $reservaProduto->getProduto()->getGrade();
                if (isset($arrayFlush[$codProduto][$grade])) {
                    $arrayFlush = array();
                    $this->getEntityManager()->flush();
                }
                $arrayFlush[$codProduto][$grade] = 1;
            }
            $estoqueRepo->movimentaEstoque($params, false, null, $dataValidade);
        }
        if ($reservaEstoqueEn != NULL) {
            $reservaEstoqueEn->setAtendida("S");
            $reservaEstoqueEn->setDataAtendimento(new \DateTime());
            $reservaEstoqueEn->setUsuarioAtendimento($usuarioEn);
            $this->getEntityManager()->persist($reservaEstoqueEn);
        }
        if($controleProprietario == 'S') {
            return $arrayFlush;
        }
        return true;
    }

    public function efetivaReservaEstoque ($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $idUsuario = NULL, $idOs = NULL, $unitizador = Null, $throwException = false, $dataValidade = null)
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$produtos,$tipoReserva,$origemReserva,$idOrigem,$idOs);
        if ($reservaEstoqueEn == NULL)  {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            } else {
                return false;
            }
        }elseif($reservaEstoqueEn->getAtendida() == 'S'){
            if ($throwException == true) {
                throw new \Exception("Reserva já atendida - (".$reservaEstoqueEn->getId().")");
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

        $unitizadorEn = null;
        if ($unitizador != NULL) {
            $unitizadorEn = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador")->find($unitizador);
        }

        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        return $this->efetivaReservaByReservaEntity($estoqueRepo,$reservaEstoqueEn,$origemReserva,$idOrigem,$usuarioEn,$osEn,$unitizadorEn,$dataValidade);
    }

    /**
     * @param $idEndereco
     * @param $produtos
     * @param $tipoReserva
     * @param $origemReserva
     * @param $idOrigem
     * @param bool $throwException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    public function reabrirReservaEstoque($idEndereco,$produtos, $tipoReserva, $origemReserva, $idOrigem, $throwException = false )
    {
        $reservaEstoqueEn = $this->findReservaEstoque($idEndereco,$produtos,$tipoReserva,$origemReserva,$idOrigem);

        if ($reservaEstoqueEn == NULL) {
            if ($throwException == true) {
                throw new \Exception("Reserva de estoque não encontrada");
            }
        }

        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();

        if (($reservaEstoqueEn != NULL) && ($reservaEstoqueEn->getDataAtendimento() != NULL)) {
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

            $params = array();

            if ($origemReserva == "U") {
                $observacoes = "Mov. ref. estorno endereçamento - palete " . $idOrigem;
                $params['tipo'] = HistoricoEstoque::TIPO_ENDERECAMENTO;
            }
            elseif ($origemReserva == "E") {
                $observacoes = "Mov. ref. estorno - expedição " . $idOrigem;
                $params['tipo'] = HistoricoEstoque::TIPO_EXPEDICAO;
            }
            elseif ($origemReserva == "O") {
                $observacoes = "Mov. ref. estorno - onda OS " . $idOrigem;
                $params['tipo'] = HistoricoEstoque::TIPO_RESSUPRIMENTO;
            }

            $params['endereco'] = $reservaEstoqueEn->getEndereco();
            $params['observacoes'] = $observacoes;

            foreach ($produtos as $produto) {
                /** @var Produto\Embalagem $embalagenEn */
                $embalagenEn = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findOneBy(array('id'=>$produto['codProdutoEmbalagem']));
                /** @var Produto\Volume $volumeEn */
                $volumeEn = $this->getEntityManager()->getRepository("wms:Produto\Volume")->findOneBy(array('id'=>$produto['codProdutoVolume']));
                $produtoEn = (!empty($embalagenEn))? $embalagenEn->getProduto() : $volumeEn->getProduto();

                $params['validade'] = null;
                if ($produtoEn->getValidade() == 'S' ) {
                    if ($origemReserva == "U") {
                        /** @var \Wms\Domain\Entity\Enderecamento\Palete $umaEn */
                        $umaEn = $this->_em->find('wms:Enderecamento\Palete', $idOrigem);
                        if (!empty($umaEn)) {
                            if (!is_null($umaEn->getValidade())) {
                                $params['validade'] = $umaEn->getValidade()->format('d/m/Y');
                            }
                        }
                    }
                }

                $params['qtd'] = $produto['qtd'] * -1;
                $params['produto'] = $produtoEn;
                $params['volume'] = $volumeEn;
                $params['embalagem'] = $embalagenEn;
                $params['lote'] = $produto['lote'];
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

        if ($reservaEstoqueEn->getAtendida() == "N") {
            $reservaEstoqueEn->setAtendida('C');
            $this->getEntityManager()->flush();
            return true;
        } else {
            /** @var ReservaEstoqueProduto $produto */
            $arr = $reservaEstoqueEn->getProdutos()->toArray();
            $produto = $arr[0];
            $codProduto = $produto->getCodProduto();
            $grade = $produto->getGrade();
            $enderecoEn = $reservaEstoqueEn->getEndereco()->getDescricao();
            $tipoReserva = ($reservaEstoqueEn->getTipoReserva() == "S") ? "saida": "entrada";
            throw new \Exception("Não é permitido cancelar a reserva de $tipoReserva já atendida do produto $codProduto grade $grade, no endereco $enderecoEn");
        }
    }

    private function addReservaEstoque($enderecoEn, $produtos, $tipoReserva, $usuario, $observacoes, $repositorios = null)
    {
        if ($repositorios == null) {
            $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
        }  else {
            $produtoRepo = $repositorios['produtoRepo'];
        }

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
            $produtoEn = $produtoRepo->findOneBy(array('id'=>$produto['codProduto'], 'grade'=>$produto['grade']));
            $reservaEstoqueProduto = new ReservaEstoqueProduto();
            $reservaEstoqueProduto->setProduto($produtoEn);
            if ($produto['codProdutoEmbalagem'] != null) {
                $reservaEstoqueProduto->setCodProdutoEmbalagem($produto['codProdutoEmbalagem']);
                $reservaEstoqueProduto->setProdutoEmbalagem($this->getEntityManager()->getReference("wms:Produto\Embalagem", $produto['codProdutoEmbalagem']));
            }
            if ($produto['codProdutoVolume'] != null)  {
                $reservaEstoqueProduto->setProdutoVolume($this->getEntityManager()->getReference("wms:Produto\Volume",$produto['codProdutoVolume']));
                $reservaEstoqueProduto->setCodProdutoVolume($produto['codProdutoVolume']);
            }
            if (isset($produto['validade']) && !empty($produto['validade'])){
                $arg = explode(" ", $produto['validade']);
                $arrDateISO = explode("-",$arg[0]);
                $arrDateABNT = explode("/",$arg[0]);
                $dataValidade = null;
                if (count($arrDateISO) == 3) {
                    $dataValidade = new \DateTime($produto['validade'], new \DateTimeZone('America/Sao_Paulo'));
                } elseif (count($arrDateABNT) == 3) {
                    $dataValidade = date_create_from_format('d/m/Y',$produto['validade']);
                }
                if ($dataValidade) $reservaEstoqueProduto->setValidade($dataValidade);
            }
            if (!empty($produto['lote']) && $produto['lote'] !== Produto\Lote::NCL){
                $reservaEstoqueProduto->setLote($produto['lote']);
            }
            $reservaEstoqueProduto->setQtd(str_replace(",",".",$produto['qtd']));
            $reservaEstoqueProduto->setQtdOriginal(str_replace(",",".",$produto['qtd']));
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

        $reservaEstoqueUma = $reservaEstoqueUmaRepo->findBy(array('palete' => $paleteEn));
        foreach ($reservaEstoqueUma as $reserva) {
            if ($reserva->getReservaEstoque()->getAtendida() == "N") {
                throw new \Exception("UMA $idUMA já possui uma reserva de entrada");
            }
        }

        $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtos,$tipoReserva,$usuarioReserva,$observacoes);
        $reservaEstoqueUma = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamento();
            $reservaEstoqueUma->setPalete($paleteEn);
            $reservaEstoqueUma->setReservaEstoque($reservaEstoqueEn);
        $this->getEntityManager()->persist($reservaEstoqueUma);

        return $reservaEstoqueEn;
    }

    private function addReservaEstoqueExpedicao ($enderecoEn, $produtos, $criterioReserva, $usuarioReserva, $observacoes,  $repositorios = null){

        if ($repositorios == null) {
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
            $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
        } else {
            $expedicaoRepo = $repositorios['expedicaoRepo'];
            $pedidoRepo = $repositorios['pedidoRepo'];
        }

        $reservaEstoqueEn = $this->findReservaEstoque($enderecoEn->getId(),$produtos,"S","E", $criterioReserva, null, $repositorios);

        if ($reservaEstoqueEn != NULL) {
            $reservaProdutos = $reservaEstoqueEn->getProdutos();
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto $reservaProduto */
            foreach ($reservaProdutos as $reservaProduto) {
                foreach ($produtos as $produto){
                    if (($produto['codProdutoVolume'] == $reservaProduto->getCodProdutoVolume()) &&
                        ($produto['codProdutoEmbalagem'] == $reservaProduto->getCodProdutoEmbalagem())) {
                        $reservaProduto->setQtd($reservaProduto->getQtd() + $produto['qtd']);
                        $this->getEntityManager()->persist($reservaProduto);
                    }
                }
            }
        } else {

            /** @var Expedicao $expedicaoEn */
            $expedicaoEn = $expedicaoRepo->findOneBy(array('id'=>$criterioReserva['expedicao']));
            /** @var Expedicao\Pedido $pedidoEn */
            $pedidoEn = $pedidoRepo->findOneBy(array('id' => $criterioReserva['pedido']));

            $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtos,"S",$usuarioReserva,$observacoes, $repositorios);
            $reservaEstoqueExpedicao = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao();
            $reservaEstoqueExpedicao->setExpedicao($expedicaoEn);
            $reservaEstoqueExpedicao->setReservaEstoque($reservaEstoqueEn);
            $reservaEstoqueExpedicao->setPedido($pedidoEn);
            $reservaEstoqueExpedicao->setQuebraPulmaoDoca($criterioReserva['quebraPulmaoDoca']);
            $reservaEstoqueExpedicao->setCodCriterioPD($criterioReserva['codCriterioPD']);
            $reservaEstoqueExpedicao->setTipoSaida($criterioReserva['tipoSaida']);
            $this->getEntityManager()->persist($reservaEstoqueExpedicao);
        }
        return $reservaEstoqueEn;
    }

    private function addReservaEstoqueOnda ($enderecoEn, $produtos, $tipoReserva, $ondaOsEn,$osEn, $usuarioReserva, $observacoes, $repositorios = null)
    {
        $reservaEstoqueEn = $this->addReservaEstoque($enderecoEn,$produtos,$tipoReserva,$usuarioReserva,$observacoes, $repositorios);

        $reservaEstoqueOnda = new \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueOnda();
            $reservaEstoqueOnda->setReservaEstoque($reservaEstoqueEn);
            $reservaEstoqueOnda->setOs($osEn);
            $reservaEstoqueOnda->setOndaRessuprimentoOs($ondaOsEn);

        $this->getEntityManager()->persist($reservaEstoqueOnda);

        return $reservaEstoqueEn;
    }

    public function getQtdReservadaByProduto($codProduto, $grade, $volume, $idEndereco, $tipo = "E", $lote = Produto\Lote::LND)
    {
        $SQL = "SELECT CASE WHEN SUM(QTD_RESERVADA)IS NULL THEN 0 ELSE SUM(QTD_RESERVADA) END AS QTD
                  FROM RESERVA_ESTOQUE RE
                 INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                 WHERE REP.COD_PRODUTO = '$codProduto'
                   AND REP.DSC_GRADE = '$grade'
                   AND RE.COD_DEPOSITO_ENDERECO = '$idEndereco'
                   AND RE.TIPO_RESERVA = '$tipo'
                   AND RE.IND_ATENDIDA = 'N'";
        if ($volume != NULL) {
            $SQL .= " AND REP.COD_PRODUTO_VOLUME = '$volume' ";
        }

        if ($lote != Produto\Lote::LND) {
            $SQL .= "AND REP.DSC_LOTE = '$lote'";
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result[0]['QTD'];
    }

    public function getResumoReservasNaoAtendidasByParams($params) {
        $SQL = "SELECT 
                       CASE WHEN REEXP.COD_RESERVA_ESTOQUE IS NOT NULL THEN 'Expedição: ' || REEXP.COD_EXPEDICAO || ' Pedido: ' || P.COD_EXTERNO
                            WHEN REOND.COD_RESERVA_ESTOQUE IS NOT NULL THEN 'Ressuprimento: '  || OOS.COD_ONDA_RESSUPRIMENTO
                            WHEN REEND.COD_RESERVA_ESTOQUE IS NOT NULL THEN 'Endereçamento do Palete: '  || REEND.UMA || ' Recebimento: ' || P.COD_RECEBIMENTO
                       END AS ORIGEM,
                       TO_CHAR(RE.DTH_RESERVA,'DD/MM/YYYY HH24:MI:SS') as DTH_RESERVA,
                       CASE WHEN REP.QTD_RESERVADA >= 0 THEN 'ENTRADA'
                            ELSE 'SAÍDA'
                       END AS TIPO,
                       REP.QTD_RESERVADA,
                       P.COD_EXTERNO,
                       DE.DSC_DEPOSITO_ENDERECO,
                       P.NUM_SEQUENCIAL
                  FROM RESERVA_ESTOQUE RE
                  INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                  LEFT JOIN RESERVA_ESTOQUE_ENDERECAMENTO REEND ON REEND.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                  LEFT JOIN PALETE P ON REEND.UMA = P.UMA
                  LEFT JOIN RESERVA_ESTOQUE_ONDA_RESSUP REOND ON REOND.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
				  LEFT JOIN ONDA_RESSUPRIMENTO_OS OOS ON OOS.COD_ONDA_RESSuPRIMENTO_OS = REOND.COD_ONDA_RESSUPRIMENTO_OS
                  LEFT JOIN RESERVA_ESTOQUE_EXPEDICAO REEXP ON REEXP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                  LEFT JOIN DEPOSITO_ENDERECO DE ON RE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                  LEFT JOIN PEDIDO P ON P.COD_PEDIDO = REEXP.COD_PEDIDO
                 WHERE RE.IND_ATENDIDA = 'N'";

        if ($params['idVolume'] == "0") {
            $SQL .= " AND REP.COD_PRODUTO = '$params[idProduto]' ";
            $SQL .= " AND REP.DSC_GRADE = '$params[grade]' ";
        }else {
            $SQL .= " AND REP.COD_PRODUTO_VOLUME = '$params[idVolume]' ";
        }
        if (isset($params['idEndereco']) && !empty($params['idEndereco'])) {
            $SQL .= " AND RE.COD_DEPOSITO_ENDERECO = $params[idEndereco] ";
        }
        if (isset($params['dscLote']) && !empty($params['dscLote'])) {
            $SQL .= " AND REP.DSC_LOTE = '$params[dscLote]' ";
        }
        $result = $this->getEntityManager()->getConnection()->query($SQL . " ORDER BY RE.DTH_RESERVA ")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $key => $value){
            if(!empty($value['NUM_SEQUENCIAL']) && $value['NUM_SEQUENCIAL'] > 1){
                $result[$key]['ORIGEM'] = $value['ORIGEM'].' - '.$value['NUM_SEQUENCIAL'];
            }
        }
        return $result;

    }

    /**
     * @param $produtoPedido Expedicao\PedidoProduto
     * @return array
     */
    public function getReservasExpedicao($produtoPedido)
    {
        $produto = $produtoPedido->getProduto();
        $dql = $this->_em->createQueryBuilder()
            ->select("
                        de.id as idEndereco,
                        ree.quebraPulmaoDoca,
                        ree.codCriterioPD,
                        ree.tipoSaida,
                        rep.codProdutoVolume,
                        (rep.qtd * -1) as qtd,
                        rep.lote as lote
                        ")
            ->from("wms:Ressuprimento\ReservaEstoque", "re")
            ->innerJoin("wms:Ressuprimento\ReservaEstoqueProduto", "rep", "WITH" , "rep.reservaEstoque = re")
            ->innerJoin("wms:Ressuprimento\ReservaEstoqueExpedicao", "ree", "WITH", "ree.reservaEstoque = re")
            ->innerJoin("re.endereco", "de")
            ->leftJoin("wms:Produto\Volume", "pv", "WITH", "pv = rep.produtoVolume and pv.dataInativacao is null")
            ->where("re.atendida = 'N' and ree.pedido = :pedido and rep.codProduto = :codProduto and rep.grade = :grade")
            ->setParameter(":pedido", $produtoPedido->getPedido())
            ->setParameter(":codProduto", $produto->getId())
            ->setParameter(":grade", $produto->getGrade())
            ->orderBy("pv.codigoSequencial, de.rua, de.predio, de.nivel, de.apartamento")
        ;

        return $dql->getQuery()->getResult();

    }

    public function updateReservaExpedicao ($strExp, $codProduto, $grade, $idPicking, $lotes)
    {

        $lnd = Produto\Lote::LND;

        $dql = $this->_em->createQueryBuilder()
            ->select("rep, ree")
            ->from("wms:Ressuprimento\ReservaEstoque", "re")
            ->innerJoin("wms:Ressuprimento\ReservaEstoqueProduto", "rep", "WITH", "rep.reservaEstoque = re")
            ->innerJoin("wms:Ressuprimento\ReservaEstoqueExpedicao", "ree", "WITH", "ree.reservaEstoque = re")
            ->where("re.atendida = 'N' and ree.expedicao in ($strExp) and rep.lote = '$lnd' and re.endereco = :idPicking and rep.codProduto = :codProduto and rep.grade = :grade")
            ->setParameter(":codProduto", $codProduto)
            ->setParameter(":grade", $grade)
            ->setParameter(":idPicking", $idPicking);

        /** @var ReservaEstoqueProduto[] $reservas */
        $reservas = $dql->getQuery()->getResult();

        $arrReservas = [];
        $arrResExp = [];
        foreach ($reservas as $entity) {
            if (is_a($entity, ReservaEstoqueProduto::class))
                $arrReservas[$entity->getId()] = [
                    'qtdTotal' => Math::multiplicar($entity->getQtd(), -1),
                    'repEnMatriz' => $entity,
                    'lotes' => [],
                    'qtdPrometida' => 0,
                    'atendida' => false
                ];
            if (is_a($entity, ReservaEstoqueExpedicao::class))
                $arrResExp[$entity->getReservaEstoque()->getId()] = $entity;
        }

        foreach ($lotes as $lotePrometido => $val) {

            $qtdLote = $val['QTD'];

            foreach ($arrReservas as $idReserva => $reserva) {
                if (!$reserva['atendida']) {

                    $qtdPendente = Math::subtrair($reserva['qtdTotal'], $reserva['qtdPrometida']);

                    if (Math::compare($qtdPendente, $qtdLote, ">")) {

                        $qtdPrometida = $arrReservas[$idReserva]['qtdPrometida'];
                        $arrReservas[$idReserva]['qtdPrometida'] = Math::adicionar($qtdPrometida, $qtdLote);
                        $arrReservas[$idReserva]['lotes'][$lotePrometido] = [
                            'qtdLote' => $qtdLote
                        ];

                        $qtdLote = 0;

                    } else {
                        $arrReservas[$idReserva]['atendida'] = true;
                        $arrReservas[$idReserva]['qtdPrometida'] = $reserva['qtdTotal'];
                        $arrReservas[$idReserva]['lotes'][$lotePrometido] = [
                            'qtdLote' => $qtdPendente
                        ];

                        $qtdLote = Math::subtrair($qtdLote, $qtdPendente);
                    }
                }

                if ($qtdLote == 0) break;
            }
        }

        /** @var Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
        $pedidoProdutoRepo = $this->_em->getRepository("wms:Expedicao\PedidoProduto");
        /** @var Expedicao\PedidoProdutoLoteRepository $pedProdLoteRepo */
        $pedProdLoteRepo = $this->_em->getRepository("wms:Expedicao\PedidoProdutoLote");

        foreach ($arrReservas as $reserva) {
            /** @var ReservaEstoqueProduto $repEn */
            $repEn = $reserva['repEnMatriz'];
            $primeiroLote = key($reserva['lotes']);

            /** @var ReservaEstoqueExpedicao $reeEn */
            $reeEn = $arrResExp[$repEn->getId()];
            $criterioReserva = array(
                'expedicao' => $reeEn->getExpedicao()->getId(),
                'pedido' => $reeEn->getPedido()->getId(),
                'tipoSaida' => $reeEn->getTipoSaida(),
                'quebraPulmaoDoca' => $reeEn->getQuebraPulmaoDoca(),
                'codCriterioPD' => $reeEn->getCodCriterioPD()
            );

            $pedidoProduto = $pedidoProdutoRepo->findOneBy([
                'pedido' => $reeEn->getPedido(),
                'codProduto' => $codProduto,
                'grade' => $grade
            ]);

            if (count($reserva['lotes']) > 1) {

                foreach ($reserva['lotes'] as $lote => $val) {

                    $pedProdLoteData = [
                        'lote' => $lote,
                        'pedidoProduto' => $pedidoProduto,
                        'codPedidoProduto' => $pedidoProduto->getId(),
                        'quantidade' => $val['qtdLote'],
                        'definicao' => Expedicao\PedidoProdutoLote::DEF_WMS
                    ];

                    if ($lote == $primeiroLote) {
                        $repEn->setQtd($val['qtdLote'] * -1);
                        $repEn->setLote($primeiroLote);
                        $this->_em->persist($repEn);
                        $pedProdLoteRepo->update($pedProdLoteData);
                    } else {
                        $idElemento = (!empty($repEn->getCodProdutoEmbalagem())) ? "E-" . $repEn->getCodProdutoEmbalagem() : "V-" . $repEn->getCodProdutoVolume();
                        $arr[$idElemento] = [
                           'codProdutoEmbalagem' => $repEn->getCodProdutoEmbalagem(),
                           'codProdutoVolume' => $repEn->getCodProdutoVolume(),
                           'codProduto' => $codProduto,
                           'grade' => $grade,
                           'qtd' => $val['qtdLote'] * -1,
                           'lote' => $lote,
                        ];
                        self::adicionaReservaEstoque($idPicking, $arr, "S", "E", $criterioReserva);
                        $pedProdLoteRepo->save($pedProdLoteData);
                    }

                }
            } else {
                $repEn->setLote($primeiroLote);
                $this->_em->persist($repEn);

                $pedProdLoteRepo->update([
                    'lote' => $primeiroLote,
                    'pedidoProduto' => $pedidoProduto,
                    'codPedidoProduto' => $pedidoProduto->getId(),
                    'quantidade' => $arrReservas[$repEn->getId()]['lotes'][$primeiroLote]['qtdLote'],
                    'definicao' => Expedicao\PedidoProdutoLote::DEF_WMS
                ]);
            }
        }
    }
}
