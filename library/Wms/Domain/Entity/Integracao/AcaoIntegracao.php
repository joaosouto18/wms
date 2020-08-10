<?php

namespace Wms\Domain\Entity\Integracao;

use Wms\Domain\Configurator;
use Wms\Domain\Entity\Util\Sigla;

/**
 *
 * @Table(name="ACAO_INTEGRACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository")
 */
class AcaoIntegracao
{

    const INTEGRACAO_PRODUTO = 600;
    const INTEGRACAO_ESTOQUE = 601;
    const INTEGRACAO_PEDIDOS = 602;
    const INTEGRACAO_RESUMO_CONFERENCIA = 603;
    const INTEGRACAO_CONFERENCIA = 604;
    const INTEGRACAO_NOTAS_FISCAIS = 605;
    const INTEGRACAO_RECEBIMENTO = 606;
    const INTEGRACAO_CORTES = 607;
    const INTEGRACAO_IMPRESSAO_ETIQUETA_MAPA = 608;
    const INTEGRACAO_INICIO_CONFERENCIA_CARGA = 610;
    const INTEGRACAO_INICIO_CONFERENCIA_CARGAS = 611;
    const INTEGRACAO_INICIO_CONFERENCIA_PEDIDO = 612;
    const INTEGRACAO_VERIFICA_CARGA_FINALIZADA = 614;
    const INTEGRACAO_NOTA_FISCAL_SAIDA = 615;
    const INTEGRACAO_LIBERAR_ESTOQUE_ERP = 616;
    const INTEGRACAO_PEDIDO_VENDA = 618;
    const INTEGRACAO_CANCELAMENTO_CARGA = 617;
    const INTEGRACAO_FINALIZACAO_CARGA_RETORNO_PRODUTO = 609;
    const INTEGRACAO_FINALIZACAO_CARGA_RETORNO_CARGA = 619;
    const INTEGRACAO_FINALIZACAO_CARGA_RETORNO_PEDIDO = 620;
    const INTEGRACAO_FINALIZACAO_CARGA_RETORNO_CARGAS = 621;
    const INTEGRACAO_NOTA_LIBERADA_FATURAMENTO = 628;

    const INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_RECEBIMENTO_ERP = 630;
    const INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_NOTA_FISCAL = 631;
    const INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_ITEM_RECEBIMENTO = 632;
    const INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_ITEM_NOTA_FISCAL = 633;
    const INTEGRACAO_COMPARATIVO_INVENTARIO_ERP = 634;

    /**
     * @Id
     * @Column(name="COD_ACAO_INTEGRACAO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ACAO_INTEGRACAO_01", initialValue=1, allocationSize=100)
     */
    protected $id;
    
    /**
     * @var ConexaoIntegracao
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
     * @Column(name="IND_EXECUCAO", type="string", nullable=true)
     */
    protected $indExecucao;

    /**
     * @Column(name="IND_TIPO_CONTROLE", type="string")
     */
    protected $tipoControle;

    /**
     * @Column(name="TABELA_REFERENCIA", type="string")
     */
    protected $tabelaReferencia;

    /**
     * @Column(name="COD_ACAO_RELACIONADA", type="string")
     */
    protected $idAcaoRelacionada;

    /**
     * @Column(name="PARAMETROS", type="string")
     */
    protected $parametros;

    /**
     * @var string
     * @Column(name="DSC_ACAO_INTEGRACAO", type="string")
     */
    protected $dscAcaoIntegracao;

    /**
     * @return mixed
     */
    public function getIdAcaoRelacionada()
    {
        return $this->idAcaoRelacionada;
    }

    /**
     * @param mixed $idAcaoRelacionada
     */
    public function setIdAcaoRelacionada($idAcaoRelacionada)
    {
        $this->idAcaoRelacionada = $idAcaoRelacionada;
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
     * @return ConexaoIntegracao
     */
    public function getConexao()
    {
        return $this->conexao;
    }

    /**
     * @param ConexaoIntegracao $conexao
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
     * @return Sigla
     */
    public function getTipoAcao()
    {
        return $this->tipoAcao;
    }

    /**
     * @param Sigla $tipoAcao
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

    /**
     * @param mixed $indExecucao
     */
    public function setIndExecucao($indExecucao)
    {
        $this->indExecucao = $indExecucao;
    }

    /**
     * @return mixed
     */
    public function getIndExecucao()
    {
        return $this->indExecucao;
    }

    /**
     * @return mixed
     */
    public function getTipoControle()
    {
        return $this->tipoControle;
    }

    /**
     * @param mixed $tipoControle
     */
    public function setTipoControle($tipoControle)
    {
        $this->tipoControle = $tipoControle;
    }

    /**
     * @return mixed
     */
    public function getTabelaReferencia()
    {
        return $this->tabelaReferencia;
    }

    /**
     * @return mixed
     */
    public function getParametros()
    {
        return $this->parametros;
    }

    /**
     * @param mixed $parametros
     */
    public function setParametros($parametros)
    {
        $this->parametros = $parametros;
    }


    /**
     * @param mixed $tabelaReferencia
     */
    public function setTabelaReferencia($tabelaReferencia)
    {
        $this->tabelaReferencia = $tabelaReferencia;
    }

    /**
     * @return string
     */
    public function getDscAcaoIntegracao()
    {
        return $this->dscAcaoIntegracao;
    }

    /**
     * @param string $dscAcaoIntegracao
     */
    public function setDscAcaoIntegracao($dscAcaoIntegracao)
    {
        $this->dscAcaoIntegracao = $dscAcaoIntegracao;
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }
}