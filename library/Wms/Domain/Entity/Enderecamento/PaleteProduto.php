<?php

namespace Wms\Domain\Entity\Enderecamento;


    /**
 * Palete
 * @Table(name="PALETE_PRODUTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository")
 */
class PaleteProduto
{

    /**
     * @Id
     * @Column(name="COD_PALETE_PRODUTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PALETE_PRODUTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Enderecamento\Palete")
     * @JoinColumn(name="UMA", referencedColumnName="UMA")
     */
    protected $uma;

    /**
     * @Column(name="COD_PRODUTO_EMBALAGEM", type="integer",  nullable=true)
     */
    protected $codProdutoEmbalagem;

    /**
     * @Column(name="COD_PRODUTO_VOLUME", type="integer",  nullable=true)
     */
    protected $codProdutoVolume;

    /**
     * @Column(name="COD_NORMA_PALETIZACAO", type="integer",  nullable=true)
     */
    protected $codNormaPaletizacao;

    /**
     * @Column(name="QTD", type="decimal", nullable=false)
     */
    protected $qtd;

    /**
     * @Column(name="QTD_ENDERECADA", type="decimal", nullable=false)
     */
    protected $qtdEnderecada;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
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
     * @Column(name="DTH_VALIDADE", type="date")
     * @var date
     */
    protected $validade;

    /**
     * @param mixed $codNormaPaletizacao
     */
    public function setCodNormaPaletizacao($codNormaPaletizacao)
    {
        $this->codNormaPaletizacao = $codNormaPaletizacao;
    }

    /**
     * @return mixed
     */
    public function getCodNormaPaletizacao()
    {
        return $this->codNormaPaletizacao;
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

    /**
     * @param mixed $qtdEnderecada
     */
    public function setQtdEnderecada($qtdEnderecada)
    {
        $this->qtdEnderecada = $qtdEnderecada;
    }

    /**
     * @return mixed
     */
    public function getQtdEnderecada()
    {
        return $this->qtdEnderecada;
    }

    /**
     * @param mixed $uma
     */
    public function setUma($uma)
    {
        $this->uma = $uma;
    }

    /**
     * @return mixed
     */
    public function getUma()
    {
        return $this->uma;
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
    public function getCodProduto()
    {
        return $this->codProduto;
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
    public function getGrade()
    {
        return $this->grade;
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
     * @return date
     */
    public function getValidade()
    {
        return $this->validade;
    }

    /**
     * @param date $validade
     */
    public function setValidade($validade)
    {
        $this->validade = $validade;
    }

    public function getEmbalagemEn(){
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $embalagemEn = null;
        if ($this->codProdutoEmbalagem == null) {
            $embalagemEn = $em->getRepository('wms:Produto\Volume')->findOneBy(array('id'=>$this->getCodProdutoVolume()));
        } else {
            $embalagemEn = $em->getRepository('wms:Produto\Embalagem')->findOneBy(array('id'=>$this->getCodProdutoEmbalagem()));
        }
        return $embalagemEn;
    }

}