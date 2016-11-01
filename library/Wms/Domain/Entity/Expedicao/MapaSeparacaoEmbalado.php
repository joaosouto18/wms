<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MAPA_SEPARACAO_EMB_CLIENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository")
 */
class MapaSeparacaoEmbalado
{
    const CONFERENCIA_EMBALADO_INICIADO = 567;
    const CONFERENCIA_EMBALADO_FINALIZADO = 569;

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_MAPA_SEPARACAO_EMB_CLIENTE", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_EMBALADO_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacao")
     * @JoinColumn(name="COD_MAPA_SEPARACAO", referencedColumnName="COD_MAPA_SEPARACAO")
     */
    protected $mapaSeparacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="NUM_SEQUENCIA", type="string", nullable=false)
     */
    protected $sequencia;

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
    public function getPessoa()
    {
        return $this->pessoa;
    }

    /**
     * @param mixed $pessoa
     */
    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
    }

    /**
     * @return mixed
     */
    public function getMapaSeparacao()
    {
        return $this->mapaSeparacao;
    }

    /**
     * @param mixed $mapaSeparacao
     */
    public function setMapaSeparacao($mapaSeparacao)
    {
        $this->mapaSeparacao = $mapaSeparacao;
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
     * @return mixed
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

    /**
     * @param mixed $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

}