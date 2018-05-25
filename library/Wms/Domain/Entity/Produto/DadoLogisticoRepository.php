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
        return $dadoLogisticoEntity;
    }

    public function verificaDadoLogistico($itemDadoLogistico){
        $dadoLogistico = $this->findBy(array('normaPaletizacao' => $itemDadoLogistico['idNormaPaletizacao']));
        $ret = false;
        if(empty($dadoLogistico)){
            $ret = true;
        }
        return $ret;
    }

    public function getDadoNorma($idProduto, $dscGrade = 'UNICA'){
        $SQL = "SELECT COD_PRODUTO_DADO_LOGISTICO, COD_NORMA_PALETIZACAO FROM PRODUTO_DADO_LOGISTICO WHERE COD_PRODUTO_EMBALAGEM IN 
                (SELECT COD_PRODUTO_EMBALAGEM FROM PRODUTO_EMBALAGEM WHERE COD_PRODUTO = '$idProduto' AND DSC_GRADE = '$dscGrade')";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
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
