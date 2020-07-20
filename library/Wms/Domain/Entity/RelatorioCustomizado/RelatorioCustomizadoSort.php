<?php

namespace Wms\Domain\Entity\RelatorioCustomizado;
/**
 * @Table(name="RELATORIO_CUSTOMIZADO_SORT")
 * @Entity(repositoryClass="Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoSortRepository")
 */
class RelatorioCustomizadoSort
{
    /**
     * @Id
     * @Column(name="COD_RELATORIO_CUSTOMIZADO_FILTRO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RELATORIO_CUSTOMIZADO_FILTRO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_TITULO", type="string", nullable=false)
     */
    protected $titulo;

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

}
