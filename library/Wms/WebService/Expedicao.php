<?php

use Wms\Domain\Entity\Expedicao,
    Core\Util\Produto as ProdutoUtil,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;

class cliente {
    /** @var string */
    public $codCliente;
    /** @var string */
    public $nome;
    /** @var string */
    public $cpf_cnpj;
    /** @var string */
    public $tipoPessoa;
    /** @var string */
    public $logradouro;
    /** @var string */
    public $numero;
    /** @var string */
    public $bairro;
    /** @var string */
    public $cidade;
    /** @var string */
    public $uf;
    /** @var string */
    public $complemento;
    /** @var string */
    public $referencia;
}

class itinerario {
    /** @var string */
    public $idItinerario;
    /** @var string */
    public $nomeItinerario;
}

class produto {
    /** @var string */
    public $codProduto;
    /** @var string */
    public $grade;
    /** @var string */
    public $quantidade;
    /** @var string */
    public $quantidadeAtendida;
}

class pedido {
    /** @var string */
    public $codPedido;
    /** @var string */
    public $linhaEntrega;
    /** @var itinerario */
    public $itinerario;
    /** @var cliente */
    public $cliente;
    /** @var string */
    public $situacao;
    /** @var  boolean */
    public $conferido;
    /** @var produto[] */
    public $produtos = array();
}

class pedidos {
    /** @var pedido[] */
    public $pedidos = array();
}

class carga {
    /** @var string */
    public $codCarga;
    /** @var string */
    public $tipo;
    /** @var string */
    public $situacao;
    /** @var string */
    public $veiculo;
    /** @var string */
    public $dataFechamento;
    /** @var pedido[] */
    public $pedidos = array();
    /** @var string */
    public $motorista;
    /** @var string */
    public $linhaEntrega;
    /** @var double */
    public $peso;
    /** @var double */
    public $cubagem;
    /** @var double */
    public $valor;
    /** @var int */
    public $volumes;
    /** @var int */
    public $entregas;
    /** @var int */
    public $qtdPedidos;
}

class pedidoFaturado {
    /** @var string */
    public $codPedido;
    /** @var string */
    public $tipoPedido;
}

class notaFiscal {
    /** @var pedidoFaturado[] */
    public $pedidos;
    /** @var integer */
    public $numeroNf;
    /** @var string */
    public $serieNf;
    /** @var string */
    public $cnpjEmitente;
    /** @var double */
    public $valorVenda;
    /** @var notaFiscalProduto[] */
    public $itens;
}

class notaFiscalProduto {
    /** @var string */
    public $codProduto;
    /** @var string */
    public $grade;
    /** @var integer */
    public $qtd;
    /** @var double */
    public $valorVenda;
}

class Wms_WebService_Expedicao extends Wms_WebService
{

    private $_em;

    public function __construct()
    {
        $this->_em = $this->__getDoctrineContainer()->getEntityManager();
    }

    /**
     *  Recebe Carga com Placa da Expedição
     *  Verifica se existe expedição aberta(Integrado, Em Separação ou Em Conferencia) com a placa da carga,
     *  Se existir retorna código da expedição senão Insere na tabela expedição
     *  Insere na tabela de carga com o numero da expedição
     *
     * @param string cargas informacoes das cargas com os pedidos
     * @return boolean Se as cargas foram salvas com sucesso
     */
    public function enviarJson ($cargas){

        $writer = new Zend_Log_Writer_Stream(DATA_PATH.'/log/'.date('Y-m-d').'-enviarJson.log');
        $logger = new Zend_Log($writer);

        try {
            $cargas = str_replace("/","",$cargas);
            $cargas = str_replace('\\','',$cargas);

            ini_set('max_execution_time', 3000);

            $array = json_decode($cargas, true);
            if (!is_array($array)) {throw new \Exception("Formato de dados incorreto - Não está formatado como JSON");}

            $arrayCargas = $array['cargas'];
            $result = $this->enviar($arrayCargas);
            $logger->debug($cargas);

            ini_set('max_execution_time', 30);

            if ($result == true) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            $logger->warn($e->getMessage());
            throw new \Exception($e->getMessage() . ' - Trace: ' .$e->getTraceAsString());
            /*$showTrace = true;
            if ($showTrace == true) {
                throw new \Exception($e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            } else {
                throw new \Exception($e->getMessage());
            }*/
            return false;
        }
    }

    /**
     *  Recebe Carga com Placa da Expedição
     *  Verifica se existe expedição aberta(Integrado, Em Separação ou Em Conferencia) com a placa da carga,
     *  Se existir retorna código da expedição senão Insere na tabela expedição
     *  Insere na tabela de carga com o numero da expedição
     *
     * @param string codCarga informacoes das cargas com os pedidos
     * @param string placa informacoes das cargas com os pedidos
     * @param pedidos pedidos informacoes das cargas com os pedidos
     * @return boolean Se as cargas foram salvas com sucesso
     */
    public function enviarPedidos ($codCarga, $placa, $pedidos) {
        $pedidosArray = array();
        foreach ($pedidos->pedidos as $pedidoWs) {
            $cliente = array();
            $cliente['codCliente'] = $pedidoWs->cliente->codCliente;
            $cliente['bairro'] = $pedidoWs->cliente->bairro;
            $cliente['cidade'] = $pedidoWs->cliente->cidade;
            $cliente['complemento'] = $pedidoWs->cliente->complemento;
            $cliente['cpf_cnpj'] = $pedidoWs->cliente->cpf_cnpj;
            $cliente['logradouro'] = $pedidoWs->cliente->logradouro;
            $cliente['nome'] = $pedidoWs->cliente->nome;
            $cliente['numero'] = $pedidoWs->cliente->numero;
            $cliente['referencia'] = $pedidoWs->cliente->referencia;
            $cliente['tipoPessoa'] = $pedidoWs->cliente->tipoPessoa;
            $cliente['uf'] = $pedidoWs->cliente->uf;

            $itinerario = array();
            $itinerario['idItinerario'] = $pedidoWs->itinerario->idItinerario;
            $itinerario['nomeItinerario'] = $pedidoWs->itinerario->nomeItinerario;

            $produtos = array();
            foreach ($pedidoWs->produtos as $produtoWs) {
                $produto['codProduto'] = $produtoWs->codProduto;
                $produto['grade'] = $produtoWs->grade;
                $produto['quantidade'] = $produtoWs->quantidade;
                $produtos[] = $produto;
            }

            $pedido = array();
            $pedido['codPedido'] = $pedidoWs->codPedido;
            $pedido['cliente'] = $cliente;
            $pedido['itinerario'] = $itinerario;
            $pedido['produtos'] = $produtos;
            $pedido['linhaEntrega'] = $pedidoWs->linhaEntrega;

            $pedidosArray[] = $pedido;
        }

        $carga = array();
        $carga['idCarga'] = $codCarga;
        $carga['placaExpedicao'] = $placa;
        $carga['placa'] = $placa;
        $carga['pedidos'] = $pedidosArray;

        $cargas = array();
        $cargas[] = $carga;

        return $this->enviar($cargas);
    }


