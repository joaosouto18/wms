<?php


namespace Wms\Domain\Entity\Enderecamento;


use Wms\Domain\Configurator;
use Wms\Domain\Entity\Usuario;
use Wms\Domain\EntityRepository;

class MotivoMovimentacaoRepository extends EntityRepository
{

    /**
     * @param MotivoMovimentacao $entity
     * @param array $params
     * @param bool $flush
     * @return MotivoMovimentacao
     * @throws \Exception
     */
    public function save(MotivoMovimentacao $entity, array $params, $flush = true)
    {
        try {
            /** @var MotivoMovimentacao $entity */
            $entity = Configurator::configure($entity, $params['identificacao']);

            if (empty($entity->getUsuarioCriacao())) {
                $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
                /** @var Usuario $pessoaEntity */
                $pessoaEntity = $this->_em->getReference('wms:Usuario', $idPessoa);
                $entity->setUsuarioCriacao($pessoaEntity);
            }

            $this->_em->persist($entity);
            if ($flush) $this->_em->flush($entity);

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id int
     * @param $flush bool
     * @throws \Exception
     */
    public function exclusaoLogica($id, $flush = true)
    {
        try{
            /** @var MotivoMovimentacao $entity */
            $entity = $this->find($id);
            $entity->setIsAtivo(false);

            $this->_em->persist($entity);
            if ($flush) $this->_em->flush($entity);

        } catch (\Exception $e) {
            throw $e;
        }
    }

}