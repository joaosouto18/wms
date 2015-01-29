<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * Palete
 *
 * @Table(name="RELATORIO_PICKING")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\RelatorioPickingRepository")
 */
class RelatorioPicking
{
    /**
     * U.M.A
     * @Column(name="COD_RELATORIO_PICKING", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RELATORIO_PICKING_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @param mixed $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
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

}