<?php

use Wms\Domain\Entity\Expedicao,
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
    /** @var pedido[] */
    public $pedidos = array();
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
        try {
            $array = json_decode($cargas, true);
            $arrayCargas = $array['cargas'];
            return $this->enviar($arrayCargas);
        } catch (\Exception $e) {
            throw $e;
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
    public function enviar($cargas)
    {
        $this->trimArray($cargas);
        ini_set('max_execution_time', 300);
        try {

            $this->_em->beginTransaction();
            foreach($cargas as $carga) {
                $this->checkProductsExists($carga['pedidos']);
                $this->checkPedidosExists($carga['pedidos']);
                $this->saveCarga($carga);
            }
            $this->_em->commit();
            return true;
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
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
        $idCargaExterno = trim ($idCargaExterno);
        if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
        $tipoCarga = trim($tipoCarga);

        $siglaTipoCarga = $this->verificaTipoCarga($tipoCarga);

        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepository */
        $cargaRepository = $this->_em->getRepository('wms:Expedicao\Carga');

        return $cargaRepository->cancelar($idCargaExterno,$siglaTipoCarga);
    }

    /**
     * @param integer $idCargaExterno
     * @param string $tipoCarga
     * @param string $tipoPedido
     * @param integer $idPedido
     * @return boolean
     */
    public function cancelarPedido ($idCargaExterno, $tipoCarga, $tipoPedido,$idPedido)
    {
        $idCargaExterno = trim ($idCargaExterno);
        if ((!isset($tipoCarga)) OR ($tipoCarga == "")) {$tipoCarga = "C";}
        $tipoCarga = trim($tipoCarga);
        $tipoPedido = trim($tipoPedido);
        $idPedido = trim($idPedido);

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepository */
        $pedidoRepository = $this->_em->getRepository('wms:Expedicao\Pedido');

        /** @var \Wms\Domain\Entity\Expedicao\Pedido $EntPedido */
        $EntPedido = $pedidoRepository->find($idPedido);
        if ($EntPedido->getConferido() == 1) {
            throw new \Exception("Pedido $idPedido já conferido");
        }

        $pedidoRepository->cancelar($idPedido);

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
                $itinerario->idItinerario = $pedidoEn->getItinerario()->getId();
                $itinerario->nomeItinerario = $pedidoEn->getItinerario()->getDescricao();
            } else {
                $itinerario->idItinerario = "";
                $itinerario->nomeItinerario = "";
            }

            throw new \Exception("Chegou aqui");

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
            $produtos = $pedidoRepo->getQtdPedidaAtendidaByPedido($pedidoEn->getId());
            foreach ($produtos as $item) {
                $produto = new produto();
                $produto->codProduto = $item['COD_PRODUTO'];
                $produto->grade = $item['DSC_GRADE'];
                $produto->quantidade = $item['QTD_PEDIDO'];
                $produto->qtdeAtendido = $item['QTD_ATENDIDO'];
                $pedido->produtos[] = $produto;
            }
            $carga->pedidos[] = $pedido;
        }

        return $carga;
    }

    protected function saveCarga($carga)
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
        $entityExpedicao = $this->findExpedicaoByPlacaExpedicao($carga['placaExpedicao']);

        if (isset($expedicaoEntity) && is_object($expedicaoEntity)) {
            $hoje = new \DateTime("now");
            if ($expedicaoEntity->getDataInicio()->format('Y-m-d') != $hoje->format('Y-m-d')) {
               throw new \Exception('Existem expedições antigas para a placa ' . $carga['placaExpedicao'] . ' abertas no sistema');
            }
        }

        $arrayCarga['idExpedicao'] = $entityExpedicao;
        $entityCarga = $this->findCargaByTipoCarga($arrayCarga);

        foreach ($carga['pedidos'] as $pedido) {
            $this->savePedido($pedido, $entityCarga);
        }
    }

    protected function savePedido (array $pedido, $entityCarga) {
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
        if (is_array($cliente[0])) {
            $cliente = $cliente[0];
        }

        $entityCliente          = $this->findClienteByCodigoExterno($cliente);
        $entityItinerario       = $this->findItinerarioById($pedido['itinerario']);

        $arrayPedido = array (
            'codPedido' => $pedido['codPedido'],
            'tipoPedido' => $pedido['tipoPedido'],
            'linhaEntrega' => $pedido['linhaEntrega'],
            'centralEntrega' => $pedido['centralEntrega'],
            'carga' => $entityCarga,
            'itinerario' => $entityItinerario,
            'pessoa' => $entityCliente,
            'pontoTransbordo' => $pedido['pontoTransbordo'],
            'envioParaLoja' => $pedido['envioParaLoja']
        );

        $entityPedido  = $this->findPedidoById($arrayPedido);
        $this->savePedidoProduto($pedido['produtos'], $entityPedido);
    }

    protected function savePedidoProduto(array $produtos, Expedicao\Pedido $enPedido) {
        $ProdutoRepo        = $this->_em->getRepository('wms:Produto');
        $PedidoProdutoRepo  = $this->_em->getRepository('wms:Expedicao\PedidoProduto');

        foreach ($produtos as $produto) {
            $enProduto = $ProdutoRepo->find(array('id' => $produto['codProduto'], 'grade' => $produto['grade']));
            if (isset($produto['quantidade'])) {
                $produto['qtde'] = $produto['quantidade'];
            }

            $prod = array(
                'codPedido' => $enPedido->getId(),
                'pedido' => $enPedido,
                'produto' => $enProduto,
                'grade' => $produto['grade'],
                'quantidade' => $produto['qtde']
            );
            $PedidoProdutoRepo->save($prod);
        }
    }

    /**
     * @param array $pedidos
     * @throws Exception
     */
    protected function checkPedidosExists(array $pedidos) {

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $PedidoRepo */
        $PedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        foreach ($pedidos as $pedido) {
            $PedidoEntity = $PedidoRepo->find($pedido['codPedido']);
            if ($PedidoEntity != null) {
                if ( count($EtiquetaRepo->getEtiquetasByPedido($pedido['codPedido'], EtiquetaSeparacao::STATUS_PENDENTE_CORTE)) > 0) {
                    throw new Exception("Pedido $pedido[codPedido] tem etiquetas pendentes de corte");
                } else {
                    $PedidoRepo->remove($PedidoEntity);
                }
            }
        }

    }

    /**
     * @param array $pedidos
     * @throws Exception
     */
    protected function checkProductsExists(array $pedidos) {
        $ProdutoRepo = $this->_em->getRepository('wms:Produto');

        foreach($pedidos as $pedido) {

            foreach($pedido['produtos'] as $produto) {
                if ($ProdutoRepo->find(array('id' => $produto['codProduto'], 'grade' => $produto['grade'])) == null) {
                    throw new Exception("Produto $produto[codProduto] - $produto[grade] nao encontrado");
                }
            }
        }

    }

    protected function findClienteByCodigoExterno ($cliente) {
        $ClienteRepo    = $this->_em->getRepository('wms:Pessoa\Papel\Cliente');
        $entityCliente  = $ClienteRepo->findOneBy(array('codClienteExterno' => $cliente['codCliente']));

        if ($entityCliente == null) {

            switch ($cliente['tipoPessoa']) {
                case 'J':
                    $cliente['pessoa']['tipo'] = 'J';

                    $PessoaJuridicaRepo    = $this->_em->getRepository('wms:Pessoa\Juridica');
                    $entityPessoa = $PessoaJuridicaRepo->findOneBy(array('cnpj' => str_replace(array(".", "-", "/"), "",$cliente['cpf_cnpj'])));
                    if ($entityPessoa) {
                        break;
                    }

                    $cliente['pessoa']['juridica']['dataAbertura'] = null;
                    $cliente['pessoa']['juridica']['cnpj'] = $cliente['cpf_cnpj'];
                    $cliente['pessoa']['juridica']['idTipoOrganizacao'] = null;
                    $cliente['pessoa']['juridica']['idRamoAtividade'] = null;
                    $cliente['pessoa']['juridica']['nome'] = $cliente['nome'];
                    break;
                case 'F':

                    $PessoaFisicaRepo    = $this->_em->getRepository('wms:Pessoa\Fisica');
                    $entityPessoa       = $PessoaFisicaRepo->findOneBy(array('cpf' => str_replace(array(".", "-", "/"), "",$cliente['cpf_cnpj'])));
                    if ($entityPessoa) {
                        break;
                    }

                    $cliente['pessoa']['tipo']              = 'F';
                    $cliente['pessoa']['fisica']['cpf']     = $cliente['cpf_cnpj'];
                    $cliente['pessoa']['fisica']['nome']    = $cliente['nome'];
                    break;
            }

            $SiglaRepo      = $this->_em->getRepository('wms:Util\Sigla');
            $entitySigla    = $SiglaRepo->findOneBy(array('referencia' => $cliente['uf']));

            $cliente['enderecos'][0] = array (
                'acao' => 'incluir',
                'idTipo' => \Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA,
                'idUf' => $entitySigla->getId(),
                'complemento' => $cliente['complemento'],
                'descricao' => $cliente['logradouro'],
                'pontoReferencia' => $cliente['referencia'],
                'bairro' => $cliente['bairro'],
                'localidade' => $cliente['cidade'],
                'numero' => $cliente['numero']
            );

            $entityCliente  = new \Wms\Domain\Entity\Pessoa\Papel\Cliente();

            if ($entityPessoa == null) {
                $entityPessoa   = $ClienteRepo->persistirAtor($entityCliente, $cliente);
            } else {
                $entityCliente->setPessoa($entityPessoa);
            }

            $entityCliente->setId($entityPessoa->getId());
            $entityCliente->setCodClienteExterno($cliente['codCliente']);

            $this->_em->persist($entityCliente);
            $this->_em->flush();



        }

        return $entityCliente;
    }

    protected function findPedidoById($pedido) {
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $PedidoRepo */
        $PedidoRepo     = $this->_em->getRepository('wms:Expedicao\Pedido');
        $entityPedido   = $PedidoRepo->find($pedido['codPedido']);
        if ($entityPedido == null) {
            $entityPedido = $PedidoRepo->save($pedido);
        }
        return $entityPedido;
    }

    protected function  findItinerarioById($Itinerario) {
        $ItinerarioRepo = $this->_em->getRepository('wms:Expedicao\Itinerario');
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

    protected function findExpedicaoByPlacaExpedicao($placaExpedicao) {
        $ExpedicaoRepo      = $this->_em->getRepository('wms:Expedicao');
        $entityExpedicao    = $ExpedicaoRepo->findOneBy(array('placaExpedicao' => $placaExpedicao, 'status' => array(Expedicao::STATUS_INTEGRADO, Expedicao::STATUS_EM_SEPARACAO, Expedicao::STATUS_EM_CONFERENCIA)));
        if ($entityExpedicao == null) {
            $entityExpedicao= $ExpedicaoRepo->save($placaExpedicao);
        }

        if ($entityExpedicao->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_FINALIZADO) {
            throw new \Exception('Expedicao ' . $entityExpedicao->getId() . ' já está finalizada');
        }
				
        return $entityExpedicao;
    }

    protected function findCargaByTipoCarga($carga) {
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $CargaRepo */
        $CargaRepo = $this->_em->getRepository('wms:Expedicao\Carga');

        $tipoCarga = $this->verificaTipoCarga($carga['codTipoCarga']);

        $entityCarga = $CargaRepo->findOneBy(array('codCargaExterno' => $carga['codCargaExterno'], 'tipoCarga' => $tipoCarga->getId()));
        if ($entityCarga == null) {
            $entityCarga = $CargaRepo->save($carga);
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

}
