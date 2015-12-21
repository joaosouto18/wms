<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="RECEBIMENTO_REENTREGA_NF")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository")
 */
class RecebimentoReentregaNota
{

    /**
     * @Id
     * @Column(name="COD_RECEBIMENTO_REENTREGA_NF", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECEBIMENTO_REENTREGA_NF_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\RecebimentoReentrega")
     * @JoinColumn(name="COD_RECEBIMENTO_REENTREGA", referencedColumnName="COD_RECEBIMENTO_REENTREGA")
     */
    protected $recebimentoReentrega;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\NotaFiscalSaida")
     * @JoinColumn(name="COD_NOTA_FISCAL", referencedColumnName="COD_NOTA_FISCAL_SAIDA")
     */
    protected $notaFiscalSaida;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getRecebimentoReentrega()
    {
        return $this->recebimentoReentrega;
    }

    /**
     * @param mixed $recebimentoReentrega
     */
    public function setRecebimentoReentrega($recebimentoReentrega)
    {
        $this->recebimentoReentrega = $recebimentoReentrega;
    }

    /**
     * @return mixed
     */
    public function getNotaFiscalSaida()
    {
        return $this->notaFiscalSaida;
    }

    /**
     * @param mixed $notaFiscalSaida
     */
    public function setNotaFiscalSaida($notaFiscalSaida)
    {
        $this->notaFiscalSaida = $notaFiscalSaida;
    }

}