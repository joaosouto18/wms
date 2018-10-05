<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="EQUIPE_CARREGAMENTO_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository")
 */
class EquipeCarregamento
{

    /**
     * @Id
     * @Column(name="COD_EQUIPE_CARREGAMENTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EQUIPE_CARREG_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_INICIO", type="datetime", nullable=false)
     */
    protected $dataVinculo;

    /**
     * @Column(name="DTH_FINAL", type="datetime", nullable=false)
     */
    protected $dataFim;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

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
     * @param mixed $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return mixed
     */
    public function getExpedicao()
    {
        return $this->expedicao;
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

    /**
     * @return mixed
     */
    public function getDataFim()
    {
        return $this->dataFim;
    }

    /**
     * @param mixed $dataFim
     */
    public function setDataFim($dataFim)
    {
        $this->dataFim = $dataFim;
    }

}