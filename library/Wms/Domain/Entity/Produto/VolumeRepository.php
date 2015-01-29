<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto as ProdutoEntity,
    Wms\Domain\Entity\Produto\Volume as VolumeEntity,
    Wms\Util\CodigoBarras,
    Core\Util\Produto,
    Wms\Util\Endereco as EnderecoUtil;

/**
 * 
 */
class VolumeRepository extends EntityRepository
{

    /**
     *
     * @param array $values 
     */
    public function save(ProdutoEntity $produtoEntity, array $values)
    {
        $em = $this->getEntityManager();

        extract($values);

        $volumeEntity = (isset($id) && is_numeric($id)) ? $this->find($id) : new VolumeEntity;

        if (!$volumeEntity)
            throw new \Exception('Id de volume inválido');

        

        $volumeEntity->setProduto($produtoEntity)
                ->setGrade($produtoEntity->getGrade())
                ->setLargura($largura)
                ->setProfundidade($profundidade)
                ->setCubagem($cubagem)
                ->setPeso($peso)
                ->setAltura($altura)
                ->setCodigoSequencial($codigoSequencial)
                ->setDescricao($descricao)
                ->setCBInterno($CBInterno)
                ->setImprimirCB($imprimirCB)
                ->setCodigoBarras($codigoBarras)
                ->setEndereco(null);

        
        //valida o endereco informado
        if (!empty($endereco)) {
            $endereco = EnderecoUtil::separar($endereco);
            $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
            $enderecoEntity = $enderecoRepo->findOneBy(array('rua' => $endereco['RUA'], 'predio' => $endereco['PREDIO'], 'nivel' => $endereco['NIVEL'], 'apartamento' => $endereco['APTO']));

            if (!$enderecoEntity) {
                throw new \Exception('Não existe o Endereço informado no volume ' . $descricao);
            }
            
            $volumeEntity->setEndereco($enderecoEntity);
        }

        if (!empty($idNormaPaletizacao)) {
            $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $volumeEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($volumeEntity);
        
        // gera o codigo de barras com base no id do volume. Ex: 12340102 / 12340202
        if ($CBInterno == 'S') {
            $codigoBarras = $volumeEntity->getId();
            $codigoBarras .= Produto::preencheZerosEsquerda($codigoSequencial, 2);
            $codigoBarras .= Produto::preencheZerosEsquerda($produtoEntity->getNumVolumes(), 2);
            $codigoBarras = CodigoBarras::formatarCodigoEAN128Volume($codigoBarras);
            $volumeEntity->setCodigoBarras($codigoBarras);
        }
        
    }

    /**
     *
     * @param int $id 
     * @return boolean
     * @throws \Exception 
     */
    public function remove($id)
    {
        $volumeEntity = $this->find($id);

        if (!$volumeEntity)
            throw new \Exception('Codigo de Norma de paletização inválida');

        $this->getEntityManager()->remove($volumeEntity);
        $this->getEntityManager()->flush();

        return true;
    }

}
