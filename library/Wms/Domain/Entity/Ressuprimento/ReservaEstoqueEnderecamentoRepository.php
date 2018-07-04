<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;

class ReservaEstoqueEnderecamentoRepository extends EntityRepository
{

    public function removerReservaUMA ($paleteEn, $executeFlush = false) {
        /** @var ReservaEstoqueEnderecamento $reservaEnderecamento */
        $reservasEnderecamento = $this->findBy(['palete' => $paleteEn->getId()]);
        foreach ($reservasEnderecamento as $reservaEnderecamento) {
            $reserva = $reservaEnderecamento->getReservaEstoque();
            $produtosReserva = $reserva->getProdutos()->toArray();

            foreach ($produtosReserva as $produtoReserva) {
                $this->_em->remove($produtoReserva);
            }

            $this->_em->remove($reservaEnderecamento);
            $this->_em->remove($reserva);
        }

        if ($executeFlush) $this->_em->flush();
    }
}
