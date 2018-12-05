<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Pessoa;

/**
 *
 * @Table(name="NOTA_FISCAL_SAIDA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository")
 */
class NotaFiscalSaida
{
    const NOTA_FISCAL_EMITIDA = 553;
    const DEVOLVIDO_PARA_REENTREGA = 554;
    const EXPEDIDO_REENTREGA = 555;
    const REENTREGA_DEFINIDA = 562;
    const REENTREGA_EM_SEPARACAO = 563;

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
     * @var int
     * @Column(name="COD_PESSOA", type="string",nullable=false)
     */
    protected $codPessoa;

    /**
     * @var Pessoa
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @Column(name="VALOR_TOTAL_NF", type="decimal",nullable=false)
     */
    protected $valorTotal;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

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

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param int $codPessoa
     */
    public function setCodPessoa($codPessoa)
    {
        $this->codPessoa = $codPessoa;
    }

    /**
     * @return int
     */
    public function getCodPessoa()
    {
        return $this->codPessoa;
    }

    /**
     * @param Pessoa $pessoa
     */
    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
    }

    /**
     * @return Pessoa
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

}