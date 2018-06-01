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
            case ConexaoIntegracao::PROVEDOR_FIREBIRD:
                return self::firebirdQuery($query, $conexao);

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

    private function mssqlQuery($query, $conexao) {
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

            //mssql_select_db($dbName, $conexao) or die(mssql_get_last_message());

            if ($conexao == false) {
                $error = $conexao->connect_error;
                throw new \Exception("Não foi possível conectar: $error");
            }
            $result = \sqlsrv_query($conexao, $query);

            if (!$result || $result == false) {
                $error = \sqlsrv_errors();
                throw new \Exception($error);
            }
            $vetResult = array();
            $i = 0;

            while( $row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC) ) {
                $vetResult[$i]['CODPRO'] = isset($row['CODPRO']) ? $row['CODPRO'] : null;
                $vetResult[$i]['GRADE'] = isset($row['GRADE']) ? $row['GRADE'] : null;
                $vetResult[$i]['QTDFAT'] = isset($row['QTDFAT']) ? $row['QTDFAT'] : null;
                $vetResult[$i]['COD_PRODUTO'] = $row['COD_PRODUTO'];
                $vetResult[$i]['DSC_GRADE'] = $row['DSC_GRADE'];
                $vetResult[$i]['DESCRICAO_PRODUTO'] = $row['DESCRICAO_PRODUTO'];
                $vetResult[$i]['CODIGO_CLASSE_NIVEL_1'] = $row['CODIGO_CLASSE_NIVEL_1'];
                $vetResult[$i]['DSC_CLASSE_NIVEL_1'] = $row['DSC_CLASSE_NIVEL_1'];
                $vetResult[$i]['CODIGO_CLASSE_NIVEL_2'] = $row['CODIGO_CLASSE_NIVEL_2'];
                $vetResult[$i]['DSC_CLASSE_NIVEL_2'] = $row['DSC_CLASSE_NIVEL_2'];
                $vetResult[$i]['CODIGO_FABRICANTE'] = $row['CODIGO_FABRICANTE'];
                $vetResult[$i]['DESCRICAO_FABRICANTE'] = $row['DESCRICAO_FABRICANTE'];
                $vetResult[$i]['DESCRICAO_EMBALAGEM'] = $row['DESCRICAO_EMBALAGEM'];
                $vetResult[$i]['PESO_VARIAVEL'] = $row['PESO_VARIAVEL'];
                $vetResult[$i]['QTD_EMBALAGEM'] = $row['QTD_EMBALAGEM'];
                $vetResult[$i]['COD_BARRAS'] = $row['COD_BARRAS'];
                $vetResult[$i]['PESO_BRUTO_EMBALAGEM'] = $row['PESO_BRUTO_EMBALAGEM'];
                $vetResult[$i]['ALTURA_EMBALAGEM'] = $row['ALTURA_EMBALAGEM'];
                $vetResult[$i]['LARGURA_EMBALAGEM'] = $row['LARGURA_EMBALAGEM'];
                $vetResult[$i]['PROFUNDIDADE_EMBALAGEM'] = $row['PROFUNDIDADE_EMBALAGEM'];
                $vetResult[$i]['CUBAGEM_EMBALAGEM'] = isset($row['CUBAGEM_EMBALAGEM']) ? $row['CUBAGEM_EMBALAGEM'] : null;
                $vetResult[$i]['EMBALAGEM_ATIVA'] = $row['EMBALAGEM_ATIVA'];
                $vetResult[$i]['DTH'] = $row['DTH'];

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

    private function firebirdQuery($query, $conexao)
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

            if ($resultado === true) {
                ibase_close($conexao);
                return true;
            }

            if ($resultado === false) {
                $errmsg = ibase_errmsg();
                ibase_close($conexao);
                throw new \Exception($errmsg);
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

}
