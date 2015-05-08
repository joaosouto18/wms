<?php

namespace Wms\Domain\Entity\Enderecamento;


/**
 * Palete
 *
 * @Table(name="HISTORICO_ESTOQUE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository")
 */
class HistoricoEstoque
{

    const MANUAL = 'M';
    const SISTEMA = 'S';

    /**
     * U.M.A
     * @Column(name="COD_HISTORICO_ESTOQUE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_HISTORICO_ESTOQUE_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="QTD", type="integer", nullable=false)
     */
    protected $qtd;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column(name="OBSERVACAO", type="string", nullable=true)
     */
    protected $observacao;

    /**
     * @Column(name="DTH_MOVIMENTACAO", type="datetime", nullable=true)
     */
    protected $data;

    /**
     * @Column(name="COD_OS", type="integer", nullable=true)
     */
    protected $codOS;

    /**
     * @var Wms\Domain\Entity\OrdemServico $ordemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $ordemServico;

    /**
     * @var Wms\Domain\Entity\Usuario $usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @Column(name="IND_TIPO", type="string", length=1)
     * @var string
     */
    protected $tipo;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\Unitizador")
     * @JoinColumn(name="COD_UNITIZADOR", referencedColumnName="COD_UNITIZADOR")
     */

    protected $unitizador;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     */
    protected $produtoEmbalagem;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Volume")
     * @JoinColumn(name="COD_PRODUTO_VOLUME", referencedColumnName="COD_PRODUTO_VOLUME")
     */
    protected $produtoVolume;

    /**
     * @Column(name="UMA", type="integer", nullable=false)
     */
    protected $uma;

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
     * @param mixed $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
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

    /**
     * @param mixed $codOS
     */
    public function setCodOS($codOS)
    {
        $this->codOS = $codOS;
    }

    /**
     * @return mixed
     */
    public function getCodOS()
    {
        return $this->codOS;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $observacao
     */
    public function setObservacao($observacao)
    {
        $this->observacao = $observacao;
    }

    /**
     * @return mixed
     */
    public function getObservacao()
    {
        return $this->observacao;
    }

    /**
     * @param mixed $ordemServico
     */
    public function setOrdemServico($ordemServico)
    {
        $this->ordemServico = $ordemServico;
    }

    /**
     * @return mixed
     */
    public function getOrdemServico()
    {
        return $this->ordemServico;
    }

    /**
     * @param string $tipo
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    /**
     * @return string
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * @param mixed $unitizador
     */
    public function setUnitizador($unitizador)
    {
        $this->unitizador = $unitizador;
    }

    /**
     * @return mixed
     */
    public function getUnitizador()
    {
        return $this->unitizador;
    }

    /**
     * @param mixed $produtoEmbalagem
     */
    public function setProdutoEmbalagem($produtoEmbalagem)
    {
        $this->produtoEmbalagem = $produtoEmbalagem;
    }

    /**
     * @return mixed
     */
    public function getProdutoEmbalagem()
    {
        return $this->produtoEmbalagem;
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
    public function getProdutoVolume()
    {
        return $this->produtoVolume;
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
     * @param \Wms\Domain\Entity\Enderecamento\Wms\Domain\Entity\Usuario $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return \Wms\Domain\Entity\Enderecamento\Wms\Domain\Entity\Usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }


}