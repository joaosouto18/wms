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
    public function save(ProdutoEntity $produtoEntity, array $values, $webservice = false)
    {
        $em = $this->getEntityManager();
        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();

        /** @var \Wms\Domain\Entity\Produto\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $em->getRepository('wms:Produto\Andamento');

        extract($values);

        $volumeEntity = (isset($id) && is_numeric($id)) ? $this->find($id) : new VolumeEntity;

        if (!$volumeEntity)
            throw new \Exception('Id de volume inválido');

        $volumeEntity->setProduto($produtoEntity);
        $volumeEntity->setGrade($produtoEntity->getGrade());
        $volumeEntity->setLargura($largura);
        $volumeEntity->setProfundidade($profundidade);
        $volumeEntity->setCubagem($cubagem);
        $volumeEntity->setPeso($peso);
        $volumeEntity->setAltura($altura);
        $volumeEntity->setCodigoSequencial($codigoSequencial);
        $volumeEntity->setDescricao($descricao);
        $volumeEntity->setCBInterno($CBInterno);
        $volumeEntity->setImprimirCB($imprimirCB);
        $volumeEntity->setCodigoBarras($codigoBarras);
        $volumeEntity->setCapacidadePicking($capacidadePicking);
        $volumeEntity->setPontoReposicao($pontoReposicao);
        $volumeEntity->setEndereco(null);

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

        if (isset($values['ativarDesativar']) && !empty($values['ativarDesativar'])){
            if ($volumeEntity->getDataInativacao() == null) {
                $volumeEntity->setDataInativacao(new \DateTime());
                $volumeEntity->setUsuarioInativacao($idUsuario);
                $andamentoRepo->save($volumeEntity->getProduto()->getId(), $volumeEntity->getGrade(), $idUsuario, 'Produto Desativado com sucesso');
            }
        } else {
            if (!is_null($volumeEntity->getDataInativacao())) {
                $volumeEntity->setDataInativacao(null);
                $volumeEntity->setUsuarioInativacao(null);
                $andamentoRepo->save($volumeEntity->getProduto()->getId(), $volumeEntity->getGrade(), $idUsuario, 'Produto Ativado com sucesso');
            }
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

    public function getNormasByProduto($codProduto, $grade) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("np.id")
            ->from("wms:Produto\Volume",'v')
            ->innerJoin("v.normaPaletizacao",'np')
            ->where("v.codProduto = " . $codProduto)
            ->andWhere("v.grade = '$grade'")
            ->distinct(true);
        $normasId = $dql->getQuery()->getResult();

        $result = array();
        foreach ($normasId as $normaId){
            $result[] = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id'=>$normaId));
        }

        return $result;
    }

    public function getVolumesByNorma($codNormaPaletizacao, $codProduto, $grade) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("v")
            ->from("wms:Produto\Volume",'v')
            ->innerJoin("v.normaPaletizacao",'np')
            ->where("v.codProduto = '$codProduto'")
            ->andWhere("v.grade = '$grade'")
            ->andWhere("np.id = '$codNormaPaletizacao'");
        $result = $dql->getQuery()->getResult();
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
        $volumeEntity = $this->find($id);

        if (!$volumeEntity)
            throw new \Exception('Codigo de Norma de paletização inválida');

        $this->getEntityManager()->remove($volumeEntity);
        $this->getEntityManager()->flush();

        return true;
    }

}
