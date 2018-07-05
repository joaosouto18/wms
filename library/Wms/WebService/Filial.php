<?php

use Wms\Domain\Entity\Filial;

/**
 * 
 */
class Wms_WebService_Filial extends Wms_WebService
{

    /**
     * Adiciona um Filial no WMS
     * 
     * @param string $id ID 
     * @param string $cnpj Cnpj da empresa
     * @param string $nome Nome
     * @param string $nomeFantasia Nome Fantasia
     * @return boolean|Exception se o Filial foi inserido com sucesso ou não
     */
    public function salvar($id, $cnpj, $nome, $nomeFantasia = '')
    {
        $id = trim($id);
        $cnpj = trim($cnpj);
        $nome = trim($nome);
        $nomeFantasia = trim($nomeFantasia);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $service = $this->__getServiceLocator()->getService('Filial');

        $em->beginTransaction();

        try {
            $filial = $service->findOneBy(array('idExterno' => $id));

            if ($filial == null)
                $filial = new \Wms\Domain\Entity\Filial;

            $values = array(
                'pessoa' => array(
                    'tipo' => 'J',
                    'juridica' => array(
                        'idExterno' => $id,
                        'idTipoOrganizacao' => '114',
                        'idRamoAtividade' => '272',
                        'dataAbertura' => '01/01/1970',
                        'nome' => $nome,
                        'nomeFantasia' => $nomeFantasia,
                        'cnpj' => $cnpj,
                        'isAtivo' => 'S',
                    )
                )
            );

            $em->getRepository('wms:Filial')->save($filial, $values);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Retorna uma matriz com todas as filiais cadastrados no WMS
     * 
     * @return array|Exception
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('f.id, j.nome, j.cnpj')
                ->from('wms:Filial', 'f')
                ->innerJoin('f.juridica', 'j')
                ->orderBy('j.nome')
                ->getQuery()
                ->getArrayResult();

        return $result;
    }

}