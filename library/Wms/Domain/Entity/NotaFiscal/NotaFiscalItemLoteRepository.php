<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 08/05/2018
 * Time: 09:21
 */

namespace Wms\Domain\Entity\NotaFiscal;

use Doctrine\ORM\EntityRepository;

class NotaFiscalItemLoteRepository extends EntityRepository
{
    public function save($codLote, $setCodNotaFiscalItem, $quantidade){
        $NFlote = new NotaFiscalItemLote();
        $NFlote->setCodLote($codLote);
        $NFlote->setCodNotaFiscalItem($setCodNotaFiscalItem);
        $NFlote->setQuantidade($quantidade);
        $this->_em->persist($NFlote);
    }
}