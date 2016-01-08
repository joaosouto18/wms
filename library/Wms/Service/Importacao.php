<?php

namespace Wms\Service;

use Doctrine\ORM\Mapping\Entity;
use Wms\Domain\Entity\Fabricante;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;
use Wms\Domain\Entity\Produto\Classe;
use Wms\Module\Web\Controller\Action;

class Importacao
{

    public function importar($em)
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

    private function saveFabricante($idFabricante, $nome, $em)
    {
        $idFabricante = trim($idFabricante);
        $nome = trim($nome);

        $em->beginTransaction();

        try {
            $fabricanteEn = $em->getReference('wms:Fabricante', $idFabricante);
            if (!$fabricanteEn)
                $fabricanteEn = new Fabricante();

            $fabricanteEn->setId($idFabricante);
            $fabricanteEn->setNome($nome);

            return $em->persist($fabricanteEn);
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function saveClasse($idClasse, $nome, $idClassePai = null, $em)
    {
        $idClasse = trim($idClasse);
        $nome = trim($nome);

        $em->beginTransaction();

        try {
            $classeEn = $em->getReference('wms:Produto\Classe', $idClasse);
            if (!$classeEn)
                $classeEn = new Classe();

            $classeEn->setId($idClasse);
            $classeEn->setNome($nome);
            $classeEn->setIdPai($idClassePai);

            return $em->persist($classeEn);

        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function saveCliente($idCliente, $em)
    {
        $em->beginTransaction();
        try {
            $clienteRepo = $em->getRepository('wms:Pessoa\Papel\Cliente');
            $clienteEn = $clienteRepo->findOneBy(array('codClienteExterno' => $idCliente));
            if (!$clienteEn)
                $clienteEn = new Cliente();

            $clienteEn->setCodClienteExterno($idCliente);

        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function saveExpedicao($em,$placaExpedicao)
    {
        $expedicaoRepo = $em->getRepository('wms:Expedicao');
        return $expedicaoRepo->save($placaExpedicao);
    }

    private function savevCarga($em,$carga)
    {
        $cargaRepo = $em->getRepository('wms:Expedicao\Carga');
        return $cargaRepo->save($carga);
    }

    private function savePedido($em,$pedido)
    {
        $pedidoRepo = $em->getRepository('wms:Expedicao\Pedido');
        return $pedidoRepo->save($pedido);
    }

}