<?php

namespace Wms\Domain\Entity\Pessoa\Papel;

use Wms\Domain\Entity\Pessoa,
    Wms\Domain\Entity\Ator;

/**
 * Cliente
 *
 * @Table(name="CLIENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Pessoa\Papel\ClienteRepository")
 */
class Cliente implements Ator, EmissorInterface {

    /**
     * @var integer $id
     * @Column(name="COD_PESSOA", type="integer", nullable=false)
     * @Id
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @Column(name="COD_CLIENTE_EXTERNO", type="string", nullable=false)
     */
    protected $codClienteExterno;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Praca")
     * @JoinColumn(name="COD_PRACA", referencedColumnName="COD_PRACA")
     */
    protected $praca;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Rota")
     * @JoinColumn(name="COD_ROTA", referencedColumnName="COD_ROTA")
     */
    protected $rota;

    public function setCodClienteExterno($codClienteExterno)
    {
        $this->codClienteExterno = $codClienteExterno;
    }

    public function getCodClienteExterno()
    {
        return $this->codClienteExterno;
    }

    public function getCodExterno()
    {
        return $this->codClienteExterno;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
    }

    /**
     * @return Pessoa
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    public function getPraca()
    {
        return $this->praca;
    }

    public function setPraca($praca)
    {
        $this->praca = $praca;
    }

    /**
     * @return mixed
     */
    public function getRota()
    {
        return $this->rota;
    }

    /**
     * @param mixed $rota
     */
    public function setRota($rota)
    {
        $this->rota = $rota;
    }

}