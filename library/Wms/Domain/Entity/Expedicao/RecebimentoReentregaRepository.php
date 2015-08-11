<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao;

class RecebimentoReentregaRepository extends EntityRepository
{

    public function save()
    {
        $data = new \Zend_Date();
        $data = $data->toString('dd/MM/Y');
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

        /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        $siglaEn = $siglaRepo->findOneBy(array('id' => NotaFiscalSaida::NOTA_FISCAL_EMITIDA));

        /** @var \Wms\Domain\Entity\Usuario $usuarioRepo */
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioEn = $usuarioRepo->findOneBy(array('pessoa' => $idPessoa));

        $recebimentoReentregaEn = new RecebimentoReentrega();
        $recebimentoReentregaEn->setDataCriacao($data);
        $recebimentoReentregaEn->setStatus($siglaEn);
        $recebimentoReentregaEn->setObservacao("");
        $recebimentoReentregaEn->setUsuario($usuarioEn);

        $this->_em->persist($recebimentoReentregaEn);
        $this->_em->flush();

        return $recebimentoReentregaEn;
    }
}