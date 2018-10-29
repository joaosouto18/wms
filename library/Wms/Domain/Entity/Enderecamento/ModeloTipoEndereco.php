<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * @Table(name="MODELO_END_TIPO_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ModeloTipoEnderecoRepository")
 */
class ModeloTipoEndereco
{

    /**
    * @Id
    * @Column(name="COD_MODELO_END_TIPO_ENDERECO", type="integer", nullable=false)
    * @GeneratedValue(strategy="SEQUENCE")
    * @SequenceGenerator(sequenceName="SQ_MODELO_END_TIPO_ENDERECO_01", allocationSize=1, initialValue=1)
     * @var int
    */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Modelo")
     * @JoinColumn(name="COD_MODELO_ENDERECAMENTO", referencedColumnName="COD_MODELO_ENDERECAMENTO")
     */
    protected $modeloEnderecamento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco\Tipo")
     * @JoinColumn(name="COD_TIPO_ENDERECO", referencedColumnName="COD_TIPO_ENDERECO")
     */
    protected $tipoEndereco;

    /**
     * @Column(name="COD_PRIORIDADE", type="integer")
     * @int
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
    public function getTipoEndereco()
    {
        return $this->tipoEndereco;
    }

    /**
     * @param mixed $tipoEndereco
     */
    public function setTipoEndereco($tipoEndereco)
    {
        $this->tipoEndereco = $tipoEndereco;
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
