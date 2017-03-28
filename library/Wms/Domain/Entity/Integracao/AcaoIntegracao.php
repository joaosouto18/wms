<?php

namespace Wms\Domain\Entity\Integracao;

use Wms\Domain\Configurator;

/**
 *
 * @Table(name="ACAO_INTEGRACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository")
 */
class AcaoIntegracao
{

    const INTEGRACAO_PRODUTO = 600;
    const INTEGRACAO_ESTOQUE = "601";
    const INTEGRACAO_PEDIDOS = "602";
    const INTEGRACAO_RESUMO_CONFERENCIA = "603";
    const INTEGRACAO_CONFERENCIA = "604";
    const INTEGRACAO_NOTAS_FISCAIS = 605;

    /**
     * @Id
     * @Column(name="COD_ACAO_INTEGRACAO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ACAO_INTEGRACAO_01", initialValue=1, allocationSize=100)
     */
    protected $id;
    
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Integracao\ConexaoIntegracao")
     * @JoinColumn(name="COD_CONEXAO_INTEGRACAO", referencedColumnName="COD_CONEXAO_INTEGRACAO")
     */
    protected $conexao;
    
    /**
     * @Column(name="DSC_QUERY", type="string", nullable=true)
     */
    protected $query;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_TIPO_ACAO_INTEGRACAO", referencedColumnName="COD_SIGLA")
     */
    protected $tipoAcao;

    /**
     * @Column(name="IND_UTILIZA_LOG", type="string", nullable=true)
     */
    protected $indUtilizaLog;

    /**
     * @var \DateTime
     * @Column(name="DTH_ULTIMA_EXECUCAO", type="datetime", nullable=true)
     */
    protected $dthUltimaExecucao;

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
    public function getConexao()
    {
        return $this->conexao;
    }

    /**
     * @param mixed $conexao
     */
    public function setConexao($conexao)
    {
        $this->conexao = $conexao;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
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
    public function getTipoAcao()
    {
        return $this->tipoAcao;
    }

    /**
     * @param mixed $tipoAcao
     */
    public function setTipoAcao($tipoAcao)
    {
        $this->tipoAcao = $tipoAcao;
    }

    /**
     * @return mixed
     */
    public function getIndUtilizaLog()
    {
        return $this->indUtilizaLog;
    }

    /**
     * @param mixed $indUtilizaLog
     */
    public function setIndUtilizaLog($indUtilizaLog)
    {
        $this->indUtilizaLog = $indUtilizaLog;
    }

    /**
     * @return \DateTime
     */
    public function getDthUltimaExecucao()
    {
        return $this->dthUltimaExecucao;
    }

    /**
     * @param \DateTime $dthUltimaExecucao
     */
    public function setDthUltimaExecucao($dthUltimaExecucao)
    {
        $this->dthUltimaExecucao = $dthUltimaExecucao;
    }

}