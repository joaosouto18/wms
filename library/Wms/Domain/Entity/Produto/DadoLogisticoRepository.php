<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto\DadoLogistico as DadoLogisticoEntity;

/**
 * 
 */
class DadoLogisticoRepository extends EntityRepository
{
    /**
     *
     * @param array $values 
     */
    public function save(array $values)
    {
        $em = $this->getEntityManager();

        extract($values);

        $dadoLogisticoEntity = (isset($id) && is_numeric($id)) ? $this->find($id) : new DadoLogisticoEntity;

        if (!$dadoLogisticoEntity)
            throw new \Exception('Id de dado logistico inválido');
        
        $embalagemEntity = $em->getReference('wms:Produto\Embalagem', $idEmbalagem);
        
        if (!$embalagemEntity)
            throw new \Exception('Id de embalagem inválido');

        $dadoLogisticoEntity->setEmbalagem($embalagemEntity)
                ->setLargura($largura)
                ->setProfundidade($profundidade)
                ->setCubagem($cubagem)
                ->setPeso($peso)
                ->setAltura($altura);

        if(!empty($idNormaPaletizacao)) {
            $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $dadoLogisticoEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }
        
        $em->persist($dadoLogisticoEntity);
        $em->flush($dadoLogisticoEntity);
    }

    /**
     *
     * @param int $id 
     * @return boolean
     * @throws \Exception 
     */
    public function remove($id)
    {
        $dadoLogisticoEntity = $this->find($id);
        
        if(!$dadoLogisticoEntity)
            return true;
        
        $this->getEntityManager()->remove($dadoLogisticoEntity);
        $this->getEntityManager()->flush();
        
        return true;
    }

}
