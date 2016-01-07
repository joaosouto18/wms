<?php

namespace Wms\Service;

use Wms\Module\Web\Controller\Action;

class Importacao
{

    public function importarCliente($em)
    {
        $exemploTxt = array(
            0 => 'Senha;Cód. Material;Descrição;Qt. Item;Qt. Peso Bruto (kg);Cód. Cliente;Empresa Destinatária/Remetente;Endereço Coleta/Entrega;Cidade;UF',
            1 => '867776;210124;FARINHA C/FERMENTO VILMA PLAST. 10X1_DOC;2;20,3;134412;ALVARO TRAJANO PEREIRA ALVES COM ME;RUA NOSSA SENHORA APARECIDA 231;BOTUMIRIM ;MG',
            2 => '867776;210124;FARINHA C/FERMENTO VILMA PLAST. 10X1_DOC;1;10,15;143487;LEONARDO PEREIRA RODRIGUES ME;RUA JOAQUINA RODRIGUES FERREIRA SN;GRAO MOGOL ;MG',
            3 => '867776;210124;FARINHA C/FERMENTO VILMA PLAST. 10X1_DOC;1;10,15;144715;SERGIO ROCHA BALDAIA ME;RUA BOM JARDIM 379;CRISTALIA ;MG'
        );

        $arquivo = array(
            'descricaoLeitura' => 'CLIENTE',
            'nomeArquivo' => "MOC - 002.txt",
            'cabecalho' => true,
            'caracterQuebra' => ";"
        );

        $cabecalhoArquivo = array(
            0 => array(
                'nomeCampo' => 'codCliente',
                'posicaoTxt' => 5,
                'pk' => true,
                'nomeBanco' => 'COD_CLIENTE_EXTERNO',
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            1 => array(
                'nomeCampo' => 'nome',
                'posicaoTxt' => 6,
                'pk' => false,
                'nomeBanco' => 'COD_CLIENTE_EXTERNO',
                'parametros' => "",
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            2 => array(
                'nomeCampo' => 'cpf_cnpj',
                'posicaoTxt' => 5,
                'pk' => false,
                'parametros' => "",
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            3 => array(
                'nomeCampo' => 'logradouro',
                'posicaoTxt' => 7,
                'pk' => false,
                'parametros' => "",
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            4 => array(
                'nomeCampo' => 'cidade',
                'posicaoTxt' => 8,
                'pk' => false,
                'parametros' => "",
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            6 => array(
                'nomeCampo' => 'uf',
                'posicaoTxt' => 9,
                'pk' => false,
                'parametros' => "",
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
        );

        $wsExpedicao = new \Wms_WebService_Expedicao();
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

                $cliente['tipoPessoa'] = 'F';
                $cliente[$campo['nomeCampo']] = $valorCampo;
            }
            $wsExpedicao->findClienteByCodigoExterno($repositorios, $cliente);
        }
        $em->flush();
        return true;
    }

    public function importarProdutos($em)
    {
        $exemploTxt = array(
            0 => 'Fabricante;Cód. Material;Grade;Descrição;Classe;Qtd. Vol.;Nome Fabricante;Nome Classe; ',
            1 => '10690;210124;UNICA;FARINHA C/FERMENTO VILMA PLAST. 10X1_DOC;12008;1;Vilma; Alimenticio;',
            2 => '10690;210124;UNICA;FARINHA C/FERMENTO VILMA PLAST. 10X1_DOC;12008;2;Vilma; Alimenticio;',
            3 => '10690;210124;UNICA;FARINHA C/FERMENTO VILMA PLAST. 10X1_DOC;12008;3;Vilma; Alimenticio;'
        );

        $arquivo = array(
            'descricaoLeitura' => 'CLIENTE',
            'nomeArquivo' => "MOC - 002.txt",
            'cabecalho' => true,
            'caracterQuebra' => ";"
        );

        $cabecalhoArquivo = array(
            0 => array(
                'nomeCampo' => 'codProduto',
                'posicaoTxt' => 1,
                'pk' => true,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            1 => array(
                'nomeCampo' => 'grade',
                'posicaoTxt' => 2,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            2 => array(
                'nomeCampo' => 'descricao',
                'posicaoTxt' => 3,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            3 => array(
                'nomeCampo' => 'idFabricante',
                'posicaoTxt' => 0,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            4 => array(
                'nomeCampo' => 'numVolumes',
                'posicaoTxt' => 5,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            5 => array(
                'nomeCampo' => 'classe',
                'posicaoTxt' => 4,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            6 => array(
                'nomeCampo' => 'nomeFabricante',
                'posicaoTxt' => 6,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            ),
            7 => array(
                'nomeCampo' => 'nomeClasse',
                'posicaoTxt' => 7,
                'pk' => false,
                'parametros' => '',
                'tamanhoInicio' => '',
                'tamanhoFim' => ''
            )
        );
        $wsProduto = new \Wms_WebService_Produto();

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
            $produtos = array();
            foreach ($cabecalhoArquivo as $campo) {
                $valorCampo = $conteudoArquivo[$campo['posicaoTxt']];
                if ($campo['tamanhoInicio'] != "") {
                    $valorCampo = substr($valorCampo,$campo['tamanhoInicio'],$campo['tamanhoFim']);
                }
                if ($campo['parametros'] != '') {
                    $valorCampo = str_replace('VALUE',$valorCampo,$campo['parametros']);
                }

                //necessario verificar se irá escrever codigo para pegar o tipo de comercialiçao ou se será passado pelo cliente
                $produtos['tipoComercializacao'] = 1;
                $produtos[$campo['nomeCampo']] = $valorCampo;
            }

            $fabricante['idFabricante'] = $produtos['idFabricante'];
            $fabricante['nome'] = $produtos['nomeFabricante'];

            $classes[0]['idClasse'] = $produtos['classe'];
            $classes[0]['nome'] = $produtos['nomeClasse'];
            $classes[0]['idClassePai'] = null;

            $grade = array(
                0 => 'UNICA'
            );

            //$wsProduto->salvar($produtos['codProduto'], $produtos['descricao'], $produtos['grade'], $produtos['fabricante'], $produtos['tipoComercializacao'], $produtos['classe']);
            $wsProduto->salvarCompleto($produtos['codProduto'], $produtos['descricao'], $produtos['idFabricante'], $produtos['tipoComercializacao'], $produtos['classe'], $grade, $classes, $fabricante );
        }

    }


}