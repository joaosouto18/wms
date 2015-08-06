<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="NOTA_FISCAL_SAIDA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository")
 */
class NotaFiscalSaida
{

    /**
     * @Id
     * @Column(name="COD_NOTA_FISCAL_SAIDA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NF_SAIDA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="NUMERO_NOTA", type="integer",nullable=false)
     */
    protected $numeroNf;

    /**
     * @Column(name="SERIE", type="string",nullable=false)
     */
    protected $serieNf;

    /**
     * @Column(name="VALOR_TOTAL_NF", type="decimal",nullable=false)
     */
    protected $valorTotal;

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
     * @param mixed $numeroNf
     */
    public function setNumeroNf($numeroNf)
    {
        $this->numeroNf = $numeroNf;
    }

    /**
     * @return mixed
     */
    public function getNumeroNf()
    {
        return $this->numeroNf;
    }

    /**
     * @param mixed $serieNf
     */
    public function setSerieNf($serieNf)
    {
        $this->serieNf = $serieNf;
    }

    /**
     * @return mixed
     */
    public function getSerieNf()
    {
        return $this->serieNf;
    }

    /**
     * @param mixed $valorTotal
     */
    public function setValorTotal($valorTotal)
    {
        $this->valorTotal = $valorTotal;
    }

    /**
     * @return mixed
     */
    public function getValorTotal()
    {
        return $this->valorTotal;
    }

}