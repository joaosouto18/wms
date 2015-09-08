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
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
        $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");

        $statusNfEmitida = $this->getEntityManager()->getRepository('wms:Util\Sigla')->findOneBy(array('id'=>NotaFiscalSaida::NOTA_FISCAL_EMITIDA));

        foreach ($params['mass-id'] as $notaFiscal) {
            $notaFiscalEn = $notaFiscalSaidaRepo->findOneBy(array('id' => $notaFiscal));

            if ($notaFiscalEn->getStatus()->getId() == NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA) {
                $notaFiscalEn->setStatus($statusNfEmitida);
                $this->getEntityManager()->persist($notaFiscalEn);
            }

            $recebimentoReentregaNotaEn = new RecebimentoReentregaNota();
            $recebimentoReentregaNotaEn->setRecebimentoReentrega($recebimentoReentregaEn);
            $recebimentoReentregaNotaEn->setNotaFiscalSaida($notaFiscalEn);
            $this->_em->persist($recebimentoReentregaNotaEn);

            $andamentoNFRepo->save($notaFiscalEn, RecebimentoReentrega::RECEBIMENTO_INICIADO);

        }

        $this->_em->flush();

        return true;
    }

    public function getRecebimentoReentregaByNota()
    {
        $status = RecebimentoReentrega::RECEBIMENTO_INICIADO;
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('nfs.numeroNf', 'rr.id recebimento')
            ->from('wms:Expedicao\RecebimentoReentregaNota', 'rrn')
            ->innerJoin('rrn.recebimentoReentrega', 'rr')
            ->innerJoin('rrn.notaFiscalSaida', 'nfs')
            ->where("rr.status = $status")
            ->groupBy('nfs.numeroNf', 'rr.id')
            ->orderBy('rr.id', 'DESC');

        return $sql->getQuery()->getResult();
    }
}