<?php

namespace Wms\Domain\Entity\Integracao;

use Doctrine\ORM\EntityRepository;

class ConexaoIntegracaoRepository extends EntityRepository
{

    /**
     * @param $query string
     * @param $conexao ConexaoIntegracao
     * @return array
     */
    public function runQuery($query, $conexao, $update = false)
    {
        switch ($conexao->getProvedor()) {

            case ConexaoIntegracao::PROVEDOR_ORACLE:
                return self::oracleQuery($query,$conexao, $update);

            case ConexaoIntegracao::PROVEDOR_MYSQL:
                return self::mysqlQuery($query,$conexao);

            default:
                throw new \Exception("Provedor não específicado");
        }
        /*
        if ($conexao->getProvedor() == "ORACLE") {
            return $this->oracleQuery($query,$conexao);
        }*/
    }

    private function mysqlQuery($query, $conexao)
    {
        try {
            ini_set('memory_limit', '-1');
            $usuario = $conexao->getUsuario();
            $senha = trim($conexao->getSenha());
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $dbName = $conexao->getDbName();

            $conexao = new \mysqli($servidor, $usuario, $senha, $dbName, $porta);

            if ($conexao->connect_errno > 0) {
                $error = $conexao->connect_error;
                throw new \Exception("Não foi possível conectar: $error");
            }

            $result = $conexao->query($query);

            if (!$result) {
                $error = $conexao->error;
                throw new \Exception($error);
            }

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e2) {
            throw new \Exception($e2->getMessage());
        }

    }


    private function oracleQuery($query, $conexao, $update)
    {
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

            $arrayResult = array();
            if ($update == false) {
                oci_fetch_all($res, $result);

                foreach ($result[key($result)] as $rowId => $row) {
                    $newLine = array();
                    foreach ($result as $columnId => $column) {
                        $newLine[$columnId] = $result[$columnId][$rowId];
                    }
                    $arrayResult[] = $newLine;
                }
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
