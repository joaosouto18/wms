<?php

namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class EmbalagemRepository extends EntityRepository
{
    public function getEmbalagemByRecebimento($recebimento, $codProduto, $grade, $notArray = false, $lote = null, $controlaData = false)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('re')
            ->from('wms:Recebimento\Embalagem', 're')
            ->innerJoin('re.embalagem', 'e')
            ->where("re.recebimento = $recebimento")
            ->andWhere("e.codProduto = '$codProduto'")
            ->andWhere("e.grade = '$grade'");
        if($lote != null) {
            $source->andWhere("re.lote = '$lote'");
        }
        if ($controlaData) {
            $source->andWhere("re.dataValidade IS NOT NULL");
        }

        if ($notArray)
            return $source->getQuery()->getResult();
        else
            return $source->getQuery()->getArrayResult();
    }

    public function getEmbalagensVolumesByRecebimento($codRecebimento,$codigoBarras)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('re')
            ->from('wms:Recebimento', 'r')
            ->leftJoin('wms:Recebimento\Embalagem', 're', 'WITH', 're.recebimento = r.id')
            ->leftJoin('wms:Recebimento\Volume', 'rv', 'WITH', 'rv.recebimento = r.id')
            ->leftJoin('re.embalagem', 'pe')
            ->leftJoin('rv.volume', 'pv')
            ->leftJoin('wms:Produto', 'p', 'WITH', '(pv.codProduto = p.id OR pe.codProduto = p.id) AND (pv.grade = p.grade OR pe.grade = p.grade)')
            ->where("r.id = $codRecebimento AND (pe.codigoBarras = $codigoBarras OR pv.codigoBarras = $codigoBarras)");

        return $source->getQuery()->getArrayResult();
    }

}
