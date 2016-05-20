<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoProdutoRepository extends EntityRepository
{
    public function getMapaProdutoByProdutoAndMapa($idMapa, $idProduto, $grade)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(msp.qtdSeparar) qtdSeparar, msp.codPedidoProduto, IDENTITY(msp.codDepositoEndereco) codDepositoEndereco')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->where("ms.id = $idMapa AND msp.codProduto = '$idProduto' AND msp.dscGrade = '$grade'")
            ->groupBy('msp.codPedidoProduto, msp.codDepositoEndereco');

        return $sql->getQuery()->getResult();
    }

    public function getMapaProdutoByMapa($idMapa)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('ms.id, msp.codProduto, msp.dscGrade')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->where("ms.id = $idMapa")
            ->groupBy('ms.id, msp.codProduto, msp.dscGrade');

        return $sql->getQuery()->getResult();
    }


}