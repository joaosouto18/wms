<?php

namespace Wms\Domain\Entity\Deposito\Endereco\Rua;

/**
 * @Table(name="SENTIDO_RUA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\Endereco\Rua\SentidoRepository")
 */
class Sentido
{

    /**
     * @Column(name="COD_SENTIDO_RUA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_SENTIDO_RUA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $rua
     * @Column(name="NUM_RUA", type="integer", nullable=false)
     */
    protected $rua;

    /**
     * @Column(name="DSC_SENTIDO_RUA", type="string", length=60, nullable=true)
     */
    protected $descricao;

    /**
     * @var smallint $idDeposito
     * @Column(name="COD_DEPOSITO", type="smallint", nullable=false)
     */
    protected $idDeposito;

    /**
     * @var Wms\Domain\Entity\Deposito $deposito
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito")
     * @JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO") 
     */
    protected $deposito;

    public function getId()
    {
        return $this->id;
    }

    public function getDeposito()
    {
        return $this->deposito;
    }

    public function setDeposito($deposito)
    {
        $this->deposito = $deposito;
        return $this;
    }

    public function getRua()
    {
        return $this->rua;
    }

    public function setRua($rua)
    {
        $this->rua = $rua;
        return $this;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getIdDeposito()
    {
        return $this->idDeposito;
    }

    public function setIdDeposito($idDeposito)
    {
        $this->idDeposito = $idDeposito;
        return $this;
    }

}