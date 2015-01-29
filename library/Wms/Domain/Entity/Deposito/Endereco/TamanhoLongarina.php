<?php

namespace Wms\Domain\Entity\Deposito\Endereco;
/**
 * Description of Rua
 * @Entity
 * @Table(name="TAMANHO_LONGARINA")
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class TamanhoLongarina
{
    /**
     * @Column(name="NUM_RUA", type="integer", nullable=false)
     * @Id
     */
    protected $rua;

    /**
     * @Column(name="NUM_PREDIO", type="integer", nullable=false)
     * @Id
     */
    protected $predio;

    /**
     * @Column(name="TAMANHO", type="decimal", nullable=false)
     */
    protected $tamanho;

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