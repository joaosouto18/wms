<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="RESERVA_ESTOQUE_ENDERECAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamentoRepository")
 */
class ReservaEstoqueEnderecamento
{

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoque")
     * @JoinColumn(name="COD_RESERVA_ESTOQUE", referencedColumnName="COD_RESERVA_ESTOQUE")
     */
    protected $reservaEstoque;

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Palete")
     * @JoinColumn(name="UMA", referencedColumnName="UMA")
     */
    protected $palete;

    public function setPalete($palete)
    {
        $this->palete = $palete;
    }

    public function getPalete()
    {
        return $this->palete;
    }

    public function setReservaEstoque($reservaEstoque)
    {
        $this->reservaEstoque = $reservaEstoque;
    }

    public function getReservaEstoque()
    {
        return $this->reservaEstoque;
    }

}
