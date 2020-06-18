<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Pessoa\Papel\Cliente;

/**
 * @Table(name="CONF_CARREG_CLIENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ConfCarregClienteRepository")
 */
class ConfCarregCliente
{

    /**
     * @var int
     * @Column(name="COD_CONF_CARREG_CLI", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CONF_CARREG_CLIENTE_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @var Cliente
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Papel\Cliente")
     * @JoinColumn(name="COD_CLIENTE", referencedColumnName="COD_PESSOA")
     */
    protected $cliente;

    /**
     * @var ConferenciaCarregamento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\ConferenciaCarregamento")
     * @JoinColumn(name="COD_CONF_CARREG", referencedColumnName="COD_CONF_CARREG")
     */
    protected $conferenciaCarregamento;

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

    /**
     * @return Cliente
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param Cliente $cliente
     */
    public function setCliente($cliente)
    {
        $this->cliente = $cliente;
    }

    /**
     * @return ConferenciaCarregamento
     */
    public function getConferenciaCarregamento()
    {
        return $this->conferenciaCarregamento;
    }

    /**
     * @param ConferenciaCarregamento $conferenciaCarregamento
     */
    public function setConferenciaCarregamento($conferenciaCarregamento)
    {
        $this->conferenciaCarregamento = $conferenciaCarregamento;
    }

}