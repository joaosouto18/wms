<?php

namespace Wms\Domain\Entity\RelatorioCustomizado;
/**
 * @Table(name="RELATORIO_CUSTOMIZADO_FILTRO")
 * @Entity(repositoryClass="Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoFiltroRepository")
 */
class RelatorioCustomizadoFiltro
{
    /**
     * @Id
     * @Column(name="COD_RELATORIO_CUST_FILTRO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RELATORIO_CUST_FILTRO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizado")
     * @JoinColumn(name="COD_RELATORIO_CUSTOMIZADO", referencedColumnName="COD_RELATORIO_CUSTOMIZADO")
     */
    protected $relatorio;

    /**
     * @Column(name="NOME_PARAM", type="string", nullable=false)
     */
    protected $nomeParam;

    /**
     * @Column(name="DSC_TITULO", type="string", nullable=false)
     */
    protected $titulo;

    /**
     * @Column(name="IND_OBRIGATORIO", type="string", nullable=false)
     */
    protected $obrigatorio;
    /**
     * @Column(name="TIPO", type="string", nullable=false)
     */
    protected $tipo;
    /**
     * @Column(name="PARAMS", type="string", nullable=false)
     */
    protected $params;
    /**
     * @Column(name="TAMANHO", type="string", nullable=false)
     */
    protected $tamanho;
    /**
     * @Column(name="DSC_QUERY", type="string", nullable=false)
     */
    protected $query;

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
    public function getNomeParam()
    {
        return $this->nomeParam;
    }

    /**
     * @param mixed $nomeParam
     */
    public function setNomeParam($nomeParam)
    {
        $this->nomeParam = $nomeParam;
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
    public function getObrigatorio()
    {
        return $this->obrigatorio;
    }

    /**
     * @param mixed $obrigatorio
     */
    public function setObrigatorio($obrigatorio)
    {
        $this->obrigatorio = $obrigatorio;
    }

    /**
     * @return mixed
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * @param mixed $tipo
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function getTamanho()
    {
        return $this->tamanho;
    }

    /**
     * @param mixed $tamanho
     */
    public function setTamanho($tamanho)
    {
        $this->tamanho = $tamanho;
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
    public function getRelatorio()
    {
        return $this->relatorio;
    }

    /**
     * @param mixed $relatorio
     */
    public function setRelatorio($relatorio)
    {
        $this->relatorio = $relatorio;
    }
}
