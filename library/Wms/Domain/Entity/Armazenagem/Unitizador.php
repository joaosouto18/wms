<?php

namespace Wms\Domain\Entity\Armazenagem;

use Core\Util\Converter;

/**
 * @Table(name="UNITIZADOR")
 * @Entity(repositoryClass="Wms\Domain\Entity\Armazenagem\UnitizadorRepository")
 */
class Unitizador
{

    /**
     * @Column(name="COD_UNITIZADOR", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_UNITIZADOR_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_UNITIZADOR", type="string", length=255, nullable=false)
     */
    protected $descricao;

    /**
     * @Column(name="NUM_LARGURA_UNITIZADOR", type="decimal", nullable=false)
     */
    protected $largura;

    /**
     * @Column(name="NUM_ALTURA_UNITIZADOR", type="decimal", nullable=false)
     */
    protected $altura;

    /**
     * @Column(name="NUM_PROFUNDIDADE_UNITIZADOR", type="decimal", nullable=false)
     */
    protected $profundidade;

    /**
     * @Column(name="NUM_AREA_UNITIZADOR", type="decimal", nullable=false)
     */
    protected $area;

    /**
     * @Column(name="NUM_CUBAGEM_UNITIZADOR", type="decimal", nullable=false)
     */
    protected $cubagem;

    /**
     * @Column(name="NUM_CAPACIDADE_UNITIZADOR", type="decimal", nullable=false)
     */
    protected $capacidade;

    /**
     * @Column(name="QTD_ENDERECOS_BLOQUEAR", type="integer", nullable=false)
     */
    protected $qtdOcupacao;

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

    public function getLargura($converter = true)
    {
        if ($converter == false) {
            return $this->largura;
        }
        return Converter::enToBr($this->largura, 3);
    }

    public function setLargura($largura)
    {
        $this->largura = Converter::brToEn($largura, 3);
        return $this;
    }

    public function getAltura()
    {
        return Converter::enToBr($this->altura, 3);
    }

    public function setAltura($altura)
    {
        $this->altura = Converter::brToEn($altura, 3);
        return $this;
    }

    public function getProfundidade()
    {
        return Converter::enToBr($this->profundidade, 3);
    }

    public function setProfundidade($profundidade)
    {
        $this->profundidade = Converter::brToEn($profundidade, 3);
        return $this;
    }

    public function getArea()
    {
        return Converter::enToBr($this->area, 3);
    }

    public function setArea($area)
    {
        $this->area = Converter::brToEn($area, 3);
        return $this;
    }

    public function getCubagem()
    {
        return Converter::enToBr($this->cubagem, 4);
    }

    public function setCubagem($cubagem)
    {
        $this->cubagem = Converter::brToEn($cubagem, 4);
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

    /**
     * @param mixed $qtdOcupacao
     */
    public function setQtdOcupacao($qtdOcupacao)
    {
        $this->qtdOcupacao = $qtdOcupacao;
    }

    /**
     * @return mixed
     */
    public function getQtdOcupacao()
    {
        return $this->qtdOcupacao;
    }

}