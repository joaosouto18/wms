<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\NotaFiscal;

class RecebimentoReentregaRepository extends EntityRepository
{
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
        /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        $siglaNotaEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::RECEBIDA));

        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
        $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaida');

        /** @var \Wms\Domain\Entity\Expedicao\ConferenciaRecebimentoReentregaRepository $confRecebReentregaRepo */
        $confRecebReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ConferenciaRecebimentoReentrega');

        $getQtdProdutosByNota = $notaFiscalSaidaRepo->getQtdProdutoByNota($data);

        foreach ($getQtdProdutosByNota as $qtdProduto) {
            $confRecebReentregaEn = $confRecebReentregaRepo->findBy(array('codProduto' => $qtdProduto['codProduto'], 'grade' => $qtdProduto['grade']), array('numeroConferencia' => 'DESC'));
            if ($qtdProduto['qtdProduto'] != $confRecebReentregaEn[0]->getQuantidadeConferida()) {
                return false;
            }
        }

        $notaFiscalSaidaEn = $notaFiscalSaidaRepo->findOneBy(array('numeroNf' => $data['id']));
        $notaFiscalSaidaEn->setStatus($siglaNotaEn);
        $this->_em->persist($notaFiscalSaidaEn);

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
        $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentregaNota');
        $recebimentoReentregaNotaEn = $recebimentoReentregaNotaRepo->findBy(array('notaFiscalSaida' => $notaFiscalSaidaEn->getId()), array('id' => 'DESC'));

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
        $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
        $recebimentoReentregaEn = $recebimentoReentregaRepo->findOneBy(array('id' => $recebimentoReentregaNotaEn[0]->getRecebimentoReentrega()));

        $siglaRecebimentoEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::FINALIZADO));

        $recebimentoReentregaEn->setStatus($siglaRecebimentoEn);

        $this->_em->persist($recebimentoReentregaEn);
        $this->_em->flush();

        return true;
    }
}