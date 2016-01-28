<?php

/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 25/01/2016
 * Time: 09:28
 */

use Wms\Module\Web\Controller\Action;

class Importacao_IndexController extends Action
{
    public function importAjaxAction()
    {
        if (1==2) {
            //DIRETORIO DOS ARQUIVOS
            $dir = 'C:\desenvolvimento\wms\docs\importcsv';
            //LEITURA DE ARQUIVOS COMO ARRAY
            $files = scandir($dir);

            //LEITURA DE ARQUIVOS
            foreach ($files as $file) {
                $handle = $dir.'/\/'.$file;

                //DEFINIÇÃO DE ARQUIVO E METODO ADEQUADO PARA LEITURA DE DADOS
                switch ($file) {
                    case 'expedicao.csv':
                        $this->importExpedicao($handle);
                        break;
                    case 'fabricante.csv':
                        $this->importFabricante($handle);
                        break;
                    case 'filial.csv':
                        $this->importFilial($handle);
                        break;
                    case 'fornecedor.csv':
                        $this->importFornecedor($handle);
                        break;
                    case 'notaFiscal.csv':
                        $this->importNotaFiscal($handle);
                        break;
                    case 'produto.csv':
                        $this->importProduto($handle);
                        break;
                }
            }
        }
    }

    private function importNotaFiscal($handle)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $array = array();
            $count = 0;
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);
                $array['numeroNota'] = $registro['NUMERO_NOTA'];
                $array['serie'] = $registro['SERIE'];
                $array['dataEmissao'] = $registro['DATA_EMISSAO'];
                $array['placa'] = $registro['PLACA_VEICULO'];
                $array['codFornecedorExterno'] = $registro['COD_FORNECEDOR'];
                $array['itens'][$count]['idProduto'] = $registro['COD_PRODUTO'];
                $array['itens'][$count]['grade'] = $registro['GRADE'];
                $array['itens'][$count]['quantidade'] = $registro['QUANTIDADE'];
                $count++;
            }
            $importacao->saveNotaFiscal($em, $array['codFornecedorExterno'], $array['numeroNota'], $array['serie'], $array['dataEmissao'], $array['placa'], $array['itens'], 'N', null);
            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importExpedicao($handle)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $array = array();
            $count = 0;
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {

                $registro = array_combine($cabecalho, $data);
                $array['data'] = $registro['DATA'];
                $array['codCliente'] = $registro['COD_CLIENTE'];
                $array['nomeCliente'] = $registro['NOME_CLIENTE'];
                $array['placaExpedicao'] = $registro['PLACA_VEICULO'];
                $array['placaCarga'] = $registro['PLACA_VEICULO'];
                $array['codCargaExterno'] = $registro['COD_CARGA'];
                $array['codTipoCarga'] = $registro['TIPO_CARGA'];
                $array['centralEntrega'] = $registro['CENTRAL_ENTREGA'];
                $array['codPedido'] = $registro['COD_PEDIDO'];
                $array['tipoPedido'] = $registro['TIPO_PEDIDO'];
                $array['linhaEntrega'] = $registro['LINHA_ENTREGA'];
                $array['itinerario'] = $registro['ITINERARIO'];
                $array['itens'][$count]['codProduto'] = $registro['COD_PRODUTO'];
                $array['itens'][$count]['grade'] = $registro['GRADE'];
                $array['itens'][$count]['quantidade'] = $registro['QUANTIDADE'];
                $count++;
            }
            $array['idExpedicao'] = $importacao->saveExpedicao($em, $array['placaExpedicao']);
            $array['carga'] = $importacao->saveCarga($em, $array);
            $array['pedido'] = $importacao->savePedido($em, $array);
            foreach ($array['itens'] as $item) {
                $item['pedido'] = $array['pedido'];
                $importacao->savePedidoProduto($em, $item);
            }

            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFabricante($handle)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $idFabricante = $registro['COD_FABRICANTE'];
                $nome = $registro['FABRICANTE'];
                $importacao->saveFabricante($em, $idFabricante, $nome);
            }
            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFornecedor($handle)
    {
        $em = $this->getEntityManager();
        $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');
        $ClienteRepo    = $em->getRepository('wms:Pessoa\Papel\Cliente');

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $em->beginTransaction();
            $array = array();
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $array['codFornecedor'] = $registro['COD_FORNECEDOR'];
                $array['nome'] = $registro['NOME_FORNECEDOR'];
                $array['tipoPessoa'] = $registro['TIPO_PESSOA'];
                $array['cpf_cnpj'] = $registro['CPF_CNPJ'];
                $array['logradouro'] = $registro['RUA'];
                $array['numero'] = $registro['NUMERO'];
                $array['complemento'] = $registro['COMPLEMENTO'];
                $array['bairro'] = $registro['BAIRRO'];
                $array['cidade'] = $registro['CIDADE'];
                $array['uf'] = $registro['ESTADO'];
                $array['referencia'] = $registro['REFERENCIA'];
                $array['email'] = $registro['EMAIL'];
                $array['telefone'] = $registro['TELEFONE'];
                $array['observacao'] = $registro['OBSERVACAO'];

                $entityFornecedor = $fornecedorRepo->findOneBy(array('idExterno' => $array['codFornecedor']));
                if ($entityFornecedor == null) {
                    switch ($array['tipoPessoa']) {
                        case 'PJ':
                            $cliente['pessoa']['tipo'] = 'J';

                            $PessoaJuridicaRepo    = $em->getRepository('wms:Pessoa\Juridica');
                            $entityPessoa = $PessoaJuridicaRepo->findOneBy(array('cnpj' => str_replace(array(".", "-", "/"), "",$array['cpf_cnpj'])));
                            if ($entityPessoa) {
                                break;
                            }

                            $cliente['pessoa']['juridica']['dataAbertura'] = null;
                            $cliente['pessoa']['juridica']['cnpj'] = $array['cpf_cnpj'];
                            $cliente['pessoa']['juridica']['idTipoOrganizacao'] = null;
                            $cliente['pessoa']['juridica']['idRamoAtividade'] = null;
                            $cliente['pessoa']['juridica']['nome'] = $array['nome'];
                            break;
                        case 'PF':

                            $PessoaFisicaRepo    = $em->getRepository('wms:Pessoa\Fisica');
                            $entityPessoa       = $PessoaFisicaRepo->findOneBy(array('cpf' => str_replace(array(".", "-", "/"), "",$array['cpf_cnpj'])));
                            if ($entityPessoa) {
                                break;
                            }

                            $cliente['pessoa']['tipo']              = 'F';
                            $cliente['pessoa']['fisica']['cpf']     = $array['cpf_cnpj'];
                            $cliente['pessoa']['fisica']['nome']    = $array['nome'];
                            break;
                    }

                    $SiglaRepo      = $em->getRepository('wms:Util\Sigla');
                    $entitySigla    = $SiglaRepo->findOneBy(array('referencia' => $array['uf']));

                    $array['cep'] = (isset($array['cep']) && !empty($array['cep']) ? $array['cep'] : '');
                    $cliente['enderecos'][0]['acao'] = 'incluir';
                    $cliente['enderecos'][0]['idTipo'] = \Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA;

                    if (isset($array['complemento']))
                        $cliente['enderecos'][0]['complemento'] = $array['complemento'];
                    if (isset($array['logradouro']))
                        $cliente['enderecos'][0]['descricao'] = $array['logradouro'];
                    if (isset($array['referencia']))
                        $cliente['enderecos'][0]['pontoReferencia'] = $array['referencia'];
                    if (isset($array['bairro']))
                        $cliente['enderecos'][0]['bairro'] = $array['bairro'];
                    if (isset($array['cidade']))
                        $cliente['enderecos'][0]['localidade'] = $array['cidade'];
                    if (isset($array['numero']))
                        $cliente['enderecos'][0]['numero'] = $array['numero'];
                    if (isset($array['cep']))
                        $cliente['enderecos'][0]['cep'] = $array['cep'];
                    if (isset($entitySigla))
                        $cliente['enderecos'][0]['idUf'] = $entitySigla->getId();

                    $fornecedor = new \Wms\Domain\Entity\Pessoa\Papel\Fornecedor();
                    if ($entityPessoa == null) {
                        $entityPessoa = $ClienteRepo->persistirAtor($fornecedor, $cliente, false);
                    } else {
                        $fornecedor->setPessoa($entityPessoa);
                    }
                    $fornecedor->setId($entityPessoa->getId());
                    $fornecedor->setIdExterno($array['codFornecedor']);

                    $em->persist($fornecedor);
                }
            }

            $em->flush();
            $em->commit();
            fclose($handle);
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importProduto($handle)
    {
        $em = $this->getEntityManager();

        $importacao = new \Wms\Service\Importacao();
        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            $em->beginTransaction();
            $produtos = array();
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $produtos['codProduto'] = $registro['COD_PRODUTO'];
                $produtos['descricao'] = $registro['DESCRICAO'];
                $produtos['grade'] = $registro['GRADE'];
                $produtos['referencia'] = $registro['REFERENCIA'];
                $produtos['tipoComercializacao'] = $registro['COD_TIPO_COMERCIALIZACAO'];
                $produtos['classe'] = $registro['COD_CLASSE'];
                $produtos['fabricante'] = $registro['NOME_FABRICANTE'];
                $produtos['linhaSeparacao'] = $registro['COD_LINHA_SEPARACAO'];
                $produtos['codBarras'] = $registro['COD_BARRAS'];
                $produtos['numVolumes'] = $registro['NUM_VOLUMES'];
                $produtos['diasVidaUtil'] = $registro['DIAS_VIDA_UTIL'];
                $produtos['validade'] = $registro['POSSUI_VALIDADE'];
                $produtos['enderecoReferencia'] = $registro['COD_ENDERECO_REF_END_AUTO'];
                $produtos['embalagens'][0]['descricaoEmbalagem'] = $registro['DESCRICAO_EMBALAGEM'];
                $produtos['embalagens'][0]['qtdEmbalagem'] = $registro['QTD_EMBALAGEM'];
                $produtos['embalagens'][0]['indPadrao'] = $registro['IND_PADRAO'];
                $produtos['embalagens'][0]['codigoBarras'] = $registro['CODIGO_BARRAS'];
                $produtos['embalagens'][0]['cbInterno'] = $registro['CB_INTERNO'];
                $produtos['embalagens'][0]['imprimirCb'] = $registro['IMPRIMIR_CB'];
                $produtos['embalagens'][0]['embalado'] = $registro['EMBALADO'];
                $produtos['embalagens'][0]['capacidadePicking'] = $registro['CAPACIDADE_PICKING'];
                $produtos['embalagens'][0]['pontoReposicao'] = 0;
                $produtos['embalagens'][0]['acao'] = 'incluir';

                $produtos['volumes'][0]['descricaoVolume'] = $registro['DESCRICAO_VOLUME'];
                $produtos['volumes'][0]['codigoBarras'] = $registro['CODIGO_BARRAS'];
                $produtos['volumes'][0]['sequenciaVolume'] = $registro['CODIGO_SEQUENCIAL_VOLUME'];
                $produtos['volumes'][0]['peso'] = $registro['PESO'];
                $produtos['volumes'][0]['normaPaletizacao'] = $registro['NORMA_PALETIZACAO'];
                $produtos['volumes'][0]['cbInterno'] = $registro['CB_INTERNO'];
                $produtos['volumes'][0]['imprimirCb'] = $registro['IMPRIMIR_CB'];
                $produtos['volumes'][0]['altura'] = $registro['ALTURA'];
                $produtos['volumes'][0]['largura'] = $registro['LARGURA'];
                $produtos['volumes'][0]['profundidade'] = $registro['PROFUNDIDADE'];
                $produtos['volumes'][0]['cubagem'] = $registro['CUBAGEM'];
                $produtos['volumes'][0]['capacidadePicking'] = $registro['CAPACIDADE_PICKING'];

                $importacao->saveProduto($em, $produtos);
            }
            $em->flush();
            $em->commit();
            fclose($handle);
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFilial($handle)
    {
        $em = $this->getEntityManager();

        $importacao = new \Wms\Service\Importacao();
        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            $em->beginTransaction();
            $filial = array();
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $filial['pessoa']['juridica']['idExterno'] = $registro['COD_FILIAL_INTEGRACAO'];
                $filial['pessoa']['juridica']['codExterno'] = $registro['COD_EXTERNO'];
                $filial['pessoa']['juridica']['indLeitEtqProdTransbObg'] = $registro['LEITURA_ETIQUETA'];
                $filial['pessoa']['juridica']['indRessuprimento'] = $registro['UTILIZA_RESSUPRIMENTO'];
                $filial['pessoa']['juridica']['indRecTransbObg'] = $registro['RECEBIMENTO_TRANSBORDO'];
                $filial['pessoa']['juridica']['isAtivo'] = $registro['IND_ATIVO'];

                $filial['pessoa']['tipo'] = 'J';
                $filial['pessoa']['juridica']['dataAbertura'] = $registro['DATA_ABERTURA'];
                $filial['pessoa']['juridica']['cnpj'] = $registro['CNPJ'];
                $filial['pessoa']['juridica']['nome'] = $registro['NOME_EMPRESA'];
                $filial['pessoa']['juridica']['idTipoOrganizacao'] = null;
                $filial['pessoa']['juridica']['idRamoAtividade'] = null;

                $filial['acao'] = 'incluir';

                $importacao->saveFilial($em, $filial);
            }
            $em->flush();
            $em->commit();
            fclose($handle);
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

}