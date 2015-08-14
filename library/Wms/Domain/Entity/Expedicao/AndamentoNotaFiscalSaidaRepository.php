<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class AndamentoRepository extends EntityRepository
{
    public function save()
    {
        $andamentoNotaFiscalSaidaEn = new AndamentoNotaFiscalSaida();
        $andamentoNotaFiscalSaidaEn->setNotaFiscalSaida();
        $andamentoNotaFiscalSaidaEn->setExpedicao();
        $andamentoNotaFiscalSaidaEn->setUsuario();
        $andamentoNotaFiscalSaidaEn->setStatus();
        $andamentoNotaFiscalSaidaEn->setData(new \DateTime);
        $andamentoNotaFiscalSaidaEn->setObservacao();

        $this->_em->persist($andamentoNotaFiscalSaidaEn);
        $this->_em->flush();
    }

}