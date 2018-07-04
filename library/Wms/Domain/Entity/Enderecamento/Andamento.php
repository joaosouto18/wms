<?php

namespace Wms\Domain\Entity\Enderecamento;

use Wms\Domain\Entity\Usuario,
    Wms\Domain\Entity\Produto,
    Wms\Domain\Entity\Recebimento;

/**
 * Andamento
 *
 * @Table(name="ENDERECAMENTO_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\AndamentoRepository")
 */
class Andamento
{

    /**
     * @var integer $id
     *
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_END_ANDAMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO")
     */
    protected $recebimento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * Data e hora andamento
     *
     * @var datetime $dataAndamento
     * @Column(name="DTH_ANDAMENTO", type="datetime", nullable=false)
     */
    protected $dataAndamento;

    /**
     * @var \Wms\Domain\Entity\Usuario $usuario
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
     * @param \datetime $dataAndamento
     */
    public function setDataAndamento($dataAndamento)
    {
        $this->dataAndamento = $dataAndamento;
        return $this;
    }

    /**
     * @return \datetime
     */
    public function getDataAndamento()
    {
        return $this->dataAndamento;
    }

    /**
     * @param string $dscObservacao
     */
    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
        return $this;
    }

    /**
     * @return string
     */
    public function getDscObservacao()
    {
        return $this->dscObservacao;
    }

    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
        return $this;
    }

    public function getRecebimento()
    {
        return $this->recebimento;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \Wms\Domain\Entity\Usuario $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
        return $this;
    }

    /**
     * @return \Wms\Domain\Entity\Usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param \Wms\Domain\Entity\Produto $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return \Wms\Domain\Entity\Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }


}
