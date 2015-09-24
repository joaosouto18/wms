<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class ModeloRepository extends EntityRepository
{
    public function insert(array $data)
    {
        $modeloEnderecamentoEn = new Modelo();
        $modeloEnderecamentoEn->setDescricao($data['descricao']);
        $modeloEnderecamentoEn->setReferencia($data['referencia']);

        $this->getEntityManager()->persist($modeloEnderecamentoEn);
        return $modeloEnderecamentoEn;

    }

    public function update(array $data)
    {
        $entity = $this->getEntityManager()->getReference('wms:Enderecamento\Modelo', $data['id']);
        $entity->setDescricao($data['descricao']);
        $entity->setReferencia($data['referencia']);

        $this->getEntityManager()->persist($entity);

        return $entity;
    }

    public function delete(array $data)
    {
        $modeloEnderecamentoEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Modelo")->findOneBy(array('id' => $data['id']));

        $this->getEntityManager()->remove($modeloEnderecamentoEn);

        return $modeloEnderecamentoEn;
    }

    public function getModelos()
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('wms:Enderecamento\Modelo', 'm')
            ->orderBy("m.id");

        return $source->getQuery()->getArrayResult();
    }

    public function getIdValue() {
        $valores = array();

        foreach ($this->findAll() as $entity)
            $valores[$entity->getId()] = $entity->getDescricao();

        return $valores;
    }

}