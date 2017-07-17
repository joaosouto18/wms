<?php

namespace Wms\Domain\Entity\Integracao;

use Wms\Domain\Configurator;

/**
 *
 * @Table(name="ACAO_INTEGRACAO_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\AcaoIntegracaoAndamentoRepository")
 */
class   AcaoIntegracaoAndamento
{

    /**
     * @Id
     * @Column(name="COD_ACAO_INTEGRACAO_ANDAMENTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ACAO_INTEGRACAO_AND_01", initialValue=1, allocationSize=1)
     */
    protected $id;
    
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Integracao\AcaoIntegracao")
     * @JoinColumn(name="COD_ACAO_INTEGRACAO", referencedColumnName="COD_ACAO_INTEGRACAO")
     */
    protected $acaoIntegracao;
    
    /**
     * @var \DateTime
     * @Column(name="DTH_ANDAMENTO", type="datetime", nullable=true)
     */
    protected $dthAndamento;

    /**
     * @Column(name="IND_SUCESSO", type="string", nullable=true)
     */
    protected $indSucesso;

    /**
     * @Column(name="URL", type="string", nullable=true)
     */
    protected $url;

    /**
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=true)
     */
    protected $observacao;

    /**
     * @Column(name="TRACE", type="string", nullable=true)
     */
    protected $trace;

    /**
     * @Column(name="QUERY", type="string", nullable=true)
     */
    protected $query;

    /**
     * @Column(name="IND_DESTINO", type="string", nullable=true)
     */
    protected $destino;

    /**
     * @Column(name="ERR_NUMBER", type="string", nullable=true)
     */
    protected $errNumber;

    /**
     * @param mixed $errNumber
     */
    public function setErrNumber($errNumber)
    {
        $this->errNumber = $errNumber;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getErrNumber()
    {
        return $this->errNumber;
    }

    /**
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $destino
     */
    public function setDestino($destino)
    {
        $this->destino = $destino;
    }

    /**
     * @return mixed
     */
    public function getDestino()
    {
        return $this->destino;
    }

    /**
     * @param mixed $trace
     */
    public function setTrace($trace)
    {
        $this->trace = $trace;
    }

    /**
     * @return mixed
     */
    public function getTrace()
    {
        return $this->trace;
    }
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
    public function getAcaoIntegracao()
    {
        return $this->acaoIntegracao;
    }

    /**
     * @param mixed $acaoIntegracao
     */
    public function setAcaoIntegracao($acaoIntegracao)
    {
        $this->acaoIntegracao = $acaoIntegracao;
    }

    /**
     * @return \DateTime
     */
    public function getDthAndamento()
    {
        return $this->dthAndamento;
    }

    /**
     * @param \DateTime $dthAndamento
     */
    public function setDthAndamento($dthAndamento)
    {
        $this->dthAndamento = $dthAndamento;
    }

    /**
     * @return mixed
     */
    public function getIndSucesso()
    {
        return $this->indSucesso;
    }

    /**
     * @param mixed $indSucesso
     */
    public function setIndSucesso($indSucesso)
    {
        $this->indSucesso = $indSucesso;
    }

    /**
     * @return mixed
     */
    public function getObservacao()
    {
        return $this->observacao;
    }

    /**
     * @param mixed $observacao
     */
    public function setObservacao($observacao)
    {
        $this->observacao = $observacao;
    }

}