    /**
     *  Recebe Carga com Placa da Expedição
     *  Verifica se existe expedição aberta(Integrado, Em Separação ou Em Conferencia) com a placa da carga,
     *  Se existir retorna código da expedição senão Insere na tabela expedição
     *  Insere na tabela de carga com o numero da expedição
     *
     * @param array cargas informacoes das cargas com os pedidos
     * @return boolean Se as cargas foram salvas com sucesso
     */
    public function enviar($cargas, $isIntegracaoSQL = false)
    {
        $cargas = $this->trimArray($cargas);
        ini_set('max_execution_time', -1);
        try {
            $this->_em->beginTransaction();

            $repositorios = array(
                'produtoRepo'=> $this->_em->getRepository('wms:Produto'),
                'pedidoRepo' => $this->_em->getRepository('wms:Expedicao\Pedido'),
                'pedidoProdutoRepo' => $this->_em->getRepository('wms:Expedicao\PedidoProduto'),
                'etiquetaRepo' => $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao'),
                'expedicaoRepo' => $this->_em->getRepository('wms:Expedicao'),
                'pedidoEnderecoRepo' => $this->_em->getRepository('wms:Expedicao\PedidoEndereco'),
                'cargaRepo' => $this->_em->getRepository('wms:Expedicao\Carga'),
                'clienteRepo' => $this->_em->getRepository('wms:Pessoa\Papel\Cliente'),
                'pessoaJuridicaRepo' => $this->_em->getRepository('wms:Pessoa\Juridica'),
                'pessoaFisicaRepo' => $this->_em->getRepository('wms:Pessoa\Fisica'),
                'siglaRepo' => $this->_em->getRepository('wms:Util\Sigla'),
                'itinerarioRepo' =>$this->_em->getRepository('wms:Expedicao\Itinerario')
            );

            foreach($cargas as $k1 => $carga) {
                foreach ($carga['pedidos'] as  $k2 => $pedido) {
                    foreach ($pedido['produtos'] as $k3 => $produto){
                        $idProduto = trim($cargas[$k1]['pedidos'][$k2]['produtos'][$k3]['codProduto']);
                        $idProduto = ProdutoUtil::formatar($idProduto);
                        $cargas[$k1]['pedidos'][$k2]['produtos'][$k3]['codProduto'] = $idProduto;
                    }
                }
                $this->checkProductsExists($repositorios, $carga['pedidos']);
                $result = $this->checkPedidosExists($repositorios, $carga['pedidos'], $isIntegracaoSQL);

                if ($result) {
                    $this->_em->flush();
                    $this->saveCarga($repositorios, $carga);
                }
            }
            $this->_em->flush();
            $this->_em->commit();
            return true;
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
            return false;
        }
    }

    /**
     * @param integer $idCargaExterno
     * @param string $tipoCarga
     * @return boolean Se a carga for fechada com sucesso
     */
    public function fechar($idCargaExterno,$tipoCarga)
    {
        $idCargaExterno = trim ($idCargaExterno);
        if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
        $tipoCarga = trim($tipoCarga);

        $siglaTipoCarga = $this->verificaTipoCarga($tipoCarga);

        $cargaRepository = $this->_em->getRepository('wms:Expedicao\Carga');
        $cargaEntity = $cargaRepository->findOneBy(array('codCargaExterno'=>$idCargaExterno,'tipoCarga'=>$siglaTipoCarga->getID()));

        if ($cargaEntity != null) {
            $cargaEntity->setDataFechamento(new \DateTime());
            $this->_em->persist($cargaEntity);
            $this->_em->flush();
            return true;
        }
        return false;
    }

    /**
     * @param integer $idCargaExterno
     * @param string $tipoCarga
     * @return boolean Se a carga for cancelada com sucesso
     */
    public function cancelarCarga($idCargaExterno, $tipoCarga)
    {
        try {
            $writer = new Zend_Log_Writer_Stream(DATA_PATH.'/log/'.date('Y-m-d').'-cancelarCarga.log');
            $logger = new Zend_Log($writer);
            $logger->debug("Carga:$idCargaExterno  - $tipoCarga");

            $idCargaExterno = trim ($idCargaExterno);
            if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
            $tipoCarga = trim($tipoCarga);

            $siglaTipoCarga = $this->verificaTipoCarga($tipoCarga);

            /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepository */
            $cargaRepository = $this->_em->getRepository('wms:Expedicao\Carga');
            return $cargaRepository->cancelar($idCargaExterno,$siglaTipoCarga);
        } catch (\Exception $e) {
            $logger->warn($e->getMessage());
            throw new \Exception($e->getMessage() . ' - ' . $e->getTraceAsString());
        }

    }

