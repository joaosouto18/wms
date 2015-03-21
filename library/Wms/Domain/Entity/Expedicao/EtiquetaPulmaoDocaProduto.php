<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="ETIQUETA_PULMAO_DOCA_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\EtiquetaPulmaoDocaProdutoRepository")
 */
class EtiquetaPulmaoDocaProduto
{
    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_ETIQUETA_PULMAO_DOCA_PRODUTO", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_ETIQUETA_PULMAO_DOCA_PRODUTO_01", initialValue=1, allocationSize=1)
     */
    protected $id;


    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\EtiquetaPulmaoDoca")
     * @JoinColumn(name="COD_ETIQUETA_PULMAO_DOCA", referencedColumnName="COD_ETIQUETA_PULMAO_DOCA")
     */
    protected $etiquetaPulmaoDoca;

    /**
     * @Column(name="COD_ETIQUETA_PULMAO_DOCA", type="integer", nullable=true)
     */
    protected $codEtiquetaPulmaoDoca;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer", nullable=true)
     */
    protected $codProdutoEmbalagem;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer", nullable=true)
     */
    protected $codProdutoVolume;

    /**
     * @Column(name="QTD_PRODUTO", type="integer", nullable=true)
     */
    protected $qtdProduto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\EtiquetaMae")
     * @JoinColumn(name="COD_ETIQUETA_MAE", referencedColumnName="COD_ETIQUETA_MAE")
     */
    protected $etiquetaMae;

    /**
     * @Column(name="COD_ETIQUETA_MAE", type="integer", nullable=true)
     */
    protected $codEtiquetaMae;


    public function setProdutoVolume($produtoVolume)
    {
        $this->produtoVolume = $produtoVolume;
    }

    public function getProdutoVolume()
    {
        return $this->produtoVolume;
    }

    public function setCodProdutoVolume($codProdutoVolume)
    {
        $this->codProdutoVolume = $codProdutoVolume;
    }

    public function getCodProdutoVolume()
    {
        return $this->codProdutoEmbalagem;
    }


    public function setProdutoEmbalagem($produtoEmbalagem)
    {
        $this->produtoEmbalagem = $produtoEmbalagem;
    }

    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
    }

    public function setCodProdutoEmbalagem($codProdutoEmbalagem)
    {
        $this->codProdutoEmbalagem = $codProdutoEmbalagem;
    }

    public function getCodProdutoEmbalagem()
    {
        return $this->codProdutoEmbalagem;
    }

    public function setEtiquetaPulmaoDoca($etiquetaPulmaoDoca)
    {
        $this->etiquetaPulmaoDoca = $etiquetaPulmaoDoca;
    }

    public function getEtiquetaPulmaoDoca()
    {
        return $this->etiquetaPulmaoDoca;
    }

    public function setCodEtiquetaPulmaoDoca($codEtiquetaPulmaoDoca)
    {
        $this->codEtiquetaPulmaoDoca = $codEtiquetaPulmaoDoca;
    }

    public function getCodEtiquetaPulmaoDoca()
    {
        return $this->codEtiquetaPulmaoDoca;
    }


    public function setQtdProduto($qtdProduto)
    {
        $this->qtdProduto = $qtdProduto;
    }

    public function getQtdProduto()
    {
        return $this->qtdProduto;
    }

}