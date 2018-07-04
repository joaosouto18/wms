<?php

namespace Wms\Domain\Entity\Armazenagem;

/**
 *
 * @Table(name="V_OCUP_RESERVA_LONGARINA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Armazenagem\VOcupacaoReservaLongarinaRepository")
 */
class VOcupacaoReservaLongarina
{
    /**
     * @Column(name="OCUPADO", type="integer", nullable=false)
     * @id
     */
    protected $qtdOcupada;

    /**
     * @Column(name="NUM_PREDIO", type="integer", nullable=false)
     * @id
     */
    protected $predio;

    /**
     * @Column(name="NUM_NIVEL", type="integer", nullable=false)
     * @id
     */
    protected $nivel;

    /**
     * @Column(name="NUM_RUA", type="integer", nullable=false)
     * @id
     */
    protected $rua;

    /**
     * @Column(name="TAMANHO_LONGARINA", type="integer", nullable=false)
     * @id
     */
    protected $tamanho;

    /**
     * @param mixed $nivel
     */
    public function setNivel($nivel)
    {
        $this->nivel = $nivel;
    }

    /**
     * @return mixed
     */
    public function getNivel()
    {
        return $this->nivel;
    }

    /**
     * @param mixed $predio
     */
    public function setPredio($predio)
    {
        $this->predio = $predio;
    }

    /**
     * @return mixed
     */
    public function getPredio()
    {
        return $this->predio;
    }

    /**
     * @param mixed $qtdOcupada
     */
    public function setQtdOcupada($qtdOcupada)
    {
        $this->qtdOcupada = $qtdOcupada;
    }

    /**
     * @return mixed
     */
    public function getQtdOcupada()
    {
        return $this->qtdOcupada;
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

    /**
     * @param mixed $tamanho
     */
    public function setTamanho($tamanho)
    {
        $this->tamanho = $tamanho;
    }

    /**
     * @return mixed
     */
    public function getTamanho()
    {
        return $this->tamanho;
    }


}