<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="EQUIPE_EXPEDICAO_TRANSBORDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EquipeExpedicaoTransbordoRepository")
 */
class EquipeExpedicaoTransbordo
{

    /**
     * @Id
     * @Column(name="COD_EQUIPE_TRANSBORDO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EQUIPE_EXP_TRANSB_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_VINCULO", type="datetime", nullable=false)
     */
    protected $dataVinculo;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Carga")
     * @JoinColumn(name="COD_CARGA", referencedColumnName="COD_CARGA")
     */
    protected $carga;

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
     * @return mixed
     */
    public function getCarga()
    {
        return $this->carga;
    }

    /**
     * @param mixed $carga
     */
    public function setCarga($carga)
    {
        $this->carga = $carga;
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