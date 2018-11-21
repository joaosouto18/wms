<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="V_REL_PRODUTOS_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\VRelProdutosRepository")
 */
class VRelProdutos
{
    /**
     * @Column(name="COD_EXPEDICAO", type="integer", nullable=false)
     * @id
     */
    protected $codExpedicao;

    /**
     * @Column(name="PRODUTO", type="string", nullable=false)
     * @id
     */
    protected $codProduto;

    /**
     * @Column(name="COD_CARGA", type="integer", nullable=false)
     * @id
     */
    protected $codCarga;

    /**
     * @Column(name="COD_CARGA_EXTERNO", type="integer", nullable=false)
     * @id
     */
    protected $codCargaExterno;

    /**
     * @Column (name="DSC_PLACA_EXPEDICAO", type="string", nullable=false)
     * @id
     */
    protected $dscPlacaExpedicao;

    /**
     * @Column (name="LINHA_ENTREGA", type="string", nullable=false)
     * @id
     */
    protected $dscLinhaEntrega;
    
    /**
     * @Column (name="DSC_ITINERARIO", type="string", nullable=false)
     * @id
     */
    protected $dscItinerario;

    /**
     * @Column (name="COD_ITINERARIO", type="string", nullable=false)
     * @id
     */
    protected $codItinerario;

    /**
     * @Column(name="DESCRICAO", type="string", nullable=false)
     */
    protected $descricao;

    /**
     * @Column(name="MAPA", type="string", nullable=false)
     */
    protected $linhaSeparacao;

    /**
     * @Column(name="GRADE", type="string", nullable=false)
     * @id
     */
    protected $grade;

    /**
     * @Column(name="QUANTIDADE", type="integer", nullable=false)
     */
    protected $quantidade;

    /**
     * @Column(name="SEQ_QUEBRA", type="integer", nullable=false)
     * @id
     */
    protected $seqQuebra;

    /**
     * @Column(name="FABRICANTE", type="string", nullable=false)
     */
    protected $fabricante;

    /**
     * @Column(name="NUM_PESO", type="float", nullable=false)
     */
    protected $peso;

    /**
     * @Column(name="NUM_LARGURA", type="float", nullable=false)
     */
    protected $largura;

    /**
     * @Column(name="NUM_ALTURA", type="float", nullable=false)
     */
    protected $altura;

    /**
     * @Column(name="NUM_PROFUNDIDADE", type="float", nullable=false)
     */
    protected $profundidade;

    /**
     * @Column(name="DSC_VOLUME", type="string", nullable=false)
     * @id
     */
    protected $volume;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;    
        
    /**
     * @Column(name="IND_PADRAO", type="string", nullable=false)
     * @id
     */
    protected $indPadrao;

    /**
     * @Column(name="CENTRAL_ENTREGA", type="integer", nullable=false)
     * @id
     */
    protected $centralEntrega;

    public function setAltura($altura)
    {
        $this->altura = $altura;
    }

    public function getAltura()
    {
        return $this->altura;
    }

    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    public function getCodProduto()
    {
        return $this->codProduto;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setFabricante($fabricante)
    {
        $this->fabricante = $fabricante;
    }

    public function getFabricante()
    {
        return $this->fabricante;
    }

    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setIndPadrao($indPadrao)
    {
        $this->indPadrao = $indPadrao;
    }

    public function getIndPadrao()
    {
        return $this->indPadrao;
    }

    public function setLargura($largura)
    {
        $this->largura = $largura;
    }

    public function getLargura()
    {
        return $this->largura;
    }

    public function setLinhaSeparacao($linhaSeparacao)
    {
        $this->linhaSeparacao = $linhaSeparacao;
    }

    public function getLinhaSeparacao()
    {
        return $this->linhaSeparacao;
    }

    public function setPeso($peso)
    {
        $this->peso = $peso;
    }

    public function getPeso()
    {
        return $this->peso;
    }

    public function setProfundidade($profundidade)
    {
        $this->profundidade = $profundidade;
    }

    public function getProfundidade()
    {
        return $this->profundidade;
    }

    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
    }

    public function getQuantidade()
    {
        return $this->quantidade;
    }

    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

    public function getVolume()
    {
        return $this->volume;
    }
 
    public function setCodCarga ($codCarga) {
        $this->codCarga = $codCarga;
    }
    
    public function getCodCarga () {
        return $this->codCarga;
    }
    
    public function setDscLinhaEntrega ($linhaEntrega) {
        $this->dscLinhaEntrega = $linhaEntrega;
    }
    
    public function getDscLinhaEntrega () {
        return $this->dscLinhaEntrega;
    }
    
    public function setDscItinerario ($Itinerario) {
        $this->dscItinerario = $Itinerario;
    }
    
    public function getDscItinerario () {
        return $this->dscItinerario;
    }
 
    public function setCentralEntrega($centralEntrega)
    {
        $this->centralEntrega = $centralEntrega;
    }

    public function getCentralEntrega()
    {
        return $this->centralEntrega;
    }

    public function setCodCargaExterno($codCargaExterno)
    {
        $this->codCargaExterno = $codCargaExterno;
    }

    public function getCodCargaExterno()
    {
        return $this->codCargaExterno;
    }

    public function setDscPlacaExpedicao($dscPlacaExpedicao)
    {
        $this->dscPlacaExpedicao = $dscPlacaExpedicao;
    }

    public function getDscPlacaExpedicao()
    {
        return $this->dscPlacaExpedicao;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setSeqQuebra($seqQuebra)
    {
        $this->seqQuebra = $seqQuebra;
    }

    public function getSeqQuebra()
    {
        return $this->seqQuebra;
    }

    public function setCodItinerario($codItinerario)
    {
        $this->codItinerario = $codItinerario;
    }

    public function getCodItinerario()
    {
        return $this->codItinerario;
    }

}