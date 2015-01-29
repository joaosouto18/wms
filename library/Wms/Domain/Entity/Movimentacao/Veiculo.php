<?php

namespace Wms\Domain\Entity\Movimentacao;

use Core\Util\Converter,
    Wms\Domain\Entity\Pessoa\Papel\Transportador,
    Wms\Domain\Entity\Movimentacao\Veiculo\Tipo;

/**
 * Veiculo
 *
 * @Table(name="VEICULO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Movimentacao\VeiculoRepository")
 */
class Veiculo
{

    /**
     * @Column(name="DSC_PLACA_VEICULO", type="string", length=10, nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @Column(name="DSC_VEICULO", type="string", length=255, nullable=true)
     */
    private $descricao;

    /**
     * @Column(name="NUM_ALTURA_VEICULO", type="decimal", nullable=true)
     */
    private $altura;

    /**
     * @Column(name="NUM_LARGURA_VEICULO", type="decimal", nullable=true)
     */
    private $largura;

    /**
     * @Column(name="NUM_PROFUNDIDADE_VEICULO", type="decimal", nullable=true)
     */
    private $profundidade;

    /**
     * @Column(name="NUM_CUBAGEM_VEICULO", type="decimal", nullable=true)
     */
    private $cubagem;

    /**
     * @Column(name="NUM_CAPACIDADE_VEICULO", type="decimal", nullable=true)
     */
    private $capacidade;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Movimentacao\Veiculo\Tipo")
     * @JoinColumn(name="COD_TIPO_VEICULO", referencedColumnName="COD_TIPO_VEICULO")
     */
    protected $tipo;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa\Papel\Transportador")
     * @JoinColumn(name="COD_TRANSPORTADOR", referencedColumnName="COD_TRANSPORTADOR")
     */
    protected $transportador;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = mb_strtoupper($id, 'UTF-8');
        return $this;
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
        return Converter::enToBr($this->altura, 3);
    }

    public function setAltura($altura)
    {
        $this->altura = Converter::brToEn($altura, 3);
        return $this;
    }

    public function getLargura()
    {
        return Converter::enToBr($this->largura, 3);
    }

    public function setLargura($largura)
    {
        $this->largura = Converter::brToEn($largura, 3);
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

    public function getTipo()
    {
        return $this->tipo;
    }

    public function setTipo(Tipo $tipo)
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getTransportador()
    {
        return $this->transportador;
    }

    public function setTransportador(Transportador $transportador)
    {
        $this->transportador = $transportador;
        return $this;
    }
}