    /**
     * @param integer $idCargaExterno
     * @param string $tipoCarga
     * @param string $tipoPedido
     * @param string $idPedido
     * @return bool|Exception
     * @throws Exception
     */
    public function cancelarPedido ($idCargaExterno, $tipoCarga, $tipoPedido,$idPedido)
    {
        $writer = new Zend_Log_Writer_Stream(DATA_PATH.'/log/'.date('Y-m-d').'-cancelarPedido.log');
        $logger = new Zend_Log($writer);
        try {
            $this->_em->beginTransaction();


            $logger->debug("Pedido: $idPedido");

            $idPedido = trim($idPedido);

            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepository */
            $pedidoRepository = $this->_em->getRepository('wms:Expedicao\Pedido');

            /** @var \Wms\Domain\Entity\Expedicao\Pedido $EntPedido */
            $EntPedido = $pedidoRepository->find($idPedido);

            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo  */
            $mapaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');

            if ($mapaSeparacaoRepo->validaMapasCortados($idPedido) == false) {
                throw new \Exception("Pedido $idPedido precisa ser cortado no WMS");
            }

            $pedidoRepository->cancelar($idPedido);
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepository  */
            $ExpedicaoRepository = $this->_em->getRepository('wms:Expedicao');
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo  */
            $etiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

            $idExpedicao = $EntPedido->getCarga()->getExpedicao()->getId();
            $pedidosNaoCancelados = $ExpedicaoRepository->countPedidosNaoCancelados($idExpedicao);
            if ($pedidosNaoCancelados == 0) {
                $qtdCorte     = $etiquetaSeparacaoRepo->getEtiquetasByStatus(EtiquetaSeparacao::STATUS_CORTADO,$idExpedicao);
                $qtdEtiquetas = $etiquetaSeparacaoRepo->getEtiquetasByStatus(null,$idExpedicao);
                if ($qtdCorte == $qtdEtiquetas) {
                    $ExpedicaoEn = $ExpedicaoRepository->find($idExpedicao);
                    $ExpedicaoRepository->alteraStatus($ExpedicaoEn, Expedicao::STATUS_CANCELADO);
                    $this->_em->flush();
                }
            }

            $this->_em->commit();

        } catch (\Exception $e) {
            $this->_em->rollback();
            $logger->warn($e->getMessage());
            throw new \Exception($e->getMessage(), null, $e);
        }

        return true;
    }

    /**
     * @param integer $idCargaExterno
     * @param string $tipoCarga
     * @return array Se a carga está finalizada ou nâo
     */
    public function checarStatus($idCargaExterno,$tipoCarga) {
        $idCargaExterno = trim ($idCargaExterno);
        if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
        $tipoCarga = trim($tipoCarga);

        $siglaTipoCarga = $this->verificaTipoCarga($tipoCarga);

        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepo */
        $cargaRepo     = $this->_em->getRepository('wms:Expedicao\Carga');
        /** @var \Wms\Domain\Entity\Expedicao\Carga $carga */
        $carga = $cargaRepo->findOneBy(array('codCargaExterno'=>$idCargaExterno, 'tipoCarga'=>$siglaTipoCarga));

        if ($carga == null) {
            throw new \Exception('Carga não encontrada');
        }

        /** @var \Wms\Domain\Entity\Expedicao $expedicao */
        $expedicao = $carga->getExpedicao();

        if (($expedicao->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_FINALIZADO) ||
            ($expedicao->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_SEGUNDA_CONFERENCIA) ||
            ($expedicao->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_PARCIALMENTE_FINALIZADO)) {
            return array('liberado' => true);
        } else {
            return array('liberado' => false);
        }
    }

    /**
     * @param integer $idCarga
     * @param string $tipoCarga
     * @return array Com informações das etiquetas
     */
    public function consultarEtiquetas($idCargaExterno, $tipoCarga)
    {
        $idCargaExterno = trim ($idCargaExterno);
        if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
        $tipoCarga = trim($tipoCarga);

        $siglaTipoCarga = $this->verificaTipoCarga($tipoCarga);
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo     = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $etiquetas = $etiquetaRepo->getEtiquetasByCargaExterno($idCargaExterno, $siglaTipoCarga->getID());
        if ($etiquetas == null) {
            throw new \Exception('Etiquetas não encontradas para a carga especificada');
        }
        return $etiquetas;
    }

    /**
     * @param integer $idCarga
     * @param string $tipoCarga
     * @return carga Com informações das etiquetas
     */
    public function consultarCarga($idCargaExterno,$tipoCarga){

        $idCargaExterno = trim ($idCargaExterno);
        if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
        $tipoCarga = trim($tipoCarga);

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo     = $this->_em->getRepository('wms:Expedicao\Pedido');

        $siglaTipoCarga = $this->verificaTipoCarga($tipoCarga);
        $cargaEn = $this->_em->getRepository('wms:Expedicao\Carga')->findOneBy(array('codCargaExterno'=>$idCargaExterno,'tipoCarga'=>$siglaTipoCarga->getId()));
        if ($cargaEn == null) {
            throw new \Exception($tipoCarga . " " . $idCargaExterno . " não encontrado");
        }

        $carga = new carga();
        $carga->codCarga = $idCargaExterno;
        $carga->tipo = $tipoCarga;
        $carga->situacao = $cargaEn->getExpedicao()->getStatus()->getSigla();
        $carga->pedidos = array();
        $pedidosEn = $pedidoRepo->findBy(array('codCarga'=>$cargaEn->getId()));

        /** @var \Wms\Domain\Entity\Expedicao\Pedido $pedidoEn */
        foreach ($pedidosEn as $pedidoEn) {
            $itinerario = new itinerario();
            if ($pedidoEn->getItinerario() == null) {
                $itinerario->idItinerario = "";
                $itinerario->nomeItinerario = "";
            } else {
                $itinerario->idItinerario = $pedidoEn->getItinerario()->getId();
                $itinerario->nomeItinerario = $pedidoEn->getItinerario()->getDescricao();
            }

            $cliente = new cliente();
            $cliente->codCliente = $pedidoEn->getPessoa()->getCodClienteExterno();
            $cliente->nome = $pedidoEn->getPessoa()->getPessoa()->getNome();
            if (get_class($pedidoEn->getPessoa()->getPessoa()) == "Wms\Domain\Entity\Pessoa\Fisica"){
                $cliente->cpf_cnpj = $pedidoEn->getPessoa()->getPessoa()->getCpf();
                $cliente->tipoPessoa = "F";
            } else {
                $cliente->cpf_cnpj = $pedidoEn->getPessoa()->getPessoa()->getCnpj();
                $cliente->tipoPessoa = "J";
            }

            $enderecos = $pedidoEn->getPessoa()->getPessoa()->getEnderecos();
            if (count($enderecos) >0) {
                $cliente->logradouro = $enderecos[0]->getDescricao();
                $cliente->numero = $enderecos[0]->getNumero();
                $cliente->bairro = $enderecos[0]->getBairro();
                $cliente->complemento = $enderecos[0]->getComplemento();
                $cliente->cidade = $enderecos[0]->getLocalidade();
                $cliente->referencia = $enderecos[0]->getPontoReferencia();
                $cliente->uf = $enderecos[0]->getUf()->getReferencia();

            }

            $pedido = new pedido();
            $pedido->codPedido = $pedidoEn->getId();
            $pedido->produtos = array();
            $pedido->linhaEntrega = $pedidoEn->getLinhaEntrega();
            $pedido->itinerario = $itinerario;
            $pedido->cliente = $cliente;
            $pedido->conferido = $pedidoRepo->getSituacaoPedido($pedidoEn->getId());
            $produtos = $pedidoRepo->getQtdPedidaAtendidaByPedido($pedidoEn->getId());
            foreach ($produtos as $item) {
                $produto = new produto();
                $produto->codProduto = $item['COD_PRODUTO'];
                $produto->grade = $item['DSC_GRADE'];
                $produto->quantidade = $item['QTD_PEDIDO'];
                if (is_null($item['ATENDIDA'])) {
                    $produto->quantidadeAtendida = 0;
                } else {
                    if ($pedidoEn->getCarga()->getExpedicao()->getStatus()->getId() == EXPEDICAO::STATUS_FINALIZADO) {
                        $produto->quantidadeAtendida = $item['ATENDIDA'];
                    } else {
                        $produto->quantidadeAtendida = 0;
                    }
                }
                $pedido->produtos[] = $produto;
            }
            $carga->pedidos[] = $pedido;
        }

        return $carga;
    }

