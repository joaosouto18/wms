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

class ModeloInventarioRepository extends EntityRepository
{

    public function getModelos($returnType = 'entity', $findBy = [])
    {
        $return = [];
        /** @var ModeloInventario[] $modelos */
        if (!empty($findBy)) {
            $modelos = $this->findBy($findBy);
        }
        else {
            $modelos = $this->findAll();
        }
        if ($returnType === 'stdClass') {
            foreach ($modelos as $modelo) {
                $obj = new \stdClass;
                $obj->id                    = $modelo->getId();
                $obj->dscInventario             = $modelo->getDescricao();
                $obj->dthCriacao            = $modelo->getDthCriacao(true);
                $obj->ativo                 = $modelo->isAtivo();
                $obj->isDefault             = $modelo->isDefault();
                $obj->itemAItem             = $modelo->confereItemAItem();
                $obj->controlaValidadeLbl   = $modelo->controlaValidade();
                $obj->controlaValidade      = $modelo->getControlaValidade();
                $obj->exigeUMA              = $modelo->exigeUma();
                $obj->numContagens          = $modelo->getNumContagens();
                $obj->comparaEstoque        = $modelo->comparaEstoque();
                $obj->usuarioNContagens     = $modelo->permiteUsuarioNContagens();
                $obj->contarTudo            = $modelo->forcarContarTudo();
                $obj->volumesSeparadamente  = $modelo->confereVolumesSeparadamente();
                $return[] = $obj;
            }
        } else if ($returnType === 'entity') {
            $return = $modelos;
        } else if ($returnType === 'array') {
            foreach ($modelos as $modelo) {
                $return[] = $modelo->toArray();
            }
        }

        return $return;
    }
}