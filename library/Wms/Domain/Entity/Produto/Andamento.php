<?php

namespace Wms\Domain\Entity\Produto;

use Wms\Domain\Entity\Usuario,
    Wms\Domain\Entity\Produto;

/**
 * Andamento
 *
 * @Table(name="PRODUTO_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\AndamentoRepository")
 */
class Andamento
{

    /**
     * @var integer $id
     *
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PROD_ANDAMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO",type="integer", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * Data e hora andamento
     *
     * @var datetime $dataAndamento
     * @Column(name="DTH_ANDAMENTO", type="datetime", nullable=false)
     */
    protected $dataAndamento;

    /**
     * @var Wms\Domain\Entity\Usuario $usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * Descricao do andamento
     *
     * @var string $dscObservacao
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=false)
     */
    protected $dscObservacao;

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
     * @return datetime
     */
    public function getDataAndamento()
    {
        return $this->dataAndamento;
    }

    /**
     * @param datetime $dataAndamento
     */
    public function setDataAndamento($dataAndamento)
    {
        $this->dataAndamento = $dataAndamento;
    }

    /**
     * @return Wms\Domain\Entity\Usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param Wms\Domain\Entity\Usuario $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return string
     */
    public function getDscObservacao()
    {
        return $this->dscObservacao;
    }

    /**
     * @param string $dscObservacao
     */
    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
    }



}
