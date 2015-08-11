<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao;

class RecebimentoReentregaNotaRepository extends EntityRepository
{

    public function save($recebimentoReentregaEn, $params)
    {
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
        $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");

        foreach ($params['mass-id'] as $notaFiscal) {
            $notaFiscalEn = $notaFiscalSaidaRepo->findOneBy(array('id' => $notaFiscal));

            $recebimentoReentregaNotaEn = new RecebimentoReentregaNota();
            $recebimentoReentregaNotaEn->setRecebimentoReentrega($recebimentoReentregaEn);
            $recebimentoReentregaNotaEn->setNotaFiscalSaida($notaFiscalEn);
            $this->_em->persist($recebimentoReentregaNotaEn);
        }

        $this->_em->flush();

        return true;
    }

    public function getRecebimentoReentregaByNota()
    {
        $status = NotaFiscalSaida::NOTA_FISCAL_EMITIDA;
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('nfs.numeroNf')
            ->from('wms:Expedicao\RecebimentoReentregaNota', 'rrn')
            ->innerJoin('rrn.recebimentoReentrega', 'rr')
            ->innerJoin('rrn.notaFiscalSaida', 'nfs')
            ->where("rr.status = $status")
            ->groupBy('nfs.numeroNf');

        return $sql->getQuery()->getResult();
    }
}