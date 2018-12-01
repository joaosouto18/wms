<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 11:00
 */

namespace Wms\Domain\Entity\InventarioNovo;


class InventarioNovoRepository extends EntityRepository
{
    /**
     * @return InventarioNovo
     * @throws \Exception
     */


    public function getInventarios($criterio = null, $dados = array())
    {
        // se nao receber a clausula WHERE como parametro, informo where 1=1 somente pra nao dar pau
        $where = "WHERE 1=1 ";
        if($criterio != null )
            $where = $criterio;

        $SQL = "SELECT * from InventarioNovo;
        $records = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
    }

    public function save($params)
    {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventario = new InventarioNovo();

            $statusEntity = $em->getReference('wms:Util\Sigla', Inventario::STATUS_GERADO);
            $enInventario->setStatus($statusEntity);
            $enInventario->setInicio(new \DateTime);
            $enInventario->setDescricao($params['descricao']);

            $em->persist($enInventario);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventario;
    }
}