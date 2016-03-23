<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class ReabastecimentoManualRepository extends EntityRepository
{

    public function getProdutos($codOs)
    {
        if (!$codOs) {
            throw new \Exception('Ordem de serviço não informada');
        }

        $em = $this->getEntityManager();
        $dql = $em->createQueryBuilder()
            ->select("p.id as codProduto, p.grade, p.descricao as produto, 0 as codVolume, 'PRODUTO UNITARIO' as descricao, e.descricao as endereco, rm.dataColeta, rm.qtd")
            ->distinct(true)
            ->from("wms:Enderecamento\ReabastecimentoManual", "rm")
            ->innerJoin('rm.os', 'o')
            ->innerJoin("rm.produto", "p")
            ->innerJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.codProduto = p.id')
            ->leftJoin("pe.endereco", "e")
            ->where("o.id = $codOs");

        return  $dql->getQuery()->getArrayResult();
    }

}
