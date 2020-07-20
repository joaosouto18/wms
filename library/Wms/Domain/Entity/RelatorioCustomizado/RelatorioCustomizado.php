<?php

namespace Wms\Domain\Entity\RelatorioCustomizado;
/**
 * @Table(name="RELATORIO_CUSTOMIZADO")
 * @Entity(repositoryClass="Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoRepository")
 */
class RelatorioCustomizado
{
    /**
     * @Id
     * @Column(name="COD_RELATORIO_CUSTOMIZADO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RELATORIO_CUSTOMIZADO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_TITULO_RELATORIO", type="string", nullable=false)
     */
    protected $titulo;

    /**
     * @Column(name="DSC_QUERY", type="string", nullable=false)
     */
    protected $query;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Integracao\ConexaoIntegracao")
     * @JoinColumn(name="COD_CONEXAO_INTEGRACAO", referencedColumnName="COD_CONEXAO_INTEGRACAO")
     */
    protected $conexao;

    /**
     * @Column(name="IND_ALLOW_XLS", type="string", nullable=false)
     */
    protected $allowXLS;

    /**
     * @Column(name="IND_ALLOW_PDF", type="string", nullable=false)
     */
    protected $allowPDF;

    /**
     * @Column(name="IND_ALLOW_SEARCH", type="string", nullable=false)
     */
    protected $allowSearch;

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
    public function getTitulo()
    {
        return $this->titulo;
    }

    /**
     * @param mixed $titulo
     */
    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
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
    public function getAllowXLS()
    {
        return $this->allowXLS;
    }

    /**
     * @param mixed $allowXLS
     */
    public function setAllowXLS($allowXLS)
    {
        $this->allowXLS = $allowXLS;
    }

    /**
     * @return mixed
     */
    public function getAllowPDF()
    {
        return $this->allowPDF;
    }

    /**
     * @param mixed $allowPDF
     */
    public function setAllowPDF($allowPDF)
    {
        $this->allowPDF = $allowPDF;
    }

    /**
     * @return mixed
     */
    public function getAllowSearch()
    {
        return $this->allowSearch;
    }

    /**
     * @param mixed $allowSearch
     */
    public function setAllowSearch($allowSearch)
    {
        $this->allowSearch = $allowSearch;
    }

}
