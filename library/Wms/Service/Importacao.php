<?php

namespace Wms\Service;

use Doctrine\ORM\Mapping\Entity;
use Wms\Domain\Entity\Fabricante;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;
use Wms\Domain\Entity\Pessoa\Papel\Fornecedor;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Produto\Classe;
use Wms\Module\Web\Controller\Action;

class Importacao
{

    public function importarExpedicao($em)
    {
        $exemploTxt = array(
            //      0           1               2                   3           4                                       5               6           7                               8               9           10              11                  12                              13      14      15      16      17  18              19      20          21              22      23              24              25          26              27              28
            0 => 'DATA;         ID FABRICANTE;  NOME FABRICANTE;    ID CLASSE;  NOME CLASSE;                            ID CLASSE PAI;  COD CLIENTE;NOME CLIENTE;                   TIPO CLIENTE;   CPF_CNPJ;   COMPLEMENTO;	LOGRADOURO;  	    REFERENCIA;	                    BAIRRO;	CIDADE;	NUMERO;	CEP;    UF; PLACA VEICULO;  CARGA;  TIPO CARGA; CENTRAL ENTREGA;PEDIDO; TIPO PEDIDO;    LINHA ENTREGA;  ITINERARIO; COD PRODUTO;    GRADE;          DSC PRODUTO;                            QUANTIDADE;',
            1 => '12/01/2016;   350;            SAMSUNG;            20102;      AR CONDICIONADO DE 10001;               25000;          1135616;    MARLENE BILTEUCORT GURDULINO;   F;              46082336534;CASA;	        TRAVESSA PAQUETA;	PROX AO ANTIGO BAR DO CALANGO;	CENTRO;	MUCURI;	673;	null;   BA; GYG-1521;       2591;   C;          104;            35974;  ENTREGA;        CANAVIEIRAS;    1011;       104106;         UNICA;          AR SPLIT 12000 BTUAQ12UWBVNXAZ QF INT;  8;',
            2 => '12/01/2016;	101;        	ORTOCRIN;   	    40002;  	COLCHO DE SOLTEIRO;                 	40000;      	1135616;	MARLENE BILTEUCORT GURDULINO;	F;          	46082336534;CASA;	        TRAVESSA PAQUETA;	PROX AO ANTIGO BAR DO CALANGO;	CENTRO;	MUCURI;	673;	null;   BA; GYG-1521;   	2591;	C;      	104;        	35974;	ENTREGA;     	CANAVIEIRAS;	1011;   	101069; 	    UNICA;          COLCHAO ORTHOCL OURO AZ 88X188;     	15;',
            3 => '12/01/2016;	577;        	VICINI;         	120300; 	DVD PLAYER;		                        null;           1135616;	MARLENE BILTEUCORT GURDULINO;	F;          	46082336534;CASA;   	    TRAVESSA PAQUETA;	PROX AO ANTIGO BAR DO CALANGO;	CENTRO;	MUCURI;	673;	null;   BA; GYG-1521;   	2591;	C;      	104;        	35974;	ENTREGA;    	CANAVIEIRAS;	1011;   	577062; 	    UNICA;          DVD GAME C/KARAOKE VC-931 PC;	        2;',
        );

        $arquivo = array(
            'descricaoLeitura' => 'CLIENTE',
            'nomeArquivo' => "MOC - 002.txt",
            'cabecalho' => true,
            'caracterQuebra' => ";"
        );

        $cabecalhoArquivo = array(
            0 => array(
                'nomeCampo' => 'codFabricante',
                'posicaoTxt' => 1,
                'pk' => true,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            1 => array(
                'nomeCampo' => 'nomeFabricante',
                'posicaoTxt' => 2,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            2 => array(
                'nomeCampo' => 'codClasse',
                'posicaoTxt' => 3,
                'pk' => true,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            3 => array(
                'nomeCampo' => 'nomeClasse',
                'posicaoTxt' => 4,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            4 => array(
                'nomeCampo' => 'codClassePai',
                'posicaoTxt' => 5,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            5 => array(
                'nomeCampo' => 'codCliente',
                'posicaoTxt' => 6,
                'pk' => true,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            6 => array(
                'nomeCampo' => 'nomeCliente',
                'posicaoTxt' => 7,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            7 => array(
                'nomeCampo' => 'tipoCliente',
                'posicaoTxt' => 8,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            8 => array(
                'nomeCampo' => 'cpf_cnpj',
                'posicaoTxt' => 9,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            9 => array(
                'nomeCampo' => 'complemento',
                'posicaoTxt' => 10,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            10 => array(
                'nomeCampo' => 'logradouro',
                'posicaoTxt' => 11,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            11 => array(
                'nomeCampo' => 'referencia',
                'posicaoTxt' => 12,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            12 => array(
                'nomeCampo' => 'bairro',
                'posicaoTxt' => 13,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            13 => array(
                'nomeCampo' => 'cidade',
                'posicaoTxt' => 14,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            14 => array(
                'nomeCampo' => 'numero',
                'posicaoTxt' => 15,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            15 => array(
                'nomeCampo' => 'cep',
                'posicaoTxt' => 16,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            16 => array(
                'nomeCampo' => 'uf',
                'posicaoTxt' => 17,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            17 => array(
                'nomeCampo' => 'placaExpedicao',
                'posicaoTxt' => 18,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            18 => array(
                'nomeCampo' => 'codCargaExterno',
                'posicaoTxt' => 19,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            19 => array(
                'nomeCampo' => 'codTipoCarga',
                'posicaoTxt' => 20,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            20 => array(
                'nomeCampo' => 'centralEntrega',
                'posicaoTxt' => 21,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            21 => array(
                'nomeCampo' => 'codPedido',
                'posicaoTxt' => 22,
                'pk' => true,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            22 => array(
                'nomeCampo' => 'tipoPedido',
                'posicaoTxt' => 23,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            23 => array(
                'nomeCampo' => 'linhaEntrega',
                'posicaoTxt' => 24,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            24 => array(
                'nomeCampo' => 'itinerario',
                'posicaoTxt' => 25,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            25 => array(
                'nomeCampo' => 'codProduto',
                'posicaoTxt' => 26,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            26 => array(
                'nomeCampo' => 'grade',
                'posicaoTxt' => 27,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            27 => array(
                'nomeCampo' => 'dscProduto',
                'posicaoTxt' => 28,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            28 => array(
                'nomeCampo' => 'quantidade',
                'posicaoTxt' => 29,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            )
        );

        $repositorios = array(
            'clienteRepo' => $em->getRepository('wms:Pessoa\Papel\Cliente'),
            'pessoaJuridicaRepo' => $em->getRepository('wms:Pessoa\Juridica'),
            'pessoaFisicaRepo' => $em->getRepository('wms:Pessoa\Fisica'),
            'siglaRepo' => $em->getRepository('wms:Util\Sigla'),
        );

        foreach ($exemploTxt as $key => $linha) {
            if ($arquivo['cabecalho'] == true) {
                if ($key == 0) {
                    continue;
                }
            }

            if ($arquivo['caracterQuebra'] == "") {
                $conteudoArquivo = array(0=>$linha);
            }   else {
                $conteudoArquivo = explode($arquivo['caracterQuebra'],$linha);
            }
            $cliente = array();
            foreach ($cabecalhoArquivo as $campo) {
                $valorCampo = $conteudoArquivo[$campo['posicaoTxt']];
                if ($campo['tamanhoInicio'] != "") {
                    $valorCampo = substr($valorCampo,$campo['tamanhoInicio'],$campo['tamanhoFim']);
                }
                if ($campo['parametros'] != '') {
                    $valorCampo = str_replace('VALUE',$valorCampo,$campo['parametros']);
                }

                $cliente[$campo['nomeCampo']] = trim($valorCampo);
            }

            if (isset($cliente['codFabricante']) && !is_null($cliente['codFabricante'])) {
                $this->saveFabricante($em, $cliente['codFabricante'], $cliente['nomeFabricante']);
            }

            if (isset($cliente['codClasse']) && !empty($cliente['codClasse'])) {
                $this->saveClasse($em, $cliente['codClasse'], $cliente['nomeClasse'], $cliente['codClassePai']);
            }

            if (isset($cliente['codCliente']) && !empty($cliente['codCliente'])) {
                $cliente['cpf_cnpj'] = str_replace(array(".", "-", "/"), "",$cliente['cpf_cnpj']);
                $qtdCaracterCpfCnpj = strlen($cliente['cpf_cnpj']);
                switch ($qtdCaracterCpfCnpj) {
                    case 11:
                        $cliente['tipoPessoa'] = 'F';
                        break;
                    case 14:
                        $cliente['tipoPessoa'] = 'J';
                        break;
                }
                $entityCliente = $this->saveCliente($em, $cliente);
            }

            if (isset($cliente['placaExpedicao']) && !empty($cliente['placaExpedicao'])) {

                if ($arquivo['cabecalho'] == true) {
                    if ($key == 1) {
                        $cliente['idExpedicao'] = $this->saveExpedicao($em, $cliente['placaExpedicao']);
                    }
                } else {
                    if ($key == 0) {
                        $cliente['idExpedicao'] = $this->saveExpedicao($em, $cliente['placaExpedicao']);
                    }
                }

                if (isset($cliente['codCargaExterno']) && !empty($cliente['codCargaExterno'])) {
                    $cliente['carga'] = $this->saveCarga($em, $cliente);

                    if (isset($cliente['codPedido']) && !empty($cliente['codPedido'])) {

                        $cliente['itinerario'] = $em->getReference('wms:Expedicao\Itinerario', $cliente['itinerario']);
                        $cliente['pessoa'] = $entityCliente;
                        $cliente['pedido'] = $this->savePedido($em, $cliente);
                        $grade = ($cliente['grade'] != null ? $cliente['grade'] : 'UNICA');
                        $produtoEn = $em->getRepository('wms:Produto')->findOneBy(array('id' => $cliente['codProduto'], 'grade' => $grade));

                        $cliente['produto'] = $produtoEn;
                        $this->savePedidoProduto($em, $cliente);
                    }
                }
            }
        }
        $em->flush();

        return true;
    }

    public function importarRecebimento($em)
    {
        $exemploTxt = array(
            //      0           1       2               3               4               5                   6               7                                       8       9           10              11                  12          13                                      14
            0 => 'NUMERO NOTA;	SERIE;	DATA EMISSAO;	PLACA VEICULO;	COD FORNECEDOR;	NOME FORNECEDOR;	COD PRODUTO;	DSC PRODUTO;	                        GRADE;	QUANTIDADE;	ID FABRICANTE;	NOME FABRICANTE;	ID CLASSE;	NOME CLASSE;	                        ID CLASSE PAI;',
            1 => '123545;   	3;  	10/01/2016; 	HGH-2501;   	669;	        XXXXXXXXXXXX;   	350125;	        AR SPLIT 12000 BTUAQ12UWBVNXAZ QF INT;	UNICA;	4;	        578;        	FABRICA DE QQ COISA;20102;	    AR CONDICIONADO DE 10001 AT 15000 B;	25000;',
            2 => '123545;   	3;  	10/01/2016; 	HGH-2501;   	669;        	XXXXXXXXXXXX;	    101069;     	COLCHAO ORTHOCL OURO AZ 88X188;      	UNICA;	6;	        101;        	ORTOCRIN;       	40002;  	COLCHO DE SOLTEIRO;                 	40000;',
            3 => '123545;   	3;  	10/01/2016; 	HGH-2501;   	669;        	XXXXXXXXXXXX;   	577062;     	DVD GAME C/KARAOKE VC-931 PC;       	UNICA;	10;     	577;        	VICINI;         	120300; 	DVD PLAYER;                             null;',
        );

        $arquivo = array(
            'descricaoLeitura' => 'CLIENTE',
            'nomeArquivo' => "MOC - 002.txt",
            'cabecalho' => true,
            'caracterQuebra' => ";"
        );

        $cabecalhoArquivo = array(
            0 => array(
                'nomeCampo' => 'numeroNota',
                'posicaoTxt' => 0,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            1 => array(
                'nomeCampo' => 'serie',
                'posicaoTxt' => 1,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            2 => array(
                'nomeCampo' => 'dataEmissao',
                'posicaoTxt' => 2,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            3 => array(
                'nomeCampo' => 'placa',
                'posicaoTxt' => 3,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            4 => array(
                'nomeCampo' => 'codFornecedor',
                'posicaoTxt' => 4,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            5 => array(
                'nomeCampo' => 'nomeFornecedor',
                'posicaoTxt' => 5,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            6 => array(
                'nomeCampo' => 'codProduto',
                'posicaoTxt' => 6,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            7 => array(
                'nomeCampo' => 'dscProduto',
                'posicaoTxt' => 7,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            8 => array(
                'nomeCampo' => 'grade',
                'posicaoTxt' => 8,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            9 => array(
                'nomeCampo' => 'quantidade',
                'posicaoTxt' => 9,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            10 => array(
                'nomeCampo' => 'codFabricante',
                'posicaoTxt' => 10,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            11 => array(
                'nomeCampo' => 'nomeFabricante',
                'posicaoTxt' => 11,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            12 => array(
                'nomeCampo' => 'codClasse',
                'posicaoTxt' => 12,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            13 => array(
                'nomeCampo' => 'nomeClasse',
                'posicaoTxt' => 13,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            14 => array(
                'nomeCampo' => 'codClassePai',
                'posicaoTxt' => 14,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            )
        );

        foreach ($exemploTxt as $i => $linha) {
            if ($arquivo['cabecalho'] == true) {
                if ($i == 0) {
                    continue;
                }
            }

            if ($arquivo['caracterQuebra'] == "") {
                $conteudoArquivo = array(0=>$linha);
            }   else {
                $conteudoArquivo = explode($arquivo['caracterQuebra'],$linha);
            }
            $array = array();
            foreach ($cabecalhoArquivo as $j => $campo) {
                $valorCampo = $conteudoArquivo[$campo['posicaoTxt']];
                if ($campo['tamanhoInicio'] != "") {
                    $valorCampo = substr($valorCampo,$campo['tamanhoInicio'],$campo['tamanhoFim']);
                }
                if ($campo['parametros'] != '') {
                    $valorCampo = str_replace('VALUE',$valorCampo,$campo['parametros']);
                }

                $array[$campo['nomeCampo']] = trim($valorCampo);
            }
            $array['item'][0]['idProduto'] = $array['codProduto'];
            $array['item'][0]['grade'] = $array['grade'];
            $array['item'][0]['quantidade'] = $array['quantidade'];

            if (isset($array['codFabricante']) && !empty($array['codFabricante'])) {
                $this->saveFabricante($em, $array['codFabricante'], $array['nomeFabricante']);
            }

            if (isset($array['codFornecedor']) && !empty($array['codFornecedor'])) {
                $idFornecedor = $this->saveFornecedor($em, $array['codFornecedor']);
            }

            if (isset($array['numeroNota']) && !empty($array['numeroNota'])) {
                $this->saveNotaFiscal($em, $idFornecedor->getId(), $array['numeroNota'], $array['serie'], $array['dataEmissao'], $array['placa'], $array['item'], 'N', null);
            }
        }

    }

    private function saveClasse($em, $idClasse, $nome, $idClassePai = null)
    {
        /** @var \Wms\Domain\Entity\Produto\ClasseRepository $classeRepo */
        $classeRepo = $em->getRepository('wms:Produto\Classe');
        $entityClasse = $classeRepo->save($idClasse, $nome, $idClassePai);
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



    private function saveProduto($em, $produtos)
    {
        $produtoEn = new Produto();
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $em->getRepository('wms:Produto');
        $entityProduto = $produtoRepo->save($produtoEn, $produtos);
        return $entityProduto;
    }

    private function saveEndereco($em, $dscEndereco)
    {
        $dscEndereco = str_replace('.','',$dscEndereco);
        if (strlen($dscEndereco) == 8){
            $tempEndereco = "0" . $dscEndereco;
        } else {
            $tempEndereco = $dscEndereco;
        }
        $rua = intval(substr($tempEndereco,0,2));
        $predio = intval(substr($tempEndereco,2,3));
        $nivel =  intval(substr($tempEndereco,5,2));
        $apto = intval(substr($tempEndereco,7,2));

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
        $entityEndereco = $enderecoRepo->getEndereco($rua, $predio, $nivel, $apto);

        if (!$entityEndereco)
            $entityEndereco = $enderecoRepo->save(null, $values); //verificar funcionamento do metodo save

        return $entityEndereco;
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

    public function saveFabricante($em, $idFabricante, $nome)
    {
        /** @var \Wms\Domain\Entity\FabricanteRepository $fabricanteRepo */
        $fabricanteRepo = $em->getRepository('wms:Fabricante');
        $entityFabricante = $fabricanteRepo->save($idFabricante, $nome);
        return $entityFabricante;

    }

}