<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:16
 */

namespace Wms\Service;


use Bisna\Base\Domain\Entity\EntityService;

class InventarioService extends AbstractService
{
    public function teste()
    {
        $teste = $this->findAll();
    }

}