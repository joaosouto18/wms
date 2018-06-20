<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class ModeloEstruturaArmazenagemRepository extends EntityRepository
{
    public function insert(array $data)
    {
        $modeloEstruturaArmazenagemEn = new ModeloEstruturaArmazenagem();

        //obter entidade do modelo
        $modelo = $this->getEntityManager()->getReference("wms:Enderecamento\Modelo", $data['modeloEnderecamento']);
        $modeloEstruturaArmazenagemEn->setModeloEnderecamento($modelo);

        //obter entidade da estrutura de armazenagem
        $estruturaArmazenagem = $this->getEntityManager()->getReference("wms:Armazenagem\Estrutura\Tipo", $data['idTipoEstruturaArmazenagem']);
        $modeloEstruturaArmazenagemEn->setTipoEstruturaArmazenagem($estruturaArmazenagem);

        $modeloEstruturaArmazenagemEn->setPrioridade($data['prioridadeEstruturaArmazenagem']);

        $this->getEntityManager()->persist($modeloEstruturaArmazenagemEn);
        return $modeloEstruturaArmazenagemEn;
    }

    public function delete(array $data)
    {
        $estruturaArmazenagemEn = $this->getEntityManager()->getRepository("wms:Enderecamento\ModeloEstruturaArmazenagem")->findBy(array('modeloEnderecamento' => $data['modeloEnderecamento']));

        foreach ($estruturaArmazenagemEn as $estruturaArmazenagem) {
            $this->getEntityManager()->remove($estruturaArmazenagem);
        }

        return $estruturaArmazenagemEn;
    }

}