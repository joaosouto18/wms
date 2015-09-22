<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * @Table(name="MODELO_END_AREA_ARMAZ")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ModeloAreaArmazenagemRepository")
 */
class ModeloAreaArmazenagem
{

    /**
    * @Id
    * @Column(name="COD_MODELO_END_AREA_ARMAZ", type="integer", nullable=false)
    * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ModeloAreaArmazenagemRepository")
    * @GeneratedValue(strategy="SEQUENCE")
    * @SequenceGenerator(sequenceName="SQ_MODELO_END_AREA_ARMAZ_01", allocationSize=1, initialValue=1)
    * @var int
    */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Modelo")
     * @JoinColumn(name="COD_MODELO_ENDERECAMENTO", referencedColumnName="COD_MODELO_ENDERECAMENTO")
     */
    protected $modeloEnderecamento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\AreaArmazenagem")
     * @JoinColumn(name="COD_AREA_ARMAZENAGEM", referencedColumnName="COD_AREA_ARMAZENAGEM")
     */
    protected $areaArmazenagem;

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
    public function getAreaArmazenagem()
    {
        return $this->areaArmazenagem;
    }

    /**
     * @param mixed $areaArmazenagem
     */
    public function setAreaArmazenagem($areaArmazenagem)
    {
        $this->areaArmazenagem = $areaArmazenagem;
    }

    /**
     * @return int
     */
    public function getPrioridade()
    {
        return $this->prioridade;
    }

    /**
     * @param int $prioridade
     */
    public function setPrioridade($prioridade)
    {
        $this->prioridade = $prioridade;
    }

}
