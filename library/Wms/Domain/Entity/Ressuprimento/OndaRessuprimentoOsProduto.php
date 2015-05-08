<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="ONDA_RESSUPRIMENTO_OS_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoProdutoOsRepository")
 */
class OndaRessuprimentoOsProduto
{

    /**
     * @Id
     * @Column(name="COD_ONDA_RESSUPRIMENTO_OS_PROD", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ONDA_RESSUPRIMENTO_OS_PROD", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs")
     * @JoinColumn(name="COD_ONDA_RESSUPRIMENTO_OS", referencedColumnName="COD_ONDA_RESSUPRIMENTO_OS")
     */
    protected $ondaRessuprimentoOs;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="QTD", type="integer", nullable=false)
     */
    protected $qtd;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer",  nullable=true)
     */
    protected $codProdutoEmbalagem;

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer",  nullable=true)
     */
    protected $codProdutoVolume;

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
    public function getCodProdutoEmbalagem()
    {
        return $this->codProdutoEmbalagem;
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
    public function getCodProdutoVolume()
    {
        return $this->codProdutoVolume;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $ondaRessuprimentoOs
     */
    public function setOndaRessuprimentoOs($ondaRessuprimentoOs)
    {
        $this->ondaRessuprimentoOs = $ondaRessuprimentoOs;
    }

    /**
     * @return mixed
     */
    public function getOndaRessuprimentoOs()
    {
        return $this->ondaRessuprimentoOs;
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
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param mixed $qtd
     */
    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    /**
     * @return mixed
     */
    public function getQtd()
    {
        return $this->qtd;
    }



}
