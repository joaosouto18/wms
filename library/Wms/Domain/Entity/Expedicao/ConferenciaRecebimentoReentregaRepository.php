<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Expedicao;

class ConferenciaRecebimentoReentregaRepository extends EntityRepository
{

    public function save($data)
    {
        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $produtoVolumeRepo */
        $produtoVolumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
        $produtoVolumeEn = $produtoVolumeRepo->findOneBy(array('codigoBarras' => $data['codBarras']));

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $produtoEmbalagemRepo */
        $produtoEmbalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $produtoEmbalagemEn = $produtoEmbalagemRepo->findOneBy(array('codigoBarras' => $data['codBarras']));

        if (isset($produtoVolumeEn)) {
            $produtoId = $produtoVolumeEn->getProduto();
            $grade = $produtoVolumeEn->getGrade();
        } else if (isset($produtoEmbalagemEn)) {
            $produtoId = $produtoEmbalagemEn->getProduto();
            $grade = $produtoEmbalagemEn->getGrade();
        } else {
            return false;
        }

        /** @var \Wms\Domain\Entity\Produto $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $produtoEn = $produtoRepo->findOneBy(array('id' => $produtoId, 'grade' => $grade));

        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
        $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaida');
        $notaFiscalSaidaEn = $notaFiscalSaidaRepo->findOneBy(array('numeroNf' => $data['numeroNota']));

        if (isset($notaFiscalSaidaEn)) {
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
            $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentregaNota');
            $recebimentoReentregaNotaEn = $recebimentoReentregaNotaRepo->findBy(array('notaFiscalSaida' => $notaFiscalSaidaEn->getId()), array('id' => 'DESC'));
        } else {
            return false;
        }

        if (count($recebimentoReentregaNotaEn) > 0) {
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
            $recebimentoReentregaEn = $recebimentoReentregaRepo->findOneBy(array('id' => $recebimentoReentregaNotaEn[0]->getRecebimentoReentrega()));
        } else {
            return false;
        }

        if (isset($recebimentoReentregaEn)) {
            /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
            $ordemServicoRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
            $ordemServicoEn = $ordemServicoRepo->findOneBy(array('recebimentoReentrega' => $recebimentoReentregaEn));
        } else {
            return false;
        }

        $conferenciaRecebimentoReentrega = $this->findOneBy(array('codProduto' => $produtoEn->getId(), 'grade' => $grade, 'recebimentoReentrega' => $recebimentoReentregaEn->getId()));

        if (isset($conferenciaRecebimentoReentrega)) {
            $numeroConferencia = $conferenciaRecebimentoReentrega->getNumeroConferencia() + 1;
        } else {
            $numeroConferencia = 1;
        }

        $conferenciaRecebimentoReentregaEn = new ConferenciaRecebimentoReentrega();
        $conferenciaRecebimentoReentregaEn->setProdutoVolume($produtoVolumeEn);
        $conferenciaRecebimentoReentregaEn->setProdutoEmbalagem($produtoEmbalagemEn);
        $conferenciaRecebimentoReentregaEn->setProduto($produtoEn);
        $conferenciaRecebimentoReentregaEn->setGrade($grade);
        $conferenciaRecebimentoReentregaEn->setQuantidadeConferida($data['qtd']);
        $conferenciaRecebimentoReentregaEn->setRecebimentoReentrega($recebimentoReentregaEn);
        $conferenciaRecebimentoReentregaEn->setNumeroConferencia($numeroConferencia);
        $conferenciaRecebimentoReentregaEn->setQtdEmbalagemConferida(1);
        $conferenciaRecebimentoReentregaEn->setOrdemServico($ordemServicoEn);

        $conferenciaRecebimentoReentregaEn->setDataConferencia(new \DateTime);

        $this->_em->persist($conferenciaRecebimentoReentregaEn);
        $this->_em->flush();

        return true;
    }


}

