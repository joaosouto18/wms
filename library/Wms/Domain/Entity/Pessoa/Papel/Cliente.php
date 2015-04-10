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
class Cliente implements Ator {

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
     * @Column(name="COD_CLIENTE_EXTERNO", type="integer", nullable=false)
     */
    protected $codClienteExterno;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\MapaSeparacao\Praca", cascade={"persist"})
     * @JoinColumn(name="COD_PRACA", referencedColumnName="COD_PRACA")
     * @var Wms\Domain\Entity\MapaSeparacao\Praca Praça do Cliente
     */
    protected $praca;

    public function setCodClienteExterno($codClienteExterno)
    {
        $this->codClienteExterno = $codClienteExterno;
    }

    public function getCodClienteExterno()
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

    public function getPessoa()
    {
        return $this->pessoa;
    }


}