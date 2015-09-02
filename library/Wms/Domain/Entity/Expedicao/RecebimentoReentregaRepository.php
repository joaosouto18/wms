<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\NotaFiscal;

class RecebimentoReentregaRepository extends EntityRepository
{
    public function verificaRecebimento($data)
    {
        $notas = implode(',', $data['mass-id']);
        $recebimentoIniciado = RecebimentoReentrega::RECEBIMENTO_INICIADO;
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('rr.id')
            ->from('wms:Expedicao\RecebimentoReentrega', 'rr')
            ->innerJoin('wms:Expedicao\RecebimentoReentregaNota', 'rrn', 'WITH', 'rr.id = rrn.recebimentoReentrega')
            ->where("rrn.notaFiscalSaida IN ($notas) AND rr.status = $recebimentoIniciado");
        return $sql->getQuery()->getResult();
    }

    public function save()
    {
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

        /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        $siglaEn = $siglaRepo->findOneBy(array('id' => RecebimentoReentrega::RECEBIMENTO_INICIADO));

        /** @var \Wms\Domain\Entity\Usuario $usuarioRepo */
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioEn = $usuarioRepo->findOneBy(array('pessoa' => $idPessoa));

        $recebimentoReentregaEn = new RecebimentoReentrega();
        $recebimentoReentregaEn->setDataCriacao(new \DateTime);
        $recebimentoReentregaEn->setStatus($siglaEn);
        $recebimentoReentregaEn->setObservacao("");
        $recebimentoReentregaEn->setUsuario($usuarioEn);

        $this->_em->persist($recebimentoReentregaEn);
        $this->_em->flush();

        return $recebimentoReentregaEn;
    }

    public function finalizarConferencia($data)
    {
        try {

            $this->getEntityManager()->beginTransaction();

            /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
            $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
            $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaida');
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
            $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
            $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentregaNota');


            $recebimentoReentregaEn = $recebimentoReentregaRepo->findOneBy(array('id' => $data['id']));

            //VERIFICA SE TEVE ALGUMA DIVERGENCIA NO RECEBIMENTO
            $getQtdProdutosDivergentes = $notaFiscalSaidaRepo->getQtdProdutoDivergentesByNota($data);
            if (count($getQtdProdutosDivergentes) > 0) {
                $recebimentoReentregaEn->setNumeroConferencia($recebimentoReentregaEn->getNumeroConferencia() + 1);
                $this->_em->persist($recebimentoReentregaEn);
                $this->_em->flush();
                $this->_em->clear();
                $this->getEntityManager()->commit();
                $mensagem = utf8_encode('Existem produtos com divergencia na conferencia');
                throw new \Exception($mensagem);
            }

            //alterar o status do recebimento para finalizado
            $statusRecebimentoFinalizadoEn = $siglaRepo->findOneBy(array('id' => RecebimentoReentrega::RECEBIMENTO_CONCLUIDO));
            $statusNfFinalizadaEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA));

            $recebimentoReentregaEn->setStatus($statusRecebimentoFinalizadoEn);
            $this->_em->persist($recebimentoReentregaEn);

            //GRAVO O ANDAMENTO DE CADA NOTA FALANDO QUE FOI FINALIZADO O RECEBIMENTO
            $notas = $recebimentoReentregaNotaRepo->findBy(array('recebimentoReentrega' => $recebimentoReentregaEn->getId()));
            foreach ($notas as $nota){
                $nfEntity = $nota->getNotaFiscalSaida();
                $andamentoNFRepo->save($nfEntity, \Wms\Domain\Entity\Expedicao\RecebimentoReentrega::RECEBIMENTO_CANCELADO);
                $nfEntity->setStatus($statusNfFinalizadaEn);
                $this->getEntityManager()->persist($nfEntity);
            }

            $this->_em->flush();
            $this->_em->clear();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function getProdutosByRecebimento($recebimentoReentrega, $produto, $grade)
    {
        $produtoId = $produto->getId();
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('rr.id, nfsp.codProduto, nfsp.grade')
            ->from('wms:Expedicao\RecebimentoReentrega', 'rr')
            ->innerJoin('wms:Expedicao\RecebimentoReentregaNota', 'rrn', 'WITH', 'rr.id = rrn.recebimentoReentrega')
            ->innerJoin('rrn.notaFiscalSaida', 'nfs')
            ->innerJoin('wms:Expedicao\NotafiscalSaidaProduto', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
            ->where("rr.id = $recebimentoReentrega AND nfsp.codProduto = '$produtoId' AND nfsp.grade = '$grade'");

        return $sql->getQuery()->getResult();
    }

}