<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="ONDA_RESSUPRIMENTO_PEDIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedidoRepository")
 */
class OndaRessuprimentoPedido
{
    /**
     * @Id
     * @Column(name="COD_ONDA_RESSUPRIMENTO_PEDIDO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ONDA_RESSUPRIMENTO_PEDIDO", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\OndaRessuprimento")
     * @JoinColumn(name="COD_ONDA_RESSUPRIMENTO", referencedColumnName="COD_ONDA_RESSUPRIMENTO")
     */
    protected $ondaRessuprimento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Pedido")
     * @JoinColumn(name="COD_PEDIDO", referencedColumnName="COD_PEDIDO")
     */
    protected $pedido;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="QTD", type="decimal", nullable=false)
     */
    protected $qtd;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOndaRessuprimento($ondaRessuprimento)
    {
        $this->ondaRessuprimento = $ondaRessuprimento;
    }

    public function getOndaRessuprimento()
    {
        return $this->ondaRessuprimento;
    }

    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    public function getPedido()
    {
        return $this->pedido;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    public function getQtd()
    {
        return $this->qtd;
    }

}
