<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class NotaFiscalSaidaAndamentoRepository extends EntityRepository
{
    public function save($notaFiscalEn, $idStatus, $integracao = false, $expedicaoEn = null, $observacao = "" )
    {
        $usuarioEn = null;
        if ($integracao == false) {
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuarioEn = $this->getEntityManager()->getReference('wms:Usuario', (int) $idUsuario);
        }

        $statusEn = $this->getEntityManager()->getReference('wms:Util\Sigla', (int) $idStatus);

        $andamentoNotaFiscalSaidaEn = new NotaFiscalSaidaAndamento();
            $andamentoNotaFiscalSaidaEn->setNotaFiscalSaida($notaFiscalEn);
            $andamentoNotaFiscalSaidaEn->setExpedicao($expedicaoEn);
            $andamentoNotaFiscalSaidaEn->setUsuario($usuarioEn);
            $andamentoNotaFiscalSaidaEn->setStatus($statusEn);
            $andamentoNotaFiscalSaidaEn->setData(new \DateTime);
            $andamentoNotaFiscalSaidaEn->setObservacao($observacao);
        $this->getEntityManager()->persist($andamentoNotaFiscalSaidaEn);
    }

}