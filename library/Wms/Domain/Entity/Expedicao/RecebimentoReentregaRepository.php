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

        $recebimentoReentregaEn = new RecebimentoReentrega();
        $recebimentoReentregaEn->setDataCriacao($data);
        $recebimentoReentregaEn->setStatus(NotaFiscalSaida::NOTA_FISCAL_EMITIDA);
        $recebimentoReentregaEn->setObservacao('abc');
        $recebimentoReentregaEn->setUsuario($idPessoa);
//var_dump($recebimentoReentregaEn); exit;
        $this->_em->persist($recebimentoReentregaEn);
        $this->_em->flush();

        return $recebimentoReentregaEn->getId();
    }
}