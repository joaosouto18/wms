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
    public function save(ModeloInventario $entity, $params) {

        $entity->setDescricao($params['descricao']);
        $entity->setAtivo($params['ativo']);
        $entity->setItemAItem($params['itemAItem']);
        $entity->setDefault($params['default']);
        $entity->setControlaValidade($params['controlaValidade']);
        $entity->setExigeUma($params['exigeUma']);
        $entity->setComparaEstoque($params['comparaEstoque']);
        $entity->setUsuarioNContagens($params['usuarioNContagens']);
        $entity->setContarTudo($params['contarTudo']);
        $entity->setVolumesSeparadamente($params['volumesSeparadamente']);
        $entity->setNumContagens($params['numContagens']);

        $this->_em->persist($entity);

        $this->_em->flush();

        return $entity;
    }

    public function getModelos() {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('wms:InventarioNovo\ModeloInventario', 'm')
            ->orderBy("m.id");

        return $source->getQuery()->getArrayResult();
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
        } else if($returnType === 'entity') {
            $return = $modelos;
        }

        return $return;
    }
}