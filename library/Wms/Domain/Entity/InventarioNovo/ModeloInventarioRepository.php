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
    /**
     * @return ModeloInventario
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getModelosAtivos($returnType = 'entity', $findBy = [])
    {
        $return = [];
        /** @var ModeloInventario[] $modelos */
        if (!empty($whereExclusive)) {
            $modelos = $this->findBy($findBy);
        }
        else {
            $modelos = $this->findAll();
        }
        if ($returnType === 'json') {
            foreach ($modelos as $modelo) {
                $obj = new \stdClass;
                $obj->id                    = $modelo->getId();
                $obj->dscModelo             = $modelo->getDescricao();
                $obj->dthCriacao            = $modelo->getDthCriacao(true);
                $obj->ativo                 = $modelo->isAtivo();
                $obj->isDefault             = $modelo->isDefault();
                $obj->itemAItem             = $modelo->confereItemAItem();
                $obj->controlaValidade      = $modelo->controlaValidade();
                $obj->exigeUMA              = $modelo->exigeUma();
                $obj->numContagens          = $modelo->getNumContagens();
                $obj->comparaEstoque        = $modelo->comparaEstoque();
                $obj->usuarioNContagens     = $modelo->permiteUsuarioNContagens();
                $obj->contarTudo            = $modelo->forcarContarTudo();
                $obj->volumesSeparadamente  = $modelo->confereVolumesSeparadamente();
                $return[] = $obj;
            }
        } else if($returnType === 'entity') {
            $return = $modelos;
        }

        return $return;
    }
}