<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 13/12/2018
 * Time: 09:44
 */

namespace Wms\Service;


use Wms\Domain\Configurator;
use Wms\Domain\Entity\InventarioNovo\ModeloInventario;
use Wms\Domain\Entity\InventarioNovo\ModeloInventarioLogRepository;

class ModeloInventarioService extends AbstractService
{
    public function salvar($data)
    {
        $this->em->beginTransaction();
        try {
            /** @var ModeloInventarioLogRepository $logRepo */
            $logRepo = $this->em->getRepository("wms:InventarioNovo\ModeloInventarioLog");
            /** @var ModeloInventario $default */
            $default = $this->findOneBy(['default' => 'S']);

            $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
            $pessoaEntity = $this->em->getReference('wms:Usuario', $idPessoa);

            if (empty($default) && $data['default'] !== 'S') {

                $data['default'] = 'S';

            } else if (!empty($default) && $data['default'] == 'S' &&
                (!isset($data['id']) || (isset($data['id']) && !empty($data['id']) && $data['id'] !== $default->getId()))) {

                $arr = $default->toArray();
                $arr['default'] = false;
                $logRepo->newLog($arr, $pessoaEntity);
                unset($arr['dthCriacao']);
                parent::save(Configurator::configure($default, $arr), false);
            }

            if (isset($data['id']) && !empty($data['id'])) {
                $entity = $this->find($data['id']);
                $logRepo->newLog($data, $pessoaEntity, $entity->toArray());
            } else {
                $entity = new $this->entityName();
                $entity->setUsuario($pessoaEntity);
            }

            $modeloEn = parent::save(Configurator::configure($entity, $data), false);

            $this->em->flush();
            $this->em->commit();

            return $modeloEn;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function remover($id)
    {
        $this->em->beginTransaction();
        try{
            /** @var ModeloInventarioLogRepository $logRepo */
            $logRepo = $this->em->getRepository("wms:InventarioNovo\ModeloInventarioLog");

            $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
            $pessoaEntity = $this->em->getReference('wms:Usuario', $idPessoa);

            /** @var ModeloInventario $entity */
            $entity = $this->find($id);

            if ($entity->isDefault()) {
                /** @var ModeloInventario $next */
                $next = $this->findOneBy(['default' => 'N', 'ativo' => 'S']);

                $arr = $next->toArray();
                $arr['default'] = true;
                $logRepo->newLog($arr, $pessoaEntity, $next->toArray());
                unset($arr['dthCriacao']);
                parent::save(Configurator::configure($next, $arr), false);
            }

            $removed = $entity->toArray();
            $removed['default'] = false;
            $removed['ativo'] = false;
            $logRepo->newLog($removed, $pessoaEntity, $entity->toArray());
            unset($removed['dthCriacao']);
            parent::save(Configurator::configure($entity, $removed), false);

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

}