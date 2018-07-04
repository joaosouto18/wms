<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * Modelo
 *
 * @Table(name="MODELO_ENDERECAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ModeloRepository")
 */
class Modelo
{
    /**
    * @Id
    * @Column(name="COD_MODELO_ENDERECAMENTO", type="integer", nullable=false)
    * @GeneratedValue(strategy="SEQUENCE")
    * @SequenceGenerator(sequenceName="SQ_MODELO_ENDERECAMENTO_01", allocationSize=1, initialValue=1)
    * @var int
    */
    protected $id;

    /**
     * @Column(name="DSC_MODELO_ENDERECAMENTO", type="string", nullable=false)
     * @var string
     */
    protected $descricao;

    /**
     * @Column(name="COD_MODELO_REFERENCIA", type="integer", nullable=false)
     * @var int
     */
    protected $referencia;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_MODELO_REFERENCIA", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $codReferencia;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param string $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    /**
     * @return int
     */
    public function getReferencia()
    {
        return $this->referencia;
    }

    /**
     * @param int $referencia
     */
    public function setReferencia($referencia)
    {
        $this->referencia = $referencia;
    }

    /**
     * @return mixed
     */
    public function getCodReferencia()
    {
        return $this->codReferencia;
    }

    /**
     * @param mixed $codReferencia
     */
    public function setCodReferencia($codReferencia)
    {
        $this->codReferencia = $codReferencia;
    }
}
