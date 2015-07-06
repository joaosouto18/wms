<?php

namespace Wms\Domain\Entity\Inventario;

/**
 * @Table(name="INVENTARIO_CONTAGEM_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Inventario\ContagemEnderecoRepository")
 */
class ContagemEndereco
{

    /**
     * @Column(name="COD_INV_CONT_END", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_CONTAGEM_ENDERECO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="QTD_AVARIA")
     */
    protected $qtdAvaria;

    /**
     * @Column(name="QTD_CONTADA")
     */
    protected $qtdContada;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Inventario\ContagemOs")
     * @JoinColumn(name="COD_INVENTARIO_CONTAGEM_OS", referencedColumnName="COD_INVENTARIO_CONTAGEM_OS")
     */
    protected $contagemOs;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Inventario\Endereco", inversedBy="contagemEndereco")
     * @JoinColumn(name="COD_INVENTARIO_ENDERECO", referencedColumnName="COD_INVENTARIO_ENDERECO")
     */
    protected $inventarioEndereco;

    /**
     * @Column(name="COD_PRODUTO")
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE")
     */
    protected $grade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="QTD_DIVERGENCIA")
     */
    protected $qtdDivergencia;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM")
     */
    protected $codProdutoEmbalagem;

    /**
     * @Column(name="COD_PRODUTO_VOLUME")
     */
    protected $codProdutoVolume;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="DIVERGENCIA")
     */
    protected $divergencia;

    /**
     * @Column(name="NUM_CONTAGEM")
     */
    protected $numContagem;

    /**
     * @Column(name="CONTAGEM_INVENTARIADA")
     */
    protected $contagemInventariada;

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
    public function getNumContagem()
    {
        return $this->numContagem;
    }

    /**
     * @param mixed $numContagem
     */
    public function setNumContagem($numContagem)
    {
        $this->numContagem = $numContagem;
    }

    /**
     * @return mixed
     */
    public function getQtdContada()
    {
        return $this->qtdContada;
    }

    /**
     * @param mixed $qtdContada
     */
    public function setQtdContada($qtdContada)
    {
        $this->qtdContada = $qtdContada;
    }

    /**
     * @return mixed
     */
    public function getContagemOs()
    {
        return $this->contagemOs;
    }

    /**
     * @param mixed $contagemOs
     */
    public function setContagemOs($contagemOs)
    {
        $this->contagemOs = $contagemOs;
    }

    /**
     * @return mixed
     */
    public function getInventarioEndereco()
    {
        return $this->inventarioEndereco;
    }

    /**
     * @param mixed $inventarioEndereco
     */
    public function setInventarioEndereco($inventarioEndereco)
    {
        $this->inventarioEndereco = $inventarioEndereco;
    }

    /**
     * @return mixed
     */
    public function getQtdAvaria()
    {
        return $this->qtdAvaria;
    }

    /**
     * @param mixed $avaria
     */
    public function setQtdAvaria($avaria)
    {
        $this->qtdAvaria = $avaria;
    }

    /**
     * @return mixed
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param mixed $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return mixed
     */
    public function getCodProdutoEmbalagem()
    {
        return $this->codProdutoEmbalagem;
    }

    /**
     * @param mixed $codProdutoEmbalagem
     */
    public function setCodProdutoEmbalagem($codProdutoEmbalagem)
    {
        $this->codProdutoEmbalagem = $codProdutoEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getCodProdutoVolume()
    {
        return $this->codProdutoVolume;
    }

    /**
     * @param mixed $codProdutoVolume
     */
    public function setCodProdutoVolume($codProdutoVolume)
    {
        $this->codProdutoVolume = $codProdutoVolume;
    }

    /**
     * @return mixed
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return mixed
     */
    public function getQtdDivergencia()
    {
        return $this->qtdDivergencia;
    }

    /**
     * @param mixed $qtdDivergencia
     */
    public function setQtdDivergencia($qtdDivergencia)
    {
        $this->qtdDivergencia = $qtdDivergencia;
    }

    /**
     * @return mixed
     */
    public function getDivergencia()
    {
        return $this->divergencia;
    }

    /**
     * @param mixed $divergencia
     */
    public function setDivergencia($divergencia)
    {
        $this->divergencia = $divergencia;
    }

    /**
     * @return mixed
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return mixed
     */
    public function getProdutoVolume()
    {
        return $this->produtoVolume;
    }

    /**
     * @param mixed $produtoVolume
     */
    public function setProdutoVolume($produtoVolume)
    {
        $this->produtoVolume = $produtoVolume;
    }

    /**
     * @return mixed
     */
    public function getContagemInventariada()
    {
        return $this->contagemInventariada;
    }

    /**
     * @param mixed $contagemInventariada
     */
    public function setContagemInventariada($contagemInventariada)
    {
        $this->contagemInventariada = $contagemInventariada;
    }

}
