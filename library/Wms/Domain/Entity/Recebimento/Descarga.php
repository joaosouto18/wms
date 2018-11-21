<?php

namespace Wms\Domain\Entity\Recebimento;

/**
 *
 * @Table(name="RECEBIMENTO_DESCARGA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\DescargaRepository")
 */
class Descarga
{

    /**
     * @Id
     * @Column(name="COD_RECEBIMENTO_DESCARGA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECE_DESC_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_VINCULO", type="datetime", nullable=false)
     */
    protected $dataVinculo;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @param mixed $dataVinculo
     */
    public function setDataVinculo($dataVinculo)
    {
        $this->dataVinculo = $dataVinculo;
    }

    /**
     * @return mixed
     */
    public function getDataVinculo()
    {
        return $this->dataVinculo;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $recebimento
     */
    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
    }

    /**
     * @return mixed
     */
    public function getRecebimento()
    {
        return $this->recebimento;
    }

    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return mixed
     */
    public function getUsuario()
    {
        return $this->usuario;
    }


}