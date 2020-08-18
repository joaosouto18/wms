<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\OrdemServico;

/**
 * @Table(name="CONF_CARREG_OS")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ConfCarregOsRepository")
 */
class ConfCarregOs
{

    /**
     * @var int
     * @Column(name="COD_CONF_CARREG_OS", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_CONF_CARREG_OS_01", allocationSize=1, initialValue=1)
     * @GeneratedValue(strategy="SEQUENCE")
     * @Id
     */
    protected $id;

    /**
     * @var ConferenciaCarregamento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\ConferenciaCarregamento")
     * @JoinColumn(name="COD_CONF_CARREG", referencedColumnName="COD_CONF_CARREG")
     */
    protected $conferenciaCarregamento;

    /**
     * @var OrdemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $ordemServico;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ConferenciaCarregamento
     */
    public function getConferenciaCarregamento()
    {
        return $this->conferenciaCarregamento;
    }

    /**
     * @param ConferenciaCarregamento $conferenciaCarregamento
     */
    public function setConferenciaCarregamento($conferenciaCarregamento)
    {
        $this->conferenciaCarregamento = $conferenciaCarregamento;
    }

    /**
     * @return OrdemServico
     */
    public function getOrdemServico()
    {
        return $this->ordemServico;
    }

    /**
     * @param OrdemServico $ordemServico
     */
    public function setOrdemServico($ordemServico)
    {
        $this->ordemServico = $ordemServico;
    }

}