<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="APONTAMENTO_SEPARACAO_MAPA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository")
 */
class ApontamentoMapa
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_APONTAMENTO_MAPA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_APONT_MAPA_SEP_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @Column(name="COD_USUARIO", type="integer", nullable=false)
     */
    protected $codUsuario;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacao")
     * @JoinColumn(name="COD_MAPA_SEPARACAO", referencedColumnName="COD_MAPA_SEPARACAO")
     */
    protected $mapaSeparacao;

    /**
     * @Column(name="COD_MAPA_SEPARACAO", type="integer", nullable=false)
     */
    protected $codMapaSeparacao;

    /**
     * @Column(name="DTH_CONFERENCIA", type="datetime", nullable=false)
     */
    protected $dataConferencia;

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
    public function getCodUsuario()
    {
        return $this->codUsuario;
    }

    /**
     * @param mixed $codUsuario
     */
    public function setCodUsuario($codUsuario)
    {
        $this->codUsuario = $codUsuario;
    }

    /**
     * @return mixed
     */
    public function getCodMapaSeparacao()
    {
        return $this->codMapaSeparacao;
    }

    /**
     * @param mixed $codMapaSeparacao
     */
    public function setCodMapaSeparacao($codMapaSeparacao)
    {
        $this->codMapaSeparacao = $codMapaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    /**
     * @param mixed $dataConferencia
     */
    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
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

}