<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto\NormaPaletizacao as NormaPaletizacaoEntity;

/**
 * 
 */
class NormaPaletizacaoRepository extends EntityRepository
{

    /**
     *
     * @param NormaPaletizacaoEntity $normaPaletizacaoEntity
     * @param array $values
     * @return int Id da norma de paletizacao
     * @throws \Exception 
     */
    public function save(NormaPaletizacaoEntity $normaPaletizacaoEntity, array $values)
    {
        $em = $this->getEntityManager();

        extract($values);

        $unitizadorEntity = $em->getReference('wms:Armazenagem\Unitizador', $idUnitizador);

        if (!$unitizadorEntity)
            throw new \Exception('Codigo de unitizador inválido');

        $normaPaletizacaoEntity->setUnitizador($unitizadorEntity)
                ->setNumLastro($numLastro)
                ->setNumCamadas($numCamadas)
                ->setIsPadrao($isPadrao)
                ->setNumNorma($numNorma)
                ->setNumPeso($numPeso);

        $em->persist($normaPaletizacaoEntity);
        $em->flush();

        // atualiza id no array de normas de paletizacao
        return $normaPaletizacaoEntity->getId();
    }

    public function getUnitizadoresByProduto($codProduto, $grade) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('
                NVL(unitizador_embalagem.id,        unitizador_volume.id) idUnitizador,
                NVL(unitizador_embalagem.descricao, unitizador_volume.descricao) descricaoUnitizador,
                NVL(np_embalagem.numLastro,  np_volume.numLastro) numLastro,
                NVL(np_embalagem.numCamadas, np_volume.numCamadas) numCamadas,
                NVL(np_embalagem.numPeso,    np_volume.numPeso) numPeso,
                NVL(np_embalagem.numNorma,   np_volume.numNorma) numNorma,
                NVL(np_embalagem.id,         np_volume.id) idNorma'
            )
            ->from('wms:Produto', 'p')
            ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade')
            ->leftJoin('pe.dadosLogisticos', 'dl')
            ->leftJoin('dl.normaPaletizacao', 'np_embalagem')
            ->leftJoin('np_embalagem.unitizador', 'unitizador_embalagem')
            ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade')
            ->leftJoin('pv.normaPaletizacao', 'np_volume')
            ->leftJoin('np_volume.unitizador', 'unitizador_volume')
            ->where("p.id = '$codProduto'")
            ->andWhere("p.grade = '$grade'");

        $result = $dql->getQuery()->getResult();

        $normas = array();
        foreach ($result as $norma)
            $normas[$norma['idNorma']] = $norma['descricaoUnitizador'];
        return $normas;

    }

    /**
     *
     * @param int $id 
     * @return boolean
     * @throws \Exception 
     */
    public function remove($id)
    {
        $normaPaletizacaoEntity = $this->find($id);
        
        if(!$normaPaletizacaoEntity)
            throw new \Exception('Codigo de Norma de paletização inválida');
        
        $this->getEntityManager()->remove($normaPaletizacaoEntity);
        $this->getEntityManager()->flush();
        
        return true;
    }

}
