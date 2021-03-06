<?php
namespace Wms\Domain\Entity\CodigoFornecedor;

/**
 * @Table(name="FORNECEDOR_REFERENCIA")
 * @Entity(repositoryClass="Wms\Domain\Entity\CodigoFornecedor\ReferenciaRepository")
 */
class Referencia
{

    /**
     * @Column(name="COD_FORN_REF", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_FORNECEDOR_REF_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @Column(name="ID_PRODUTO", type="integer", nullable=false)
     */
    protected $idProduto;

    /**
     *
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Papel\Fornecedor", cascade={"persist"})
     * @JoinColumn(name="COD_FORNECEDOR", referencedColumnName="COD_FORNECEDOR")
     * @var \Wms\Domain\Entity\Pessoa\Papel\Fornecedor
     */
    protected $fornecedor;

    /**
     *
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem", cascade={"persist"})
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     * @var \Wms\Domain\Entity\Produto\Embalagem
     */
    protected $embalagem;

    /**
     * @Column(name="DSC_REFERENCIA", type="string", length=1, nullable=true)
     */
    protected $dscReferencia;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getIdProduto()
    {
        return $this->idProduto;
    }

    /**
     * @param mixed $idProduto
     */
    public function setIdProduto($idProduto)
    {
        $this->idProduto = $idProduto;
    }

    /**
     * @return mixed
     */
    public function getFornecedor()
    {
        return $this->fornecedor;
    }

    /**
     * @param mixed $fornecedor
     */
    public function setFornecedor($fornecedor)
    {
        $this->fornecedor = $fornecedor;
    }

    /**
     * @return mixed
     */
    public function getDscReferencia()
    {
        return $this->dscReferencia;
    }

    /**
     * @param mixed $dscReferencia
     */
    public function setDscReferencia($dscReferencia)
    {
        $this->dscReferencia = $dscReferencia;
    }

    /**
     * @return \Wms\Domain\Entity\Produto\Embalagem
     */
    public function getEmbalagem()
    {
        return $this->embalagem;
    }

    /**
     * @param \Wms\Domain\Entity\Produto\Embalagem $embalagem
     */
    public function setEmbalagem($embalagem)
    {
        $this->embalagem = $embalagem;
    }

}