    /**
     * @param string $idPedido
     * @return pedido Informações sobre o pedido
     */
    public function consultarPedido($idPedido)
    {
        $idPedido = trim($idPedido);

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');
        $pedidoEn = $pedidoRepo->find($idPedido);

        if ($pedidoEn == null) {
            throw new \Exception("Pedido $idPedido não encontrado");
        }

        $itinerario = new itinerario();
        if ($pedidoEn->getItinerario() == null) {
            $itinerario->idItinerario = "";
            $itinerario->nomeItinerario = "";
        } else {
            $itinerario->idItinerario = $pedidoEn->getItinerario()->getId();
            $itinerario->nomeItinerario = $pedidoEn->getItinerario()->getDescricao();
        }

        $cliente = new cliente();
        $cliente->codCliente = $pedidoEn->getPessoa()->getCodClienteExterno();
        $cliente->nome = $pedidoEn->getPessoa()->getPessoa()->getNome();
        if (get_class($pedidoEn->getPessoa()->getPessoa()) == "Wms\Domain\Entity\Pessoa\Fisica"){
            $cliente->cpf_cnpj = $pedidoEn->getPessoa()->getPessoa()->getCpf();
            $cliente->tipoPessoa = "F";
        } else {
            $cliente->cpf_cnpj = $pedidoEn->getPessoa()->getPessoa()->getCnpj();
            $cliente->tipoPessoa = "J";
        }

        $enderecos = $pedidoEn->getPessoa()->getPessoa()->getEnderecos();
        if (count($enderecos) >0) {
            $cliente->logradouro = $enderecos[0]->getDescricao();
            $cliente->numero = $enderecos[0]->getNumero();
            $cliente->bairro = $enderecos[0]->getBairro();
            $cliente->complemento = $enderecos[0]->getComplemento();
            $cliente->cidade = $enderecos[0]->getLocalidade();
            $cliente->referencia = $enderecos[0]->getPontoReferencia();
            $cliente->uf = $enderecos[0]->getUf()->getReferencia();

        }

        $result = new pedido();
        $result->codPedido = $idPedido;
        $result->cliente = $cliente;
        $result->itinerario = $itinerario;
        $result->linhaEntrega = $pedidoEn->getLinhaEntrega();
        $result->situacao = $pedidoEn->getCarga()->getExpedicao()->getStatus()->getSigla();
        $result->conferido = $pedidoRepo->getSituacaoPedido($idPedido);
        $produtos = $pedidoRepo->getQtdPedidaAtendidaByPedido($pedidoEn->getId());
        foreach ($produtos as $item) {
            $produto = new produto();
            $produto->codProduto = $item['COD_PRODUTO'];
            $produto->grade = $item['DSC_GRADE'];
            $produto->quantidade = $item['QTD_PEDIDO'];
            if (is_null($item['ATENDIDA'])) {
                $produto->quantidadeAtendida = 0;
            } else {
                if ($pedidoEn->getCarga()->getExpedicao()->getStatus()->getId() == EXPEDICAO::STATUS_FINALIZADO) {
                    $produto->quantidadeAtendida = $item['ATENDIDA'];
                } else {
                    $produto->quantidadeAtendida = 0;
                }
            }
            $result->produtos[] = $produto;
        }

        return $result;
    }

    protected function saveCarga($repositorios, $carga)
    {
        //CASO OS CAMPOS SEJAM OMITIDOS, PREENCHO COM O VALOR PADRÃO
        if (!isset($carga['tipoCarga']) or $carga['tipoCarga'] == "") {
            $carga['tipoCarga'] = "C";
        }
        if (!isset($carga['centralEntrega']) or $carga['centralEntrega'] == "") {
            $carga['centralEntrega'] = "1";
        }
        if (!isset($carga['placaExpedicao']) or $carga['placaExpedicao'] == "") {
            $carga['placaExpedicao'] = $carga['idCarga'];
        }
        if (!isset($carga['placa']) or $carga['placa'] == "") {
            $carga['placa'] = $carga['placaExpedicao'];
        }

        $arrayCarga = array(
            'codCargaExterno' => $carga['idCarga'],
            'codTipoCarga' => $carga['tipoCarga'],
            'centralEntrega' => $carga['centralEntrega'],
            'placaCarga' => $carga['placa'],
            'placaExpedicao' => $carga['placaExpedicao']
        );

        /** @var \Wms\Domain\Entity\Expedicao $expedicaoEntity */
        $entityExpedicao = $this->findExpedicaoByPlacaExpedicao($repositorios, $carga['placaExpedicao']);

        if (isset($expedicaoEntity) && is_object($expedicaoEntity)) {
            $hoje = new \DateTime("now");
            if ($expedicaoEntity->getDataInicio()->format('Y-m-d') != $hoje->format('Y-m-d')) {
                throw new \Exception('Existem expedições antigas para a placa ' . $carga['placaExpedicao'] . ' abertas no sistema');
            }
        }

        $arrayCarga['idExpedicao'] = $entityExpedicao;
        $entityCarga = $this->findCargaByTipoCarga($repositorios, $arrayCarga);

        $i = 0;
        $cargaRepository = $this->_em->getRepository('wms:Expedicao\Carga');
        foreach ($carga['pedidos'] as $pedido) {
            $cargaEntity = $cargaRepository->find($entityCarga->getId());
            $this->savePedido($repositorios, $pedido, $cargaEntity);
            $this->_em->flush();
            $i++;
            if ($i == 50) $this->_em->clear();

        }
    }

