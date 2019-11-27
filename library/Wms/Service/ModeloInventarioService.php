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
use Wms\Domain\Entity\Usuario;

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
            /** @var Usuario $pessoaEntity */
            $pessoaEntity = $this->em->getReference('wms:Usuario', $idPessoa);

            if (empty($default) && $data['default'] !== 'S') {
                $data['default'] = true;
            } else if (!empty($default) && $data['default'] == 'S' && (!empty($data['id'] || $data['id'] !== $default->getId()))) {

                $arr = $default->toArray();
                $arr['default'] = false;
                $logRepo->newLog($arr, $pessoaEntity);
                unset($arr['dthCriacao']);
                parent::save(Configurator::configure($default, $arr), false);
            }

            /** @var ModeloInventario $entity */
            if (!empty($data['id'])) {
                $entity = $this->find($data['id']);
                $data['ativo'] = $entity->isAtivo();
                $logRepo->newLog($data, $pessoaEntity, $entity->toArray());
            } else {
                $entity = new $this->entityName();
                $entity->setUsuario($pessoaEntity);
                $entity->setAtivo(true);
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
                self::defineNextDefault($pessoaEntity, $logRepo);
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

    private function defineNextDefault($pessoaEntity, $logRepo)
    {
        /** @var ModeloInventario[] $ativos */
        $ativos = $this->findBy(['default' => 'N', 'ativo' => 'S'], ['id' => 'DESC']);

        if (!empty($ativos)) {
            $arr = $ativos[0]->toArray();
            $arr['default'] = true;
            $logRepo->newLog($arr, $pessoaEntity, $ativos[0]->toArray());
            unset($arr['dthCriacao']);
            parent::save(Configurator::configure($ativos[0], $arr), false);
        }
    }

}