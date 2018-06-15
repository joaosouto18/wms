<?php

namespace Wms\Domain\Entity\Enderecamento;

/**
 * @Table(name="MODELO_END_EST_ARMAZ")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ModeloEstruturaArmazenagemRepository")
 */
class ModeloEstruturaArmazenagem
{

    /**
     * @Id
     * @Column(name="COD_MODELO_END_EST_ARMAZ", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MODELO_END_EST_ARMAZ_01", allocationSize=1, initialValue=1)
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Modelo")
     * @JoinColumn(name="COD_MODELO_ENDERECAMENTO", referencedColumnName="COD_MODELO_ENDERECAMENTO")
     */
    protected $modeloEnderecamento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\Estrutura\Tipo")
     * @JoinColumn(name="COD_TIPO_EST_ARMAZ", referencedColumnName="COD_TIPO_EST_ARMAZ")
     */
    protected $tipoEstruturaArmazenagem;

    /**
     * @Column(name="COD_PRIORIDADE", type="integer")
     * @var int
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
     * @return mixed
     */
    public function getTipoEstruturaArmazenagem()
    {
        return $this->tipoEstruturaArmazenagem;
    }

    /**
     * @param mixed $tipoEstruturaArmazenagem
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
