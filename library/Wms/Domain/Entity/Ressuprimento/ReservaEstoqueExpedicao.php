<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="RESERVA_ESTOQUE_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicaoRepository")
 */
class ReservaEstoqueExpedicao
{

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoque")
     * @JoinColumn(name="COD_RESERVA_ESTOQUE", referencedColumnName="COD_RESERVA_ESTOQUE")
     */
    protected $reservaEstoque;

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    public function getExpedicao()
    {
        return $this->expedicao;
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
