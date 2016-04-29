<?php

namespace Wms\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Wms\Domain\Entity\Fabricante;
use Wms\Domain\Entity\Filial;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;
use Wms\Domain\Entity\Pessoa\Papel\Fornecedor;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Produto\Classe;
use Wms\Module\Web\Controller\Action;
use Wms\Util\CodigoBarras;
use Zend\Stdlib\Configurator;

class Importacao
{

    public function saveClasse($em, $idClasse, $nome, $idClassePai = null, $repositorios)
    {
        /** @var \Wms\Domain\Entity\Produto\ClasseRepository $classeRepo */
        $classeRepo = $repositorios['classeRepo'];
        $entityClasse = $classeRepo->save($idClasse, $nome, $idClassePai, false);
        return $entityClasse;

    }

    private function saveCliente($em, $cliente)
    {
        $repositorios = array(
            'clienteRepo' => $em->getRepository('wms:Pessoa\Papel\Cliente'),
            'pessoaJuridicaRepo' => $em->getRepository('wms:Pessoa\Juridica'),
            'pessoaFisicaRepo' => $em->getRepository('wms:Pessoa\Fisica'),
            'siglaRepo' => $em->getRepository('wms:Util\Sigla'),
        );

        $ClienteRepo    = $repositorios['clienteRepo'];
        $entityCliente  = $ClienteRepo->findOneBy(array('codClienteExterno' => $cliente['codCliente']));

        if ($entityCliente == null) {

            switch ($cliente['tipoPessoa']) {
                case 'J':
                    $cliente['pessoa']['tipo'] = 'J';

                    $PessoaJuridicaRepo    = $repositorios['pessoaJuridicaRepo'];
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

                    $PessoaFisicaRepo    = $repositorios['pessoaFisicaRepo'];
                    $entityPessoa       = $PessoaFisicaRepo->findOneBy(array('cpf' => str_replace(array(".", "-", "/"), "",$cliente['cpf_cnpj'])));
                    if ($entityPessoa) {
                        break;
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
                $cliente['enderecos'][0]['bairro'];
            if (isset($cliente['cidade']))
                $cliente['enderecos'][0]['localidade'] = $cliente['cidade'];
            if (isset($cliente['numero']))
                $cliente['enderecos'][0]['numero'];
            if (isset($cliente['cep']))
                $cliente['enderecos'][0]['cep'] = $cliente['cep'];
            if (isset($entitySigla))
                $cliente['enderecos'][0]['idUf'] = $entitySigla->getId();

            $entityCliente  = new \Wms\Domain\Entity\Pessoa\Papel\Cliente();

            if ($entityPessoa == null) {
                $entityPessoa = $ClienteRepo->persistirAtor($entityCliente, $cliente, false);
            } else {
                $entityCliente->setPessoa($entityPessoa);
            }

            $entityCliente->setId($entityPessoa->getId());
            $entityCliente->setCodClienteExterno($cliente['codCliente']);

            $em->persist($entityCliente);
        }

        return $entityCliente;
    }

    public function saveFornecedor($em, $codFornecedorExterno)
    {
        $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');
        $entityFornecedor = $fornecedorRepo->findOneBy(array('idExterno' => $codFornecedorExterno));

        if (!$entityFornecedor)
            $entityFornecedor = new Fornecedor();

        $entityFornecedor->setIdExterno($codFornecedorExterno);
        $em->persist($entityFornecedor);
        $em->flush();
        return $entityFornecedor;

    }

    public function saveNotaFiscal($em, $idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao = null)
    {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        $notaFiscalEn = $notaFiscalRepo->findOneBy(array('numero' => $numero, 'serie' => $serie, 'fornecedor' => $idFornecedor));

        if (!$notaFiscalEn) {
            $entityNotaFiscal = $notaFiscalRepo->salvarNota($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao);
        } else {
            $entityNotaFiscal = $notaFiscalRepo->salvarItens($itens, $notaFiscalEn);
        }
        return $entityNotaFiscal;

    }

    public function saveExpedicao($em, $placaExpedicao)
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $em->getRepository('wms:Expedicao');
        $entityExpedicao = $expedicaoRepo->save($placaExpedicao);
        return $entityExpedicao;
    }

    public function saveCarga($em, $carga)
    {
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepo */
        $cargaRepo = $em->getRepository('wms:Expedicao\Carga');
        $entityCarga = $cargaRepo->findOneBy(array('codCargaExterno' => $carga['codCargaExterno']));
        if (!$entityCarga)
            $entityCarga = $cargaRepo->save($carga, true);

        return $entityCarga;
    }

    public function savePedido($em, $pedido)
    {
        $pedido['pontoTransbordo'] = null;
        $pedido['envioParaLoja'] = null;

        $pedido['itinerario'] = $em->getReference('wms:expedicao\Itinerario', $pedido['itinerario']);
        $pedido['pessoa'] = $em->getRepository('wms:Pessoa\Papel\Cliente')->findOneBy(array('codClienteExterno' => $pedido['codCliente']));

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $em->getRepository('wms:Expedicao\Pedido');
        $entityPedido = $pedidoRepo->findOneBy(array('id' => $pedido['codPedido']));
        if (!$entityPedido)
            $entityPedido = $pedidoRepo->save($pedido); $em->flush();

        return $entityPedido;
    }

    public function savePedidoProduto($em, $pedido)
    {
        /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
        $pedidoProdutoRepo = $em->getRepository('wms:Expedicao\PedidoProduto');
        $pedido['produto'] = $em->getRepository('wms:Produto')->findOneBy(array('id' => $pedido['codProduto'], 'grade' => $pedido['grade']));

        $entityPedidoProduto = $pedidoProdutoRepo->findOneBy(array('codPedido' => $pedido['pedido']->getId(), 'codProduto' => $pedido['produto']->getId(), 'grade' => $pedido['produto']->getGrade()));
        if (!$entityPedidoProduto)
            $entityPedidoProduto = $pedidoProdutoRepo->save($pedido); $em->flush();

        return $entityPedidoProduto;
    }

    public function saveFabricante($em, $idFabricante, $nome, $repositorios)
    {
        /** @var \Wms\Domain\Entity\FabricanteRepository $fabricanteRepo */
        $fabricanteRepo = $repositorios['fabricanteRepo'];
        $entityFabricante = $fabricanteRepo->save($idFabricante, $nome, false);
        return $entityFabricante;

    }

    public function saveProduto($em, $produto, $repositorios)
    {
        $produtoRepo  = $repositorios['produtoRepo'];
        $enderecoRepo = $repositorios['enderecoRepo'];
        $produtoEntity = $produtoRepo->findOneBy(array('id' => $produto['id'], 'grade' => $produto['grade']));

        $novo = false;
        if ($produtoEntity == null) {
            $produtoEntity = new Produto();
            $novo = true;
        }

        try {
            $dscEndereco = '';
            if (isset($produto['enderecoReferencia'])) {
                $dscEndereco = $produto['enderecoReferencia'];
            }
            if ($dscEndereco != "") {
                $enderecoEn = $enderecoRepo->findOneBy(array('descricao'=>$dscEndereco));
                if ($enderecoEn == null) {
                    throw new \Exception("Endereço de referencia para endereçamento automático inválido");
                } else {
                    $produto['enderecoReferencia'] = $enderecoEn;
                }
            }

            $produto['linhaSeparacao'] = $em->getReference('wms:Armazenagem\LinhaSeparacao', $produto['linhaSeparacao']);
            $varTpComercializacao = $produto['tipoComercializacao'];
            $produto['tipoComercializacao'] = $em->getReference('wms:Produto\TipoComercializacao', $produto['tipoComercializacao']);
            $produto['classe'] = $em->getReference('wms:Produto\Classe', $produto['classe']);
            $produto['fabricante'] = $em->getReference('wms:Fabricante', $produto['fabricante']);

            Configurator::configure($produtoEntity, $produto);

            $em->persist($produtoEntity);

            if ($novo == true) {
                $em->flush();
                $em->clear();
            }

        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function saveFilial($em, $values)
    {
        /** @var \Wms\Domain\Entity\FilialRepository $filialRepo */
        $filialRepo = $em->getRepository('wms:Filial');
        $filianEn = $filialRepo->findOneBy(array('codExterno' => $values['pessoa']['juridica']['codExterno']));

        if (!$filianEn) {
            $filianEn = new Filial();
        }

        $filialRepo->save($filianEn, $values);
    }

    public function saveEmbalagens($em, $registro, $repositorios)
    {
        /** @var EntityManager $em */

        $produtoRepo = $repositorios['produtoRepo'];
        $embalagemRepo = $repositorios['embalagemRepo'];

        $codigoBarras = "";
        if ($registro['codigoBarras'] != "") {
            $codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem($registro['codigoBarras']);
            $embalagemEntity = $embalagemRepo->findOneBy(array(
                'codProduto' => $registro['codProduto'],
                'grade' => $registro['grade'],
                'codigoBarras' => $codigoBarras
            ));
        } else {
            $registro['CBInterno'] = 'S';
            $embalagemEntity = $embalagemRepo->findOneBy(array(
                'codProduto' => $registro['codProduto'],
                'grade' => $registro['grade'],
                'quantidade' => $registro['quantidade']
            ));
        }


        if ($embalagemEntity == null) {
            /** @var \Wms\Domain\Entity\Produto $produto */
            $produto = $produtoRepo->findOneBy(array(
                'id' => $registro['codProduto'],
                'grade' => $registro['grade'],
            ));

            /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEntity */
            $embalagemEntity = new Produto\Embalagem();
            $embalagemEntity = \Wms\Domain\Configurator::configure($embalagemEntity,$registro);
            $embalagemEntity->setProduto($produto);
            $embalagemEntity->setCodigoBarras($codigoBarras);
            $em->persist($embalagemEntity);

            if ($registro['codigoBarras'] == "") {
                $codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem($embalagemEntity->getId());
                $embalagemEntity->setCodigoBarras($codigoBarras);
                $em->persist($embalagemEntity);
            }
        }
    }

    private function persistirVolumes($em, $produtoEntity, $volume) {

        $volumeEntity = new Produto\Volume();

        $volumeEntity->setProduto($produtoEntity);
        $volumeEntity->setGrade($produtoEntity->getGrade());
        $volumeEntity->setLargura($volume['largura']);
        $volumeEntity->setProfundidade($volume['profundidade']);
        $volumeEntity->setCubagem($volume['cubagem']);
        $volumeEntity->setPeso($volume['peso']);
        $volumeEntity->setAltura($volume['altura']);
        $volumeEntity->setCodigoSequencial($volume['sequenciaVolume']);
        $volumeEntity->setDescricao($volume['descricaoVolume']);
        $volumeEntity->setCBInterno($volume['cbInterno']);
        $volumeEntity->setImprimirCB($volume['imprimirCb']);
        $volumeEntity->setCodigoBarras($volume['codigoBarras']);
        $volumeEntity->setCapacidadePicking($volume['capacidadePicking']);
        $volumeEntity->setPontoReposicao(0);
        $volumeEntity->setEndereco(null);

        if (!empty($volume['normaPaletizacao'])) {
            $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $volume['normaPaletizacao']);
            $volumeEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($volumeEntity);

        // gera o codigo de barras com base no id do volume. Ex: 12340102 / 12340202
        if ($volume['cbInterno'] == 'S') {
            $codigoBarras = $volumeEntity->getId();
            $codigoBarras .= Produto::preencheZerosEsquerda($volume['sequenciaVolume'], 2);
            $codigoBarras .= Produto::preencheZerosEsquerda($produtoEntity->getNumVolumes(), 2);
            $codigoBarras = CodigoBarras::formatarCodigoEAN128Volume($codigoBarras);
            $volumeEntity->setCodigoBarras($codigoBarras);
        }
    }

}