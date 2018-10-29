<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * @Table(name="MODELO_END_CARACT_END")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ModeloCaracteristicaEnderecoRepository")
 */
class ModeloCaracteristicaEndereco
{

    /**
    * @Id
    * @Column(name="COD_MODELO_END_CARACT_END", type="integer", nullable=false)
    * @GeneratedValue(strategy="SEQUENCE")
    * @SequenceGenerator(sequenceName="SQ_MODELO_END_CARACT_END_01", allocationSize=1, initialValue=1)
     * @var int
    */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Modelo")
     * @JoinColumn(name="COD_MODELO_ENDERECAMENTO", referencedColumnName="COD_MODELO_ENDERECAMENTO")
     */
    protected $modeloEnderecamento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco\Caracteristica")
     * @JoinColumn(name="COD_CARACTERISTICA_ENDERECO", referencedColumnName="COD_CARACTERISTICA_ENDERECO")
     */
    protected $caracteristicaEndereco;

    /**
     * @Column(name="COD_PRIORIDADE", type="integer")
     * @int
     */
    protected $prioridade;

    /**
     * @param mixed $caracteristicaEndereco
     */
    public function setCaracteristicaEndereco($caracteristicaEndereco)
    {
        $this->caracteristicaEndereco = $caracteristicaEndereco;
    }

    /**
     * @return mixed
     */
    public function getCaracteristicaEndereco()
    {
        return $this->caracteristicaEndereco;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
    public function getModeloEnderecamento()
    {
        return $this->modeloEnderecamento;
    }

    /**
     * @param mixed $prioridade
     */
    public function setPrioridade($prioridade)
    {
        $this->prioridade = $prioridade;
    }

    /**
     * @return mixed
     */
    public function getPrioridade()
    {
        return $this->prioridade;
    }



}
