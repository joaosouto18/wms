<?php

namespace Wms\Domain\Entity\Enderecamento;

/**
 * @Table(name="MODELO_END_EST_ARMAZ")
 */
class ModeloEstruturaArmazenagem
{

    /**
     * @Id
     * @Column(name="COD_MODELO_END_EST_ARMAZ", type="int", nullable=false)
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Modelo")
     * @JoinColumn(name="COD_MODELO_ENDERECAMENTO", referencedColumnName="COD_MODELO_ENDERECAMENTO")
     */
    protected $modeloEnderecamento;

    /**
     * @Column(name="COD_TIPO_EST_ARMAZ", type="integer", nullable=false)
     * @var int
     */
    protected $tipoEstruturaArmazenagem;

    /**
     * @Column(name="COD_PRIORIDADE", type="int")
     * @var
     */
    protected $prioridade;

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
     * @return mixed
     */
    public function getModeloEnderecamento()
    {
        return $this->modeloEnderecamento;
    }

    /**
     * @param mixed $modeloEnderecamento
     */
    public function setModeloEnderecamento($modeloEnderecamento)
    {
        $this->modeloEnderecamento = $modeloEnderecamento;
    }

    /**
     * @return int
     */
    public function getTipoEstruturaArmazenagem()
    {
        return $this->tipoEstruturaArmazenagem;
    }

    /**
     * @param int $tipoEstruturaArmazenagem
     */
    public function setTipoEstruturaArmazenagem($tipoEstruturaArmazenagem)
    {
        $this->tipoEstruturaArmazenagem = $tipoEstruturaArmazenagem;
    }

    /**
     * @return mixed
     */
    public function getPrioridade()
    {
        return $this->prioridade;
    }

    /**
     * @param mixed $prioridade
     */
    public function setPrioridade($prioridade)
    {
        $this->prioridade = $prioridade;
    }

}
