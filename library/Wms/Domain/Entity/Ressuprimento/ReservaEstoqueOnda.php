<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="RESERVA_ESTOQUE_ONDA_RESSUP")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueOndaRepository")
 */
class ReservaEstoqueOnda
{

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs")
     * @JoinColumn(name="COD_ONDA_RESSUPRIMENTO_OS", referencedColumnName="COD_ONDA_RESSUPRIMENTO_OS")
     */
    protected $ondaRessuprimentoOs;

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoque")
     * @JoinColumn(name="COD_RESERVA_ESTOQUE", referencedColumnName="COD_RESERVA_ESTOQUE")
     */
    protected $reservaEstoque;

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $os;

    public function setOndaRessuprimentoOs($ondaRessuprimentoOs)
    {
        $this->ondaRessuprimentoOs = $ondaRessuprimentoOs;
    }

    public function getOndaRessuprimentoOs()
    {
        return $this->ondaRessuprimentoOs;
    }

    public function setOs($os)
    {
        $this->os = $os;
    }

    public function getOs()
    {
        return $this->os;
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
