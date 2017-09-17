<?php

namespace Wms\Domain\Entity\Ressuprimento;

/**
 * @Table(name="PEDIDO_ACUMULADO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\PedidoAcumuladoRepository")
 */
class PedidoAcumulado {

    /**
     * @Id
     * @Column(name="COD_PEDIDO_ACUMULADO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PEDIDO_ACUMULADO", allocationSize=1, initialValue=1)
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
     * @Column(name="COD_PRODUTO", type="decimal", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="decimal", nullable=false)
     */
    protected $grade;

    /**
     * @Column(name="QTD_VENDIDA", type="decimal", nullable=false)
     */
    protected $qtdVendida;

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto) {
        $this->produto = $produto;
    }

    /**
     * @return mixed
     */
    public function getProduto() {
        return $this->produto;
    }

    /**
     * @param mixed $qtdVendida
     */
    public function setQtdVendida($qtdVendida) {
        $this->qtdVendida = $qtdVendida;
    }

    /**
     * @return mixed
     */
    public function getQtdVendida() {
        return $this->qtdVendida;
    }

    /**
     * @return mixed
     */
    public function getCodProduto() {
        return $this->codProduto;
    }

    /**
     * @param mixed $codProduto
     */
    public function setCodProduto($codProduto) {
        $this->codProduto = $codProduto;
    }

    /**
     * @return mixed
     */
    public function getGrade() {
        return $this->grade;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade) {
        $this->grade = $grade;
    }

}
