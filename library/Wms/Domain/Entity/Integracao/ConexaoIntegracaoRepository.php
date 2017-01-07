<?php

namespace Wms\Domain\Entity\Integracao;

use Doctrine\ORM\EntityRepository;

class ConexaoIntegracaoRepository extends EntityRepository
{

    public function runQuery($query, $conexao) {
        if ($conexao->getProvedor() == "ORACLE") {
            return $this->oracleQuery($query,$conexao);
        }
    }

    private function oracleQuery($query, $conexao) {
        try {
            ini_set('memory_limit', '-1');
            $usuario = $conexao->getUsuario();
            $senha = $conexao->getSenha();
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $sid = $conexao->getDbName();

            $connectionString = "$servidor:$porta/$sid";
            $conexao = oci_connect($usuario,$senha,$connectionString);

            if (!$conexao) {
                $erro = oci_error();
                throw new \Exception($erro['message']);
            }

            $res = oci_parse($conexao, $query) or die ("erro");
            if (!$res) {
                $erro = oci_error($conexao);
                oci_close($conexao);
                throw new \Exception($erro['message']);
            }

            $e = oci_execute($res);
            if (!$e) {
                $erro = oci_error($res);
                oci_free_statement($res);
                oci_close($conexao);
                throw new \Exception($erro['message']);
            }

            oci_fetch_all($res, $result);

            $arrayResult = array();
            foreach ($result[key($result)] as $rowId => $row) {
                $newLine = array();
                foreach ($result as $columnId => $column) {
                    $newLine[$columnId] = $result[$columnId][$rowId];
                }
                $arrayResult[] = $newLine;
            }

            //fecha a conexão atual
            oci_free_statement($res);
            oci_close($conexao);
            return $arrayResult;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

}
