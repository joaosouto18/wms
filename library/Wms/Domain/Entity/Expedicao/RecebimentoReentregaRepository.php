<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\NotaFiscal;
use Wms\Domain\Entity\Pessoa\Juridica;
use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Domain\Entity\Sistema\ParametroRepository;
use Wms\Service\ExpedicaoService;

class RecebimentoReentregaRepository extends EntityRepository
{
    public function verificaNotaExpedida ($data) {
        $notas = implode(',', $data['mass-id']);
        $notaRecebida = NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA;
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('nfs.id')
            ->from('wms:Expedicao\NotaFiscalSaida', 'nfs')
            ->where("nfs.id IN ($notas) AND nfs.status = $notaRecebida");

        $result =$sql->getQuery()->getArrayResult();
        if (count($result) == 0) {
            return true;
        } else {
            return false;
        }
    }

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
            $falha = true;
            $this->getEntityManager()->beginTransaction();

            /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
            $siglaRepo = $this->_em->getRepository("wms:Util\Sigla");
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
            $notaFiscalSaidaRepo = $this->_em->getRepository('wms:Expedicao\NotaFiscalSaida');
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->_em->getRepository('wms:Expedicao\RecebimentoReentrega');
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
            $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
            $recebimentoReentregaNotaRepo = $this->_em->getRepository('wms:Expedicao\RecebimentoReentregaNota');
            /** @var ParametroRepository $sisParamRepo */
            $sisParamRepo = $this->_em->getRepository('wms:Sistema\Parametro');

            $expedicaoService = new ExpedicaoService($this->_em);

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
                $falha = false;
                throw new \Exception($mensagem);
            }

            //alterar o status do recebimento para finalizado
            $statusRecebimentoFinalizadoEn = $siglaRepo->findOneBy(array('id' => RecebimentoReentrega::RECEBIMENTO_CONCLUIDO));
            $statusNfFinalizadaEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA));

            $recebimentoReentregaEn->setStatus($statusRecebimentoFinalizadoEn);
            $this->_em->persist($recebimentoReentregaEn);

            //GRAVO O ANDAMENTO DE CADA NOTA FALANDO QUE FOI FINALIZADO O RECEBIMENTO
            $notas = $recebimentoReentregaNotaRepo->findBy(array('recebimentoReentrega' => $recebimentoReentregaEn->getId()));
            /** @var RecebimentoReentregaNota $nota */
            foreach ($notas as $nota){
                $nfEntity = $nota->getNotaFiscalSaida();
                $andamentoNFRepo->save($nfEntity, \Wms\Domain\Entity\Expedicao\RecebimentoReentrega::RECEBIMENTO_CONCLUIDO,false, null,null, $recebimentoReentregaEn);
                $nfEntity->setStatus($statusNfFinalizadaEn);
                $this->getEntityManager()->persist($nfEntity);
                /** @var Parametro $resultParam */
                $resultParam = $sisParamRepo->findOneBy(array('constante' => 'IND_REENTREGA_RECEB_TO_EXP'));
                if (empty($resultParam) || (!empty($resultParam) && $resultParam->getValor() != 'N')) {
                    $expedicaoService->createCargaReentrega($nfEntity);
                }
            }

            $this->_em->flush();
            $this->_em->clear();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            if ($falha == true) $this->getEntityManager()->rollback();
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
            ->innerJoin('wms:Expedicao\NotaFiscalSaidaProduto', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
            ->where("rr.id = $recebimentoReentrega AND nfsp.codProduto = '$produtoId' AND nfsp.grade = '$grade'");

        return $sql->getQuery()->getResult();
    }

    public function buscar($data)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('rr.id, rr.dataCriacao, s.sigla status, s.id idStatus')
            ->from('wms:Expedicao\RecebimentoReentrega', 'rr')
            ->innerJoin('rr.status', 's')
            ->leftJoin('wms:Expedicao\NotaFiscalSaidaAndamento', 'nfsa', 'WITH', 'nfsa.recebimentoReentrega = rr.id')
            ->leftJoin('wms:Expedicao\NotaFiscalSaida', 'nfs', 'WITH', 'nfs.id = nfsa.NotaFiscalSaida')
            ->groupBy('rr.id, rr.dataCriacao, s.sigla, s.id')
            ->orderBy('rr.id');

        if (isset($data['notaFiscal']) && !empty($data['notaFiscal'])) {
            $sql->andWhere("nfs.numeroNf = $data[notaFiscal]");
        }

        if (isset($dataInicial1) && (!empty($dataInicial1)) && (!empty($dataInicial2))) {
            $dataInicial1 = str_replace("/", "-", $dataInicial1);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $dataInicial2);
            $dataI2 = new \DateTime($dataInicial2);

            $sql->andWhere("((TRUNC(rr.dataCriacao) >= ?1 AND TRUNC(rr.dataCriacao) <= ?2) OR rr.dataCriacao IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2);
        }

        return $sql->getQuery()->getResult();
    }

}