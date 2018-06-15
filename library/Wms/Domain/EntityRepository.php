<?php

namespace Wms\Domain;

use Doctrine\ORM\EntityRepository as EntityRepositoryDoctrine;


class EntityRepository extends EntityRepositoryDoctrine
{
    /**
     * Retorna os valores baseados nos campos Id e Descrição
     *
     * @param array $criteria Criterio da busca
     * @return type
     */
    public function getIdDescricao(array $criteria = array())
    {
        $array = array();
        foreach ($this->findBy($criteria) as $entity)
            $array[$entity->getId()] = $entity->getDescricao();

        return $array;
    }

    /**
     * Retorna os valores baseados nos campos Id e Nome
     *
     * @param array $criteria
     * @return type
     */
    public function getIdNome(array $criteria = array())
    {
        $array = array();
        foreach ($this->findBy($criteria) as $entity)
            $array[$entity->getId()] = $entity->getNome();

        return $array;
    }

    static function conexaoViewERP()
    {
        $config = \Zend_Registry::get('config');
        $conexao = oci_connect($config->database->viewErp->user,
            $config->database->viewErp->password,
            $config->database->viewErp->dbname,
            $config->database->viewErp->characterSet);
        return $conexao;
    }

    static function nativeQuery($query, $fetch = 'all', $conexao = null)
    {
        $config = \Zend_Registry::get('config');
        if ($conexao == null) {
            $conexao = oci_connect($config->resources->doctrine->dbal->connections->default->parameters->user,
                $config->resources->doctrine->dbal->connections->default->parameters->password,
                $config->resources->doctrine->dbal->connections->default->parameters->dbname,
                $config->resources->doctrine->dbal->connections->default->parameters->charset);
        }

        if (!$conexao) {
            $erro = oci_error();
            throw new \Exception($erro['message']);
        }

        $res = oci_parse($conexao, $query) or die ("erro");
        if (!$res) {
            $erro = oci_error($conexao);
            oci_close($conexao);
            throw new \Exception($erro['message'] . " - consulta: " . $query);
        }

        $e = oci_execute($res);
        if (!$e) {
            $erro = oci_error($res);
            oci_free_statement($res);
            oci_close($conexao);
            throw new \Exception($erro['message'] . " - consulta: " . $query);
        }

        if ($fetch == 'all') {
            oci_fetch_all($res, $result);
        }

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
    }

}
