<?php


namespace Wms\Util;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class IntegracaoLogger
{

    private static function prepareSQL($acaoId, $sucess, $url, $destino, $observacao, $errNumber, $trace, $query)
    {
        $dthAndamento = (new \DateTime())->format('d/m/Y H:i:s');

        $observacao = (!empty($observacao)) ? "'$observacao'": 'null';
        $trace = (!empty($trace)) ? "'$trace'": 'null';
        $query = (!empty($query)) ? "'". str_replace("'", "''", $query) ."'": 'null';
        $destino = (!empty($destino)) ? "'$destino'": 'null';
        $errNumber = (!empty($errNumber)) ? "'$errNumber'": 'null';
        return "INSERT INTO ACAO_INTEGRACAO_ANDAMENTO (COD_ACAO_INTEGRACAO_ANDAMENTO, COD_ACAO_INTEGRACAO, DTH_ANDAMENTO, IND_SUCESSO, DSC_OBSERVACAO, TRACE, QUERY, ERR_NUMBER, IND_DESTINO, URL)
                                VALUES (SQ_ACAO_INTEGRACAO_AND_01.NEXTVAL, $acaoId, TO_DATE('$dthAndamento', 'DD/MM/YYYY HH24:MI:SS'), '$sucess', $observacao, $trace, $query, $errNumber, $destino, '$url')";

    }

    /**
     * @param $conn Connection
     * @param $acaoId
     * @param $sucess
     * @param $url
     * @param $destino
     * @param $observacao
     * @param $errNumber
     * @param $trace
     * @param $query
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function register($conn, $acaoId, $sucess, $url, $destino, $observacao, $errNumber, $trace, $query)
    {
        $insertSql = self::prepareSQL($acaoId, $sucess, $url, $destino, $observacao, $errNumber, $trace, $query);

        $conn = self::getConnection($conn);

        $conn->query($insertSql)->execute();

        $conn->close();
    }

    private static function getConnection ($conn)
    {
        return DriverManager::getConnection($conn->getParams(), $conn->getConfiguration(), $conn->getEventManager());
    }

    /**
     * @param $conn
     * @param $acaoId
     * @param $state
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function changeState($conn, $acaoId, $state)
    {
        $conn = self::getConnection($conn);
        $conn->query("UPDATE ACAO_INTEGRACAO SET IND_EXECUCAO = '$state' WHERE COD_ACAO_INTEGRACAO = $acaoId")->execute();
        $conn->close();
    }
}