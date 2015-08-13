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
        $notaFiscalEmitida = NotaFiscalSaida::NOTA_FISCAL_EMITIDA;
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('rr.id')
            ->from('wms:Expedicao\RecebimentoReentrega', 'rr')
            ->innerJoin('wms:Expedicao\RecebimentoReentregaNota', 'rrn', 'WITH', 'rr.id = rrn.recebimentoReentrega')
            ->where("rrn.notaFiscalSaida IN ($notas) AND rr.status = $notaFiscalEmitida");
        return $sql->getQuery()->getResult();
    }

    public function save()
    {
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

        /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        $siglaEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::NOTA_FISCAL_EMITIDA));

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
        $this->getEntityManager()->beginTransaction();

        /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
        $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaida');
        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
        $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
        $recebimentoReentregaEn = $recebimentoReentregaRepo->findOneBy(array('id' => $data['id']));

        $getQtdProdutosByNota = $notaFiscalSaidaRepo->getQtdProdutoByNota($data);

        foreach ($getQtdProdutosByNota as $produto) {
            if ($produto['QTD_TOTAL'] != 0) {
                $recebimentoReentregaEn->setNumeroConferencia($recebimentoReentregaEn->getNumeroConferencia() + 1);
                $this->_em->persist($recebimentoReentregaEn);
                $this->_em->flush();
                $this->_em->clear();
                $this->getEntityManager()->commit();
                $mensagem = utf8_encode('Existem produtos com conferência errada!');
                throw new \Exception($mensagem);
            }
        }

        try {
            //alterar o status do recebimento para finalizado
            $siglaRecebimentoEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::FINALIZADO));
            $recebimentoReentregaEn->setStatus($siglaRecebimentoEn);
            $this->_em->persist($recebimentoReentregaEn);

            //alterar o status da nota fiscal para recebida
            $siglaNotaEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::RECEBIDA));
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
            $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentregaNota');
            $recebimentoReentregaNotaEn = $recebimentoReentregaNotaRepo->findBy(array('recebimentoReentrega' => $recebimentoReentregaEn->getId()));
            $notaFiscalSaidaEn = $notaFiscalSaidaRepo->findOneBy(array('id' => $recebimentoReentregaNotaEn->getNotaFiscalSaida()));
            $notaFiscalSaidaEn->setStatus($siglaNotaEn);
            $this->_em->persist($notaFiscalSaidaEn);

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