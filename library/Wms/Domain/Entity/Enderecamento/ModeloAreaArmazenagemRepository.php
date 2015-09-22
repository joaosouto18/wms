<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class ModeloAreaArmazenagemRepository extends EntityRepository
{
    public function insert(array $data)
    {
        $modeloAreaArmazenagemEn = new ModeloAreaArmazenagem();

        //obter entidade do modelo
        $modelo = $this->getEntityManager()->getReference("wms:Enderecamento\Modelo", $data['modeloEnderecamento']);
        $modeloAreaArmazenagemEn->setModeloEnderecamento($modelo);

        //obter entidade da area de armazenagem
        $areaArmazenagem = $this->getEntityManager()->getReference("wms:Deposito\AreaArmazenagem", $data['idAreaArmazenagem']);
        $modeloAreaArmazenagemEn->setAreaArmazenagem($areaArmazenagem);

        $modeloAreaArmazenagemEn->setPrioridade($data['prioridadeAreaArmazenagem']);

        $this->getEntityManager()->persist($modeloAreaArmazenagemEn);
        return $modeloAreaArmazenagemEn;

    }

    public function delete(array $data)
    {
        $areaArmazenagemEn = $this->getEntityManager()->getRepository("wms:Enderecamento\ModeloAreaArmazenagem")->findBy(array('modeloEnderecamento' => $data['modeloEnderecamento']));

        foreach ($areaArmazenagemEn as $areaArmazenagem) {
            $this->getEntityManager()->remove($areaArmazenagem);
        }

        return $areaArmazenagemEn;
    }

}