    protected function savePedido ($repositorios, array $pedido, $entityCarga) {
        if (!isset($pedido['tipoPedido']) or $pedido['tipoPedido'] == "") {
            $pedido['tipoPedido'] = "ENTREGA";
        }
        if (!isset($pedido['linhaEntrega']) or $pedido['linhaEntrega'] == "") {
            $pedido['linhaEntrega'] = "(PADRAO)";
        }
        if (!isset($pedido['centralEntrega']) or $pedido['centralEntrega'] == "") {
            $pedido['centralEntrega'] = "1";
        }
        if (!isset($pedido['pontoTransbordo']) or $pedido['pontoTransbordo'] == "") {
            $pedido['pontoTransbordo'] = "1";
        }
        if (!isset($pedido['pontoTransbordo']) or $pedido['pontoTransbordo'] == "") {
            $pedido['pontoTransbordo'] = "1";
        }
        if (!isset($pedido['itinerario']) or $pedido['itinerario'] == "") {
            $itinerario = array();
            $itinerario['idItinerario'] = "";
            $itinerario['nomeItinerario'] = "";
            $pedido['itinerario'] = $itinerario;
        }

        $cliente = $pedido['cliente'];
        if (isset($cliente[0]) && is_array($cliente[0])) {
            $cliente = $cliente[0];
        }

        $entityCliente          = $this->findClienteByCodigoExterno($repositorios,$cliente);
        $entityItinerario       = $this->findItinerarioById($repositorios, $pedido['itinerario']);

        $arrayPedido = array (
            'codPedido' => $pedido['codPedido'],
            'tipoPedido' => $pedido['tipoPedido'],
            'linhaEntrega' => $pedido['linhaEntrega'],
            'centralEntrega' => $pedido['centralEntrega'],
            'carga' => $entityCarga,
            'itinerario' => $entityItinerario,
            'pessoa' => $entityCliente,
            'pontoTransbordo' => $pedido['pontoTransbordo'],
            'envioParaLoja' => (isset($pedido['envioParaLoja'])) ? $pedido['envioParaLoja'] : null
        );

        $entityPedido  = $this->findPedidoById($repositorios, $arrayPedido);
        $this->savePedidoProduto($repositorios, $pedido['produtos'], $entityPedido);

        /** @var \Wms\Domain\Entity\Expedicao\PedidoEnderecoRepository $pedidoEnderecoRepo */
        $pedidoEnderecoRepo = $repositorios['pedidoEnderecoRepo'];
        $pedidoEnderecoRepo->save($entityPedido,$pedido['cliente']);
    }

    protected function savePedidoProduto($repositorios, array $produtos, Expedicao\Pedido $enPedido) {
        $ProdutoRepo        = $repositorios['produtoRepo'];
        $PedidoProdutoRepo  = $repositorios['pedidoProdutoRepo'];

        foreach ($produtos as $produto) {
            $idProduto = trim($produto['codProduto']);
            $idProduto = ProdutoUtil::formatar($idProduto);

            $enProduto = $ProdutoRepo->find(array('id' => $idProduto, 'grade' => $produto['grade']));
            if (isset($produto['quantidade'])) {
                $produto['qtde'] = $produto['quantidade'];
            }

            $prod = array(
                'codPedido' => $enPedido->getId(),
                'pedido' => $enPedido,
                'produto' => $enProduto,
                'valorVenda' =>$produto['valorVenda'],
                'grade' => $produto['grade'],
                'quantidade' => str_replace(',','.',$produto['qtde'])
            );
            $PedidoProdutoRepo->save($prod);
        }
    }

