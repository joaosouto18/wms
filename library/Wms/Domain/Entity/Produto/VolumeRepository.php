<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto as ProdutoEntity,
    Wms\Domain\Entity\Produto\Volume as VolumeEntity,
    Wms\Util\CodigoBarras,
    Core\Util\Produto,
    Wms\Util\Endereco as EnderecoUtil;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Util\Coletor;

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
        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();

        extract($values);

        $volumeEntity = (isset($id) && is_numeric($id)) ? $this->find($id) : new VolumeEntity();

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
        if (isset($ativarDesativar) && !empty($ativarDesativar)) {
            $volumeEntity->setDataInativacao(new \DateTime());
            $volumeEntity->setUsuarioInativacao($idUsuario);
        } else {
            $volumeEntity->setDataInativacao(null);
            $volumeEntity->setUsuarioInativacao(null);
        }

        $volumeEntity->setEndereco(null);

        //valida o endereco informado
        if (!empty($endereco)) {
            $endereco = EnderecoUtil::separar($endereco);
            $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
            /** @var Endereco $enderecoEntity */
            $enderecoEntity = $enderecoRepo->findOneBy($endereco);

            if (!$enderecoEntity) {
                throw new \Exception('Não existe o Endereço informado no volume ' . $descricao);
            }

            if ($enderecoEntity->liberadoPraSerPicking()) {
                $volumeEntity->setEndereco($enderecoEntity);
            }
        }

        if (!empty($idNormaPaletizacao)) {
            /** @var NormaPaletizacao $normaPaletizacaoEntity */
            $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $volumeEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($volumeEntity);
        
        // gera o codigo de barras com base no id do volume. Ex: 12340102 / 12340202
        if ($CBInterno == 'S') {
            $codigoBarras = "20" . $volumeEntity->getId();
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
            ->where("v.codProduto = '$codProduto'")
            ->andWhere("v.grade = '$grade'")
            ->distinct(true);
        $normasId = $dql->getQuery()->getResult();

        $result = array();
        foreach ($normasId as $normaId){
            $result[] = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id'=>$normaId));
        }

        return $result;
    }

    public function getVolumesByNorma($codNormaPaletizacao, $codProduto, $grade, $codDepositoEndereco = null) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("v")
            ->from("wms:Produto\Volume",'v')
            ->innerJoin("v.normaPaletizacao",'np')
            ->where("v.codProduto = '$codProduto'")
            ->andWhere("v.grade = '$grade'")
            ->andWhere("np.id = '$codNormaPaletizacao'");
        if($codDepositoEndereco != null){
            $dql->andWhere("v.endereco = '$codDepositoEndereco'");
        }
        $result = $dql->getQuery()->getResult();
        return $result;
    }

    public function getProdutosVolumesByNorma($codNormaPaletizacao, $codProduto, $grade, $codDepositoEndereco = null, $returnEntity = false) {

        $select = (!$returnEntity) ? "v.id as COD_PRODUTO_VOLUME" : "v";

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select($select)
            ->from("wms:Produto\Volume",'v')
            ->innerJoin("v.normaPaletizacao",'np')
            ->where("v.codProduto = '$codProduto'")
            ->andWhere("v.grade = '$grade'")
            ->andWhere("np.id = '$codNormaPaletizacao'")
            ->andWhere('v.dataInativacao IS NULL');
        if($codDepositoEndereco != null){
            $dql->andWhere("v.endereco = '$codDepositoEndereco'");
        }

        $result = $dql->getQuery()->getResult();

        if ($returnEntity) return $result;

        $arrResult = array();
        foreach ($result as $r) {
            $arrResult[] = array('COD_PRODUTO_VOLUME' => $r['COD_PRODUTO_VOLUME'],
                'COD_PRODUTO_EMBALAGEM' => null);
        }

        return $arrResult;
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

    public function setPickingVolume($codBarras, $enderecoEn, $capacidadePicking)
    {
        /** @var VolumeRepository $embalagemRepo */
        $volumeRepo = $this->_em->getRepository('wms:Produto\Volume');
        $volumeEn = $volumeRepo->findOneBy(array('codigoBarras' => $codBarras));

        if (empty($volumeEn)) {
            throw new \Exception('Volume não encontrado');
        }

        $volumesEntities = $volumeRepo->findBy(array('codProduto' => $volumeEn->getCodProduto(), 'grade' => $volumeEn->getGrade()));

        /** @var Volume $volumesEntity */
        foreach ($volumesEntities as $volumesEntity) {
            $volumesEntity->setEndereco($enderecoEn);
            $volumesEntity->setCapacidadePicking($capacidadePicking);
            $this->_em->persist($volumesEntity);
        }

        $this->_em->flush();
    }

    public function setNormaPaletizacaoVolume($codBarras, $numLastro, $numCamadas, $unitizador)
    {
        /** @var VolumeRepository $embalagemRepo */
        $volumeRepo = $this->_em->getRepository('wms:Produto\Volume');
        $unitizadorRepo = $this->_em->getRepository('wms:Armazenagem\Unitizador');
        $codBarras = Coletor::adequaCodigoBarras($codBarras);
        $volumeEn = $volumeRepo->findOneBy(array('codigoBarras' => $codBarras));

        if (empty($volumeEn)) {
            throw new \Exception('Volume não encontrado');
        }

        $normaPaletizacaoEn = $volumeEn->getNormaPaletizacao();
        $unitizadorEn = $unitizadorRepo->find($unitizador);
        if ($normaPaletizacaoEn) {
            $normaPaletizacaoEn->setNumLastro($numLastro);
            $normaPaletizacaoEn->setNumCamadas($numCamadas);
            $normaPaletizacaoEn->setNumNorma($numLastro * $numCamadas);
            $normaPaletizacaoEn->setUnitizador($unitizadorEn);
            $this->_em->persist($normaPaletizacaoEn);
        }
        $this->_em->flush();
    }

    public function checkEstoqueReservaById($id)
    {
        $dql = $this->_em->createQueryBuilder()
            ->select('NVL(e.id, rep.id)')
            ->from('wms:Produto\Volume', 'pv')
            ->leftJoin('wms:Enderecamento\Estoque', 'e', 'WITH', 'pv.id = e.produtoVolume')
            ->leftJoin('wms:Ressuprimento\ReservaEstoqueProduto', 'rep', 'WITH','pv.id = rep.codProdutoVolume')
            ->leftJoin("rep.reservaEstoque", 're')
            ->where("pv.id = :id and re.atendida = 'N'")
            ->setParameter('id',$id);

        $result = $dql->getQuery()->getResult();
        $msg = null;
        $status = 'ok';
        foreach ($result as $item) {
            foreach ($item as $id) {
                if (!empty($id)) {
                    $status = 'error';
                    $msg = 'Não é permitido excluir volumes com estoque ou reserva de estoque!';
                }
                if ($status === 'error') break;
            }
            if ($status === 'error') break;
        }
        return array($status, $msg);
    }

    public function getVolumeByCodigo($codigo) {
        $dql = $this->_em->createQueryBuilder()
            ->select('pv.id')
            ->from('wms:Produto\Volume', 'pv')
            ->innerJoin('wms:Produto', 'p', 'WITH', 'p.id = pv.codProduto AND p.grade = pv.grade')
            ->where("pv.codProduto = '$codigo'")
            ->orWhere("pv.codigoBarras = '$codigo'");

        return $dql->getQuery()->getResult();
    }

    /**
     * @param $idProduto
     * @param $grade
     * @return array
     * @throws \Exception
     */
    public function getCapacidadeAndPickingVol($idProduto, $grade) {

        $sql = "SELECT DISTINCT COD_DEPOSITO_ENDERECO, CAPACIDADE_PICKING 
                FROM PRODUTO_VOLUME WHERE COD_PRODUTO = '$idProduto' AND DSC_GRADE = '$grade' AND DTH_INATIVACAO IS NULL";

        $result = $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result))
            throw new \Exception("O produto $idProduto grade $grade não tem volume ativo");

        $idPicking = $result[0]['COD_DEPOSITO_ENDERECO'];
        $capacidade = $result[0]['CAPACIDADE_PICKING'];

        if (empty($idPicking))
            throw new \Exception("O produto $idProduto grade $grade não tem picking definido");

        if (empty($capacidade))
            throw new \Exception("O produto $idProduto grade $grade não tem capacidade de picking definida");

        $pickingEn = $this->_em->find("wms:Deposito\Endereco", $idPicking);

        return [$pickingEn, $capacidade];
    }
}
