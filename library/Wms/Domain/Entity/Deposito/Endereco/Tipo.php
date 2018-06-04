<?php

namespace Wms\Domain\Entity\Deposito\Endereco;

use Core\Util\Converter;

/**
 * @Table(name="TIPO_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\Endereco\TipoRepository")
 */
class Tipo
{

    /**
     * @Column(name="COD_TIPO_ENDERECO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_ENDERECO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_TIPO_ENDERECO", type="string", length=255, nullable=false)
     */
    protected $descricao;

    /**
     * @Column(name="NUM_ALTURA_ENDERECO", type="decimal", nullable=false)
     */
    protected $altura;

    /**
     * @Column(name="NUM_LARGURA_ENDERECO", type="decimal", nullable=false)
     */
    protected $largura;

    /**
     * @Column(name="NUM_PROFUNDIDADE_ENDERECO", type="decimal", nullable=false)
     */
    protected $profundidade;

    /**
     * @Column(name="NUM_CUBAGEM_ENDERECO", type="decimal", nullable=false)
     */
    protected $cubagem;

    /**
     * @Column(name="NUM_CAPACIDADE_ENDERECO", type="decimal", nullable=false)
     */
    protected $capacidade;

    public function getId()
    {
        return $this->id;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

    public function getAltura()
    {
        return Converter::enToBr($this->altura, 2);
    }

    public function setAltura($altura)
    {
        $this->altura = Converter::brToEn($altura, 2);
        return $this;
    }

    public function getLargura()
    {
        return Converter::enToBr($this->largura, 2);
    }

    public function setLargura($largura)
    {
        $this->largura = Converter::brToEn($largura, 2);
        return $this;
    }

    public function getProfundidade()
    {
        return Converter::enToBr($this->profundidade, 2);
    }

    public function setProfundidade($profundidade)
    {
        $this->profundidade = Converter::brToEn($profundidade, 2);
        return $this;
    }

    public function getCubagem()
    {
        return Converter::enToBr($this->cubagem, 2);
    }

    public function setCubagem($cubagem)
    {
        $this->cubagem = Converter::brToEn($cubagem, 2);
        return $this;
    }

    public function getCapacidade()
    {
        return Converter::enToBr($this->capacidade, 3);
    }

    public function setCapacidade($capacidade)
    {
        $this->capacidade = Converter::brToEn($capacidade, 3);
        return $this;
    }

}