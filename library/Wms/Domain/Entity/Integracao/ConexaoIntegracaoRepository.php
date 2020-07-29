<?php

namespace Wms\Domain\Entity\Integracao;

use Doctrine\ORM\EntityRepository;

class ConexaoIntegracaoRepository extends EntityRepository {

    /**
     * @param $query string
     * @param $conexao ConexaoIntegracao
     * @return array
     */
    public function runQuery($query, $conexao, $update = false) {
        switch ($conexao->getProvedor()) {

            case ConexaoIntegracao::PROVEDOR_ORACLE:
                return self::oracleQuery($query, $conexao, $update);
            case ConexaoIntegracao::PROVEDOR_MYSQL:
                return self::mysqlQuery($query, $conexao);
            case ConexaoIntegracao::PROVEDOR_MSSQL:
                return self::mssqlQuery($query, $conexao);
            case ConexaoIntegracao::PROVEDOR_SQLSRV:
                return self::sqlSrvQuery($query, $conexao);
            case ConexaoIntegracao::PROVEDOR_FIREBIRD:
                return self::firebirdQuery($query, $conexao, $update);
            case ConexaoIntegracao::PROVEDOR_POSTGRE:
                return self::postgreQuery($query, $conexao);
            case ConexaoIntegracao::PROVEDOR_DB2:
                return self::db2Query($query, $conexao);
            default:
                throw new \Exception("Provedor não específicado");
        }
        /*
          if ($conexao->getProvedor() == "ORACLE") {
          return $this->oracleQuery($query,$conexao);
          } */
    }

    private function mysqlQuery($query, $conexao) {
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
                throw new \Exception($conexao->error);
            } else if (is_a($result, \mysqli_result::class)) {
                return $result->fetch_all(MYSQLI_ASSOC);
            } else {
                return $result;
            }
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e2) {
            throw new \Exception($e2->getMessage());
        }
    }

    private function mssqlQuery($query, $conexao) {
        try {
            ini_set('memory_limit', '-1');
            ini_set('mssql.timeout', 60 * 10);

            $usuario = $conexao->getUsuario();
            $senha = trim($conexao->getSenha());
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $dbName = $conexao->getDbName();
            $connInfo = array(
                "Database" => $dbName,
                "UID" => $usuario,
                "PWD" => $senha
            );
            $conexao = mssql_connect("$servidor", $usuario, $senha);
            mssql_select_db($dbName);

            $result = mssql_query($query);

            $vetResult = array();
            $i = 0;

            while($row = mssql_fetch_array($result) ) {
                foreach ($row as $indice => $valor) {
                    $vetResult[$i][$indice] = $valor;
                }
                $i++;
            }

            mssql_close();
            return $vetResult;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e2) {
            throw new \Exception($e2->getMessage());
        }
    }

    private function sqlSrvQuery($query, $conexao) {
        try {
            ini_set('memory_limit', '-1');
            $usuario = $conexao->getUsuario();
            $senha = trim($conexao->getSenha());
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $dbName = $conexao->getDbName();
            $connInfo = array(
                "Database" => $dbName,
                "UID" => $usuario,
                "PWD" => $senha
            );
            $conexao = \sqlsrv_connect($servidor, $connInfo);

            if ($conexao == false) {
                $errors = \sqlsrv_errors();
                foreach( $errors as $error ) {
                    throw new \Exception($error[ 'message']);
                }
            }


            $result = \sqlsrv_query($conexao, $query);

            if (!$result || $result == false) {
                $errors = \sqlsrv_errors();
                foreach( $errors as $error ) {
                    throw new \Exception($error[ 'message']);
                }
            }
            $vetResult = array();
            $i = 0;

            while( $row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC) ) {
                foreach ($row as $indice => $valor) {
                    $vetResult[$i][$indice] = $valor;
                }
                $i++;
            }

            return $vetResult;
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e2) {
            throw new \Exception($e2->getMessage());
        }
    }

    private function oracleQuery($query, $conexao, $update) {
        try {
            ini_set('memory_limit', '-1');
            $usuario = $conexao->getUsuario();
            $senha = $conexao->getSenha();
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $sid = $conexao->getDbName();

            $connectionString = "$servidor:$porta/$sid";
            $conexao = oci_connect($usuario, $senha, $connectionString);

            if (!$conexao) {
                $erro = oci_error();
                throw new \Exception($erro['message']);
            }

            $res = oci_parse($conexao, $query) or die("erro");
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

    private function postgreQuery($query, $conexao)
    {
        try {
            ini_set('memory_limit', '-1');
            $usuario = $conexao->getUsuario();
            $senha = $conexao->getSenha();
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $sid = $conexao->getDbName();


            if(!($conexao = pg_connect("host=$servidor dbname=$sid port=$porta user=$usuario password=$senha"))) {
                throw new \Exception(pg_result_error($conexao));
            }

            $result = pg_query($conexao,$query);
            if (!$result) {
                pg_close($conexao);
                throw new \Exception(pg_result_error($result));
            }

            $arr = pg_fetch_all($result);

            if (!$arr)
                $arr = array();

            pg_close($conexao);
            return $arr;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    private function firebirdQuery($query, $conexao, $update)
    {
        try {
            ini_set('memory_limit', '-1');
            $usuario = $conexao->getUsuario();
            $senha = $conexao->getSenha();
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();
            $sid = $conexao->getDbName();

            $connectionString = "$servidor/$porta:$sid";

            $conexao = ibase_connect($connectionString, $usuario, $senha);
            if (!($conexao)) {
                ibase_close($conexao);
                throw new \Exception(ibase_errmsg());
            }

            $resultado = ibase_query($conexao, $query);

	    if (true === $update || true === $resultado) {
                ibase_close($conexao);
                return $resultado;
            } else if (false === $resultado){
                ibase_close($conexao);
                throw new \Exception(ibase_errmsg());
            }

            $result = array();
            while ($row = ibase_fetch_assoc ($resultado)) {
                $result[] = $row;
            }

            ibase_close($conexao);
            return $result;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    private function db2Query($query, $conexao)
    {

        try {
            ini_set('memory_limit', '-1');

            $database = $conexao->getDbName();
            $usuario = $conexao->getUsuario();
            $senha = $conexao->getSenha();
            $servidor = $conexao->getServidor();
            $porta = $conexao->getPorta();

            $conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database;" .
                "HOSTNAME=$servidor;PORT=$porta;PROTOCOL=TCPIP;UID=$usuario;PWD=$senha; ";
            $conn = db2_connect($conn_string, $usuario, $senha);

            if (!$conn) {
                throw new \Exception("Não foi possível se conectar no banco $database no servidor $usuario/$servidor:$porta - Motivo: " . db2_conn_errormsg());
            }

            $stmt = db2_prepare($conn, $query);
            $r = db2_execute($stmt);

            var_dump($r);exit;

            $result = array();
            while ($row = db2_fetch_assoc($stmt)) {
                $result[] = $row;
            }

            if (!$result) {
                if (db2_stmt_error($stmt) != 'ERR02000') {

                } else {
                    throw new \Exception("ERR" . db2_stmt_error($stmt) . " - " .  db2_stmt_errormsg($stmt) );
                }
            }
            db2_close($conn);

            return $result;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }


}
