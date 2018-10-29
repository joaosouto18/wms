<?php

namespace Wms\Domain\Entity\Ressuprimento;
use Wms\Domain\Entity\Enderecamento\Palete;

/**
 * @Table(name="RESERVA_ESTOQUE_ENDERECAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamentoRepository")
 */
class ReservaEstoqueEnderecamento
{

    /**
     * @var ReservaEstoque
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoque")
     * @JoinColumn(name="COD_RESERVA_ESTOQUE", referencedColumnName="COD_RESERVA_ESTOQUE")
     */
    protected $reservaEstoque;

    /**
     * @var Palete
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Palete")
     * @JoinColumn(name="UMA", referencedColumnName="UMA")
     */
    protected $palete;

    /**
     * @return ReservaEstoque
     */
    public function getReservaEstoque()
    {
        return $this->reservaEstoque;
    }

    /**
     * @param ReservaEstoque $reservaEstoque
     */
    public function setReservaEstoque($reservaEstoque)
    {
        $this->reservaEstoque = $reservaEstoque;
    }

    /**
     * @return Palete
     */
    public function getPalete()
    {
        return $this->palete;
    }

    /**
     * @param Palete $palete
     */
    public function setPalete($palete)
    {
        $this->palete = $palete;
    }

}
