<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 13:30
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;

class ModeloInventarioLogRepository extends EntityRepository
{
    /**
     * @param $depois array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function newLog($depois, $usuario, $antes = null)
    {

        /** @var ModeloInventario $antes */
        if (empty($antes)) $antes = $this->_em->find("wms:InventarioNovo\ModeloInventario", $depois['id'])->toArray();

        $arrLog = [
            "modeloInventario" => $this->_em->getReference("wms:InventarioNovo\ModeloInventario", $depois['id']),
            "usuario" => $usuario
        ];

        unset($depois['id']);
        unset($depois['dthCriacao']);
        unset($depois['controller']);
        unset($depois['action']);
        unset($depois['module']);

        $modified = false;
        foreach ($depois as $key => $value) {
            if ($value !== $antes[$key]) {
                $modified = true;
                break;
            }
        }

        if (!$modified) return;

        foreach ($depois as $key => $value) {
            $arrLog[$key."Antes"] = $antes[$key];
            $arrLog[$key."Depois"] = $value;
        }

        $this->_em->persist(Configurator::configure( new ModeloInventarioLog(), $arrLog));
    }
}