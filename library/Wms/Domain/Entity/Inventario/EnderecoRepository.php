<?php

namespace Wms\Domain\Entity\Inventario;

use Doctrine\ORM\EntityRepository;


class EnderecoRepository extends EntityRepository
{

    /**
     * @return Endereco
     * @throws \Exception
     */
    public function save($params)
    {

        if (empty($params['codInventario'])) {
            throw new \Exception("codInventario não pode ser vazio");
        }

        if (empty($params['codDepositoEndereco'])) {
            throw new \Exception("codDepositoEndereco não pode ser vazio");
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {

            $enInvEndereco = new Endereco();

            $inventarioEntity = $em->getReference('wms:Inventario',$params['codInventario']);
            $enInvEndereco->setInventario($inventarioEntity);
            $enderecoEntity = $em->getReference('wms:Deposito\Endereco',$params['codDepositoEndereco']);
            $enInvEndereco->setDepositoEndereco($enderecoEntity);

            $em->persist($enInvEndereco);
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $enInvEndereco;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getByInventario($params)
    {
        $idInventario   = isset($params['idInventario']) ? $params['idInventario'] : null;
        $numContagem    = isset($params['numContagem']) ? $params['numContagem'] : null;
        $divergencia    = isset($params['divergencia']) ? $params['divergencia'] : null;
        $rua            = isset($params['rua']) ? $params['rua'] : null;

        $andDivergencia = null;
        if ($divergencia != null) {
            $andDivergencia = " AND IE.DIVERGENCIA = 1 ";
        } else {
            $andDivergencia = " AND IE.DIVERGENCIA IS NULL ";
        }

        $andRua = null;
        if ($rua != null) {
            $andRua = " AND DE.NUM_RUA = ".$rua." ";
        }

        $andContagem = null;
        if ($numContagem != null) {
            $andContagem = " AND NVL(MAXCONT.ULTCONT,0) = ".$numContagem." AND IE.INVENTARIADO IS NULL ";
        }

        $sql = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO, NVL(MAXCONT.ULTCONT,0) as ULTIMACONTAGEM, IE.DIVERGENCIA, IE.COD_INVENTARIO_ENDERECO AS codInvEndereco,
          CASE WHEN IE.DIVERGENCIA = 1 THEN 'DIVERGENCIA' WHEN IE.INVENTARIADO = 1 THEN 'INVENTARIADO' ELSE 'PENDENTE' END SITUACAO
          FROM INVENTARIO_ENDERECO IE
          LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = IE.COD_DEPOSITO_ENDERECO
          LEFT JOIN (SELECT MAX(NUM_CONTAGEM) as ULTCONT, COD_INVENTARIO_ENDERECO
                       FROM INVENTARIO_CONTAGEM_ENDERECO
                      GROUP BY COD_INVENTARIO_ENDERECO) MAXCONT
            ON MAXCONT.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
         WHERE IE.COD_INVENTARIO = ".$idInventario."
         $andContagem
         $andDivergencia
         $andRua
         ORDER BY DE.DSC_DEPOSITO_ENDERECO
         ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRuasInventario($idInventario)
    {
        if (empty($idInventario)) {
            throw new \Exception('idInventario não pode ser vazio');
        }

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("de.rua")
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin('ie.depositoEndereco', 'de')
            ->andWhere("ie.inventario = $idInventario")
            ->orderBy('de.rua')
            ->distinct(true);

        return $query->getQuery()->getResult();
    }

    public function getUltimaContagem($enderecoEntity)
    {
        $idInvEnd = $enderecoEntity->getId();

        $query = $this->_em->createQueryBuilder()
            ->select('max(ce.id) id, ce.codProduto, ce.grade, ce.codProdutoEmbalagem, ce.codProdutoVolume')
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin("wms:Inventario\ContagemEndereco", 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->andWhere("ie.id = $idInvEnd")
            ->groupBy('ce.codProduto, ce.grade, ce.codProdutoEmbalagem, ce.codProdutoVolume');

        $results = $query->getQuery()->getResult();
        /** @var \Wms\Domain\Entity\Inventario\ContagemEndereco $invContagemEndRepo */
        $invContagemEndRepo = $this->_em->getRepository("wms:Inventario\ContagemEndereco");
        $produtosContagem = array();
        foreach($results as $result) {
            $produtosContagem[] = $invContagemEndRepo->find($result['id']);
        }
        return $produtosContagem;
    }

    public function getComContagem($idInventario)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('ie')
            ->from("wms:Inventario\Endereco","ie")
            ->andWhere("ie.inventariado = 1")
            ->andWhere("ie.atualizaEstoque = 1")
            ->andWhere("ie.inventario = $idInventario");

        return $query->getQuery()->getResult();
    }

}