<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class AndamentoNotaFiscalSaidaRepository extends EntityRepository
{
    public function save($notaFiscalEn, $idStatus,$expedicaoEn = null, $observacao = "", $idUsuario = false)
    {
        $idUsuario = ($idUsuario) ? $idStatus : \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioEn = $this->getEntityManager()->getReference('wms:Usuario', (int) $idUsuario);

        $statusEn = $this->getEntityManager()->getReference('wms:Util\Sigla', (int) $idStatus);

        $andamentoNotaFiscalSaidaEn = new AndamentoNotaFiscalSaida();
            $andamentoNotaFiscalSaidaEn->setNotaFiscalSaida($notaFiscalEn);
            $andamentoNotaFiscalSaidaEn->setExpedicao($expedicaoEn);
            $andamentoNotaFiscalSaidaEn->setUsuario($usuarioEn);
            $andamentoNotaFiscalSaidaEn->setStatus($statusEn);
            $andamentoNotaFiscalSaidaEn->setData(new \DateTime);
            $andamentoNotaFiscalSaidaEn->setObservacao($observacao);
        $this->getEntityManager()->persist($andamentoNotaFiscalSaidaEn);
    }

}