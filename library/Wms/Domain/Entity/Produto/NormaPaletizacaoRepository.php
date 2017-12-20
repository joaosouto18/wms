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
        return $normaPaletizacaoEntity;
    }

    public function getUnitizadoresByProduto($codProduto, $grade) {

        $sql = "SELECT 
                  U.COD_UNITIZADOR , U.DSC_UNITIZADOR
                FROM 
                  UNITIZADOR U
                INNER JOIN NORMA_PALETIZACAO NP ON NP.COD_UNITIZADOR = U.COD_UNITIZADOR
                WHERE NP.COD_NORMA_PALETIZACAO IN (
                    SELECT DISTINCT PDL.COD_NORMA_PALETIZACAO
                    FROM PRODUTO_EMBALAGEM PE
                    INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                    WHERE PE.COD_PRODUTO = $codProduto AND PE.DSC_GRADE = '$grade' )
                OR NP.COD_NORMA_PALETIZACAO IN (
                    SELECT DISTINCT PV.COD_NORMA_PALETIZACAO 
                    FROM PRODUTO_VOLUME PV
                    WHERE PV.COD_PRODUTO = $codProduto AND PV.DSC_GRADE = '$grade' )";

        $result = $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $normas = array();
        foreach ($result as $norma)
            $normas[$norma['COD_UNITIZADOR']] = $norma['DSC_UNITIZADOR'];
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

    public function gravarNormaPaletizacao($embalagemEn,$novaCapacidadePicking, NormaPaletizacaoEntity $normaRelativa = null)
    {
        /** @var \Wms\Domain\Entity\Produto\DadoLogisticoRepository $dadoLogisticoRepo */
        $dadoLogisticoRepo = $this->getEntityManager()->getRepository('wms:Produto\DadoLogistico');

        if (empty($normaRelativa)) {
            $normaEn = new \Wms\Domain\Entity\Produto\NormaPaletizacao();
            $values['numLastro'] = $novaCapacidadePicking;
            $values['numCamadas'] = 1;
            $values['numNorma'] = $novaCapacidadePicking;
            $values['isPadrao'] = 'S';
            $values['idUnitizador'] = $this->getSystemParameterValue('COD_UNITIZADOR_PADRAO');
            $values['numPeso'] = 1;
            $normaEntity = $this->save($normaEn, $values);
            $normaId = $normaEntity->getId();
        } else {
            $normaId = $normaRelativa->getId();
        }

        $valuesDadoLogistico = array(
            'idEmbalagem' => $embalagemEn->getId(),
            'largura' => 1,
            'profundidade' => 1,
            'cubagem' => 1,
            'peso' => 1,
            'altura' => 1,
            'idNormaPaletizacao' => $normaId,
        );
        return $dadoLogisticoRepo->save($valuesDadoLogistico);

    }

}
