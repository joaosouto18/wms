<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * PosicaoEstoque
 *
 * @Table(name="POSICAO_ESTOQUE_RESUMIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\PosicaoEstoqueResumidoRepository")
 */
class PosicaoEstoqueResumido
{
    /**
     * @Column(name="COD_POSICAO_ESTOQUE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_POSICAO_ESTOQUE_RESUM_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="NUM_RUA", type="string", nullable=false)
     */
    protected $rua;

    /**
     * @Column(name="QTD_EXISTENTES", type="integer", nullable=false)
     */
    protected $qtdExistentes;

    /**
     * @Column(name="QTD_OCUPADOS", type="integer", nullable=false)
     */
    protected $qtdOcupados;

    /**
     * @Column(name="QTD_VAZIOS", type="integer", nullable=false)
     */
    protected $qtdVazios;

    /**
     * @Column(name="OCUPACAO", type="decimal", nullable=false)
     */
    protected $percentualOcupacao;

    /**
     * @Column(name="DTH_ESTOQUE", type="datetime", nullable=true)
     */
    protected $dtEstoque;

    /**
     * @param mixed $dtEstoque
     */
    public function setDtEstoque($dtEstoque)
    {
        $this->dtEstoque = $dtEstoque;
    }

    /**
     * @return mixed
     */
    public function getDtEstoque()
    {
        return $this->dtEstoque;
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
     * @param mixed $percentualOcupacao
     */
    public function setPercentualOcupacao($percentualOcupacao)
    {
        $this->percentualOcupacao = $percentualOcupacao;
    }

    /**
     * @return mixed
     */
    public function getPercentualOcupacao()
    {
        return $this->percentualOcupacao;
    }

    /**
     * @param mixed $qtdExistentes
     */
    public function setQtdExistentes($qtdExistentes)
    {
        $this->qtdExistentes = $qtdExistentes;
    }

    /**
     * @return mixed
     */
    public function getQtdExistentes()
    {
        return $this->qtdExistentes;
    }

    /**
     * @param mixed $qtdOcupados
     */
    public function setQtdOcupados($qtdOcupados)
    {
        $this->qtdOcupados = $qtdOcupados;
    }

    /**
     * @return mixed
     */
    public function getQtdOcupados()
    {
        return $this->qtdOcupados;
    }

    /**
     * @param mixed $qtdVazios
     */
    public function setQtdVazios($qtdVazios)
    {
        $this->qtdVazios = $qtdVazios;
    }

    /**
     * @return mixed
     */
    public function getQtdVazios()
    {
        return $this->qtdVazios;
    }

    /**
     * @param mixed $rua
     */
    public function setRua($rua)
    {
        $this->rua = $rua;
    }

    /**
     * @return mixed
     */
    public function getRua()
    {
        return $this->rua;
    }

}