    /**
     * @param array $pedidos
     * @throws Exception
     */
    protected function checkPedidosExists($repositorios, array $pedidos, $isIntegracaoSQL = false) {

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $PedidoRepo */
        $PedidoRepo = $repositorios['pedidoRepo'];

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $repositorios['etiquetaRepo'];

        foreach ($pedidos as $pedido) {
            $PedidoEntity = $PedidoRepo->find($pedido['codPedido']);
            if ($PedidoEntity != null) {
                /*
                 * PEDIDO DA SONOSHOW, ELES QUEREM LIBERAR O SISTEMA SEM VALIDAR O CORTE, ISTO NÂO DEVE SER PARAMETRO
                 * DEVE SER ACERTO DE PROCESSO, PORÉM ATÈ ACERTAREM O PROCESSO FOI PEDIDO PARA NÃO FAZER VALIDAÇÃO
                 * ATÉ ACERTAREM ESTE PROCESSO CRIEI O BOOLEAN CHAMADO SONOSHOW PARA DELETAR QUANDO ACERTAREM O PROCESSO
                 */
                $sonoshow = false;

                if ($sonoshow == true) {

                    $PedidoRepo->removeReservaEstoque($pedido['codPedido'],false);
                    $PedidoRepo->remove($PedidoEntity,false);

                } else {

                    $statusExpedicao = $PedidoEntity->getCarga()->getExpedicao()->getStatus();
                    $qtdTotal = count($EtiquetaRepo->getEtiquetasByPedido($pedido['codPedido']));
                    $qtdCortadas = count($EtiquetaRepo->getEtiquetasByPedido($pedido['codPedido'],EtiquetaSeparacao::STATUS_CORTADO));

                    if (($statusExpedicao->getId() == Expedicao::STATUS_FINALIZADO) ||
                        ($statusExpedicao->getId() == Expedicao::STATUS_INTEGRADO) ||
                        ($statusExpedicao->getId() == Expedicao::STATUS_PARCIALMENTE_FINALIZADO) ||
                        ($qtdCortadas == $qtdTotal)) {

                        if (count($EtiquetaRepo->getMapaByPedido($pedido['codPedido'])) > 0) {
                            if (!$isIntegracaoSQL)
                                throw new Exception("Pedido $pedido[codPedido] possui mapa de separacao em conferencia");
                            else
                                return false;
                        }

                        $PedidoRepo->removeReservaEstoque($pedido['codPedido'],false);
                        $PedidoRepo->remove($PedidoEntity,false);

                    } else {
                        if ($qtdCortadas > 0) {
                            if (!$isIntegracaoSQL)
                                throw new Exception("Pedido $pedido[codPedido] possui etiquetas que precisam ser cortadas - Cortadas: ");
                            else
                                return false;
                        }

                        if (!$isIntegracaoSQL)
                            throw new Exception("Pedido " . $pedido['codPedido'] . " se encontra " . strtolower( $statusExpedicao->getSigla()));
                        else
                            return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param array $pedidos
     * @throws Exception
     */
    protected function checkProductsExists($repositorios, array $pedidos) {
        $ProdutoRepo = $repositorios['produtoRepo'];

        foreach($pedidos as $pedido) {

            foreach($pedido['produtos'] as $produto) {
                $idProduto = trim($produto['codProduto']);
                $idProduto = ProdutoUtil::formatar($idProduto);
                $grade = trim($produto['grade']);
                if ($ProdutoRepo->find(array('id' => $idProduto, 'grade' => $grade)) == null) {
                    throw new Exception("Produto $produto[codProduto] - $produto[grade] nao encontrado");
                }
            }
        }
    }

    public function findClienteByCodigoExterno ($repositorios, $cliente) {
        $ClienteRepo    = $repositorios['clienteRepo'];

        $permitirCnpjIguais = $ClienteRepo->getParametroCNPJ();

        if ($permitirCnpjIguais == 'S')
            $cliente['cpf_cnpj'] = $cliente['codCliente'];

        switch ($cliente['tipoPessoa']) {
            case 'J':
                $cliente['pessoa']['tipo'] = 'J';

                $PessoaJuridicaRepo    = $repositorios['pessoaJuridicaRepo'];
                $entityPessoa = $PessoaJuridicaRepo->findOneBy(array('cnpj' => str_replace(array(".", "-", "/"), "",$cliente['cpf_cnpj'])));
                if ($entityPessoa != null) {
                    $cliente['id'] = $entityPessoa->getId();
                }
                $cliente['pessoa']['juridica']['dataAbertura'] = null;
                $cliente['pessoa']['juridica']['cnpj'] = $cliente['cpf_cnpj'];
                $cliente['pessoa']['juridica']['idTipoOrganizacao'] = null;
                $cliente['pessoa']['juridica']['idRamoAtividade'] = null;
                $cliente['pessoa']['juridica']['nome'] = $cliente['nome'];
                break;
            case 'F':

                $PessoaFisicaRepo    = $repositorios['pessoaFisicaRepo'];
                $entityPessoa = $PessoaFisicaRepo->findOneBy(array('cpf' => str_replace(array(".", "-", "/"), "",$cliente['cpf_cnpj'])));
                if ($entityPessoa != null) {
                    $cliente['pessoa']['fisica']['id'] = $entityPessoa->getId();
                    $cliente['pessoa']['fisica']['acao'] = 'edit';
                }
                $cliente['pessoa']['tipo']              = 'F';
                $cliente['pessoa']['fisica']['cpf']     = $cliente['cpf_cnpj'];
                $cliente['pessoa']['fisica']['nome']    = $cliente['nome'];
                break;
        }

        $SiglaRepo      = $repositorios['siglaRepo'];
        $entitySigla    = $SiglaRepo->findOneBy(array('referencia' => $cliente['uf']));

        $cliente['cep'] = (isset($cliente['cep']) && !empty($cliente['cep']) ? $cliente['cep'] : '');
        $cliente['enderecos'][0]['acao'] = 'incluir';
        $cliente['enderecos'][0]['idTipo'] = \Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA;

        if (isset($cliente['complemento']))
            $cliente['enderecos'][0]['complemento'] = $cliente['complemento'];
        if (isset($cliente['logradouro']))
            $cliente['enderecos'][0]['descricao'] = $cliente['logradouro'];
        if (isset($cliente['referencia']))
            $cliente['enderecos'][0]['pontoReferencia'] = $cliente['referencia'];
        if (isset($cliente['bairro']))
            $cliente['enderecos'][0]['bairro'] = $cliente['bairro'];
        if (isset($cliente['cidade']))
            $cliente['enderecos'][0]['localidade'] = $cliente['cidade'];
        if (isset($cliente['numero']))
            $cliente['enderecos'][0]['numero'] = $cliente['numero'];
        if (isset($cliente['cep']))
            $cliente['enderecos'][0]['cep'] = $cliente['cep'];
        if (isset($entitySigla))
            $cliente['enderecos'][0]['idUf'] = $entitySigla->getId();

        $entityCliente  = $ClienteRepo->findOneBy(array('codClienteExterno' => $cliente['codCliente']));
        if ($entityCliente == null) {
            $entityCliente  = new \Wms\Domain\Entity\Pessoa\Papel\Cliente();
            $entityCliente->setCodClienteExterno($cliente['codCliente']);
        }

        $entityPessoa = $ClienteRepo->persistirAtor($entityCliente, $cliente, false);
        $entityCliente->setPessoa($entityPessoa);
        $entityCliente->setId($entityPessoa->getId());

        $this->_em->persist($entityCliente);

        return $entityCliente;
    }

    protected function findPedidoById($repositorios, $pedido) {
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $PedidoRepo */
        $PedidoRepo     = $repositorios['pedidoRepo'];
        $entityPedido   = $PedidoRepo->find($pedido['codPedido']);
        if ($entityPedido == null) {
            $entityPedido = $PedidoRepo->save($pedido);
        }
        return $entityPedido;
    }

    protected function  findItinerarioById($repositorios, $Itinerario) {
        $ItinerarioRepo = $repositorios['itinerarioRepo'];
        $itinerarioPadrao = 57;
        if ($Itinerario['idItinerario']== "") {
            $entityItinerario = $ItinerarioRepo->find($itinerarioPadrao);
        } else {
            $entityItinerario = $ItinerarioRepo->find($Itinerario['idItinerario']);
            if ($entityItinerario == null) {
                $entityItinerario = $ItinerarioRepo->save($Itinerario);
            }
        }
        return $entityItinerario;
    }

    protected function findExpedicaoByPlacaExpedicao($repositorios, $placaExpedicao) {
        $ExpedicaoRepo      = $repositorios['expedicaoRepo'];
        $entityExpedicao    = $ExpedicaoRepo->findOneBy(array('placaExpedicao' => $placaExpedicao, 'status' => array(Expedicao::STATUS_INTEGRADO, Expedicao::STATUS_EM_SEPARACAO, Expedicao::STATUS_EM_CONFERENCIA)));
        if ($entityExpedicao == null) {
            $entityExpedicao= $ExpedicaoRepo->save($placaExpedicao, false);
        }

        if ($entityExpedicao->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_FINALIZADO) {
            throw new \Exception('Expedicao ' . $entityExpedicao->getId() . ' já está finalizada');
        }

        return $entityExpedicao;
    }

    protected function findCargaByTipoCarga($repositorios, $carga) {
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $CargaRepo */
        $CargaRepo = $repositorios['cargaRepo'];

        $tipoCarga = $this->verificaTipoCarga($carga['codTipoCarga']);

        $entityCarga = $CargaRepo->findOneBy(array('codCargaExterno' => trim($carga['codCargaExterno']), 'tipoCarga' => $tipoCarga->getId()));
        if ($entityCarga == null) {
            $entityCarga = $CargaRepo->save($carga,true);
        }
        return $entityCarga;
    }

    /**
     * @param $tipoCarga
     * @return object
     * @throws Exception
     */
    protected function verificaTipoCarga($tipoCarga)
    {
        $siglaTipoCarga = $this->_em->getRepository('wms:Util\Sigla')->findOneBy(array('tipo' => 69, 'referencia' => $tipoCarga));

        if ($siglaTipoCarga == null) {
            throw new \Exception('Tipo de Carga não encontrado');
        }
        return $siglaTipoCarga;
    }

    /**
     *  Recebe as notas fiscais emitidas da empresa
     *
     * @param notaFiscal[] nf Array de objetos nota fiscal
     * @return boolean Se as notas fiscais foram salvas com sucesso
     */
    public function informarNotaFiscal ($nf)
    {
        try {
            $this->_em->beginTransaction();
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
            $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
            $produtoRepo = $this->_em->getRepository("wms:Produto");
            $pedidoRepo = $this->_em->getRepository("wms:Expedicao\Pedido");
            $nfRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaida");
            $pessoaJuridicaRepo    = $this->_em->getRepository('wms:Pessoa\Juridica');

            if ((count($nf) == 0) || ($nf == null)) {
                throw new \Exception("Nenhuma nota fiscal informada");
            }

            /* @var notaFiscal $notaFiscal */
            foreach ($nf as $notaFiscal) {

                $cnpjEmitente = trim(str_replace(array(".", "-", "/"), "", $notaFiscal->cnpjEmitente));
                $pessoaEn = $pessoaJuridicaRepo->findOneBy(array('cnpj' => $cnpjEmitente));

                if (is_null($pessoaEn)) {
                    throw new \Exception("Emitente não encontrado para o cnpj " . $notaFiscal->cnpjEmitente);
                }

                $nfEn = $nfRepo->findOneBy(array('numeroNf' => $notaFiscal->numeroNf, 'serieNf' => $notaFiscal->serieNf, 'codPessoa'=> $pessoaEn->getId()));

                if ($nfEn != null) {
                    return true;
                    //throw new \Exception('Nota Fiscal número '.$notaFiscal->numeroNf.', série '.$notaFiscal->serieNf.', emitente: ' . $pessoaEn->getNomeFantasia() . ', cnpj ' . $notaFiscal->cnpjEmitente . ' já existe no sistema!');
                }

                $statusEn = $this->_em->getReference('wms:Util\Sigla', (int) Expedicao\NotaFiscalSaida::NOTA_FISCAL_EMITIDA);

                $nfEntity = new Expedicao\NotaFiscalSaida();
                $nfEntity->setNumeroNf($notaFiscal->numeroNf);
                $nfEntity->setCodPessoa($pessoaEn->getId());
                $nfEntity->setPessoa($pessoaEn);
                $nfEntity->setSerieNf($notaFiscal->serieNf);
                $nfEntity->setValorTotal($notaFiscal->valorVenda);
                $nfEntity->setStatus($statusEn);
                $this->_em->persist($nfEntity);

                $andamentoNFRepo->save($nfEntity, Expedicao\NotaFiscalSaida::NOTA_FISCAL_EMITIDA, true);

                if ((count($notaFiscal->pedidos) == 0) || ($notaFiscal->pedidos == null)) {
                    throw new \Exception("Nenhuma pedido informado na nota fiscal " .$notaFiscal->numeroNf . " / " . $notaFiscal->serieNf);
                }

                /* @var pedidoFaturado $pedidoNf */
                foreach ($notaFiscal->pedidos as $pedidoNf) {
                    $nfPedidoEntity = new Expedicao\NotaFiscalSaidaPedido();
                    $nfPedidoEntity->setNotaFiscalSaida($nfEntity);
                    $nfPedidoEntity->setCodNotaFiscalSaida($nfEntity->getId());
                    $pedidoEn = $pedidoRepo->findOneBy(array('id' => $pedidoNf->codPedido));

                    if ($pedidoEn == null) {
                        throw new \Exception('Pedido '.$pedidoNf->codPedido . ' - ' . $pedidoNf->tipoPedido . ' - ' . ' não encontrado!');
                    }

                    $nfPedidoEntity->setCodPedido($pedidoEn->getId());
                    $nfPedidoEntity->setPedido($pedidoEn);
                    $this->_em->persist($nfPedidoEntity);
                }

                if ((count($notaFiscal->itens) == 0) || ($notaFiscal->itens == null)) {
                    throw new \Exception("Nenhuma produto informado na nota fiscal " .$notaFiscal->numeroNf . " / " . $notaFiscal->serieNf);
                }

                /* @var notaFiscalProduto $itemNotaFiscal */
                foreach ($notaFiscal->itens as $itemNotaFiscal) {
                    $itemNfEntity = new Expedicao\NotaFiscalSaidaProduto();

                    $idProduto = $itemNotaFiscal->codProduto;
                    $idProduto = ProdutoUtil::formatar($idProduto);
                    $produtoEn = $produtoRepo->findOneBy(array('id' => $idProduto, 'grade' => trim($itemNotaFiscal->grade)));

                    if ($produtoEn == null) {
                        throw new \Exception('PRODUTO '.$idProduto.' GRADE '.$itemNotaFiscal->grade.' não encontrado!');
                    }

                    $itemNfEntity->setCodProduto($produtoEn->getId());
                    $itemNfEntity->setGrade($produtoEn->getGrade());
                    $itemNfEntity->setProduto($produtoEn);
                    $itemNfEntity->setCodNotaFiscalSaida($nfEntity->getId());
                    $itemNfEntity->setNotaFiscalSaida($nfEntity);
                    $itemNfEntity->setValorVenda($itemNotaFiscal->valorVenda);
                    $itemNfEntity->setQuantidade($itemNotaFiscal->qtd);

                    $this->_em->persist($itemNfEntity);
                }
            }
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage() . ' - ' . $e->getTraceAsString());
        }
        return true;
    }

    /**
     *  Recebe as notas fiscais emitidas da empresa
     *
     * @param string $cnpjEmitente
     * @param integer $numeroNf
     * @param string $serieNF
     * @param integer $numeroCarga
     * @param string $tipoCarga
     * @return boolean Se as notas fiscais foram salvas com sucesso
     */
    public function definirReentrega ($cnpjEmitente, $numeroNf, $serieNF, $numeroCarga, $tipoCarga)
    {
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
        $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
        $notaFiscalRepository = $this->_em->getRepository('wms:Expedicao\NotaFiscalSaida');
        $cargaRepository = $this->_em->getRepository('wms:Expedicao\Carga');
        $reentregaRepository = $this->_em->getRepository('wms:Expedicao\Reentrega');
        $pessoaJuridicaRepository = $this->_em->getRepository('wms:Pessoa\Juridica');

        try {
            if (!isset($tipoCarga) or trim($tipoCarga) == "" or $tipoCarga == null) {
                $tipoCarga = "C";
            }
            $tipoCarga = trim($tipoCarga);
            $tipoCarga = $this->verificaTipoCarga($tipoCarga);

            $cnpjEmitente = trim(str_replace(array(".", "-", "/"), "", $cnpjEmitente));
            $pessoaEn = $pessoaJuridicaRepository->findOneBy(array('cnpj' => $cnpjEmitente));

            if (is_null($pessoaEn)) {
                throw new \Exception("Emitente não encontrado para o cnpj " . $cnpjEmitente);
            }

            $notaFiscalEn = $notaFiscalRepository->findOneBy(array('numeroNf' =>  (int) trim($numeroNf),
                'codPessoa' => $pessoaEn->getId(),
                'serieNf' => trim($serieNF)));

            $cargaEn = $cargaRepository->findOneBy(array('codCargaExterno' => trim($numeroCarga),
                'tipoCarga' => $tipoCarga->getId()));

            if (is_null($notaFiscalEn)) {
                throw new \Exception('Nota Fiscal ' . $numeroNf . " / " . $serieNF . " não encontrada");
            }

            if (is_null($cargaEn)) {
                throw new \Exception(strtolower($tipoCarga->getSigla()) . " " . $numeroCarga . " não encontrada");
            }


            $parametroRepo = $this->_em->getRepository('wms:Sistema\Parametro');
            $parametro = $parametroRepo->findOneBy(array('constante' => 'CONFERE_RECEBIMENTO_REENTREGA'));

            if ($parametro->getValor() == 'S') {
                if ($notaFiscalEn->getStatus()->getId() != Expedicao\NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA) {
                    throw new \Exception('Nota Fiscal de reentrega ' . $numeroNf . " / " . $serieNF . " ainda não foi recebida");
                }
            }

            $reentregaEn = $reentregaRepository->findOneBy(array('codNotaFiscalSaida' => $notaFiscalEn->getId(),
                'codCarga' => $cargaEn->getId()));

            if ($reentregaEn != null) {
                return true;
                //throw new \Exception('Nota Fiscal '. $numeroNf . ' / ' . $serieNF . " ja se encontra na " . strtolower($tipoCarga->getSigla()) . " " . $numeroCarga);
            }

            $reentregaEn = new Expedicao\Reentrega();
            $reentregaEn->setIndEtiquetaMapaGerado("N");
            $reentregaEn->setCarga($cargaEn);
            $reentregaEn->setCodCarga($cargaEn->getId());
            $reentregaEn->setNotaFiscalSaida($notaFiscalEn);
            $reentregaEn->setCodNotaFiscalSaida($notaFiscalEn->getId());
            $reentregaEn->setDataReentrega(new \DateTime());
            $this->_em->persist($reentregaEn);

            $andamentoNFRepo->save($notaFiscalEn, Expedicao\NotaFiscalSaida::REENTREGA_DEFINIDA, true, $cargaEn->getExpedicao(), $reentregaEn);

            $this->_em->flush();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . ' - ' . $e->getTraceAsString());
        }

        return true;
    }


    /**
     * Informa a listagem de cargas por data
     *
     * @param string $dataInicial
     * @param string $dataFinal
     * @return carga[]
     */
    public function listCargas($dataInicial, $dataFinal) {
        try{

            $expedicaoRepo = $this->_em->getRepository('wms:Expedicao');
            $cargas = $expedicaoRepo->getCargasFechadasByData($dataInicial,$dataFinal);

            $objCargas = array();
            foreach ($cargas as $carga) {
                $objCarga = new carga();
                $objCarga->codCarga = $carga['COD_CARGA_EXTERNO'];
                $objCarga->veiculo = $carga['DSC_PLACA_EXPEDICAO'];
                $objCarga->motorista = $carga['NOM_MOTORISTA'];
                $objCarga->linhaEntrega = $carga['DSC_LINHA_ENTREGA'];
                $objCarga->peso = $carga['NUM_PESO'];
                $objCarga->cubagem = $carga['NUM_CUBAGEM'];
                $objCarga->valor = $carga['VLR_CARGA'];
                $objCarga->dataFechamento = $carga['DTH_FINALIZACAO'];
                $objCarga->volumes = $carga['VOLUMES'];
                $objCarga->entregas = $carga['ENTREGAS'];
                $objCarga->qtdPedidos = $carga['QTD_PEDIDOS'];

                $objCargas[] = $objCarga;
            }
            return $objCargas;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . ' - ' . $e->getTraceAsString());
        }

}

}