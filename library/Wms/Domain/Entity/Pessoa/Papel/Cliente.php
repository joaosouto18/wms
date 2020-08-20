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
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

    public function getCodExterno()
    {
        return $this->codExterno;
    }

    /**
     * @return mixed
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    /**
     * @param mixed $pessoa
     */
    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
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

    /**
     * @param bool $maskOn
     * @return string
     * @throws \Exception
     */
    public function getCpfCnpj($maskOn = true)
    {
        if (is_a($this->pessoa, Pessoa\Fisica::class)) {
            return $this->pessoa->getCpf($maskOn);
        } else if (is_a($this->pessoa, Pessoa\Juridica::class)) {
            return $this->pessoa->getCnpj($maskOn);
        }
        throw new \Exception("Tipo Pessoa não identificado!");
    }
    /**
     * @return string
     * @throws \Exception
     */
    public function getNome()
    {
        if (is_a($this->pessoa, Pessoa\Fisica::class)) {
            return $this->pessoa->getNome();
        } else if (is_a($this->pessoa, Pessoa\Juridica::class)){
            return ($this->pessoa->getNomeFantasia() != null) ? $this->pessoa->getNomeFantasia() : $this->pessoa->getNome();
        }
        throw new \Exception("Tipo Pessoa não identificado!");
    }
}