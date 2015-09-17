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
    * @Column(name="COD_MODELO_ENDERECAMENTO", type="int", nullable=false)
    * @var int
    */
    protected $id;

    /**
     * @Column(name="DSC_MODELO_ENDERECAMENTO", type="string", nullable=false)
     * @var string
     */
    protected $descricao;

    /**
     * @Column(name="COD_MODELO_REFERENCIA", type="int", nullable=false)
     * @var int
     */
    protected $referencia;

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

}
