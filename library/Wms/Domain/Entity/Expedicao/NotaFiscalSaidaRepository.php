<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class NotaFiscalSaidaRepository extends EntityRepository
{

    public function save()
    {

    }

    public function getNotaFiscalOuCarga($data)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('nfs.numeroNf', 'c.id', 'nfs.serieNf', 'nfs.id')
            ->from('wms:Expedicao\NotaFiscalSaida', 'nfs')
            ->innerJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
            ->innerJoin('nfsp.pedido', 'p')
            ->innerJoin('p.carga', 'c');
        if (isset($data['notaFiscal']) && !empty($data['notaFiscal'])) {
            $sql->andWhere("nfs.numeroNf = $data[notaFiscal]");
        }
        if (isset($data['carga']) && !empty($data['carga'])) {
            $sql->andWhere("c.id = $data[carga]");
        }
        $sql->groupBy('nfs.numeroNf', 'c.id', 'nfs.serieNf', 'nfs.id');
echo $sql->getQuery()->getSQL(); exit;
        return $sql->getQuery()->getResult();
    }

}

