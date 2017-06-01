<?php

namespace Wms\Domain\Entity\Integracao\TabelaTemporaria;

use Wms\Domain\Configurator;

/**
 *
 * @Table(name="INTEGRACAO_PEDIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\TabelaTemporaria\PedidoRepository")
 */
class Pedido
{
    /**
     * @Id
     * @Column(name="COD_INTEGRACAO_PEDIDO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INTEGRACAO_NF_PEDIDO_01", initialValue=1, allocationSize=100)
     */
    protected $id;

   /**
     * @Column(name="CARGA", type="string", nullable=true)
     */
    protected $carga;

    /**
     * @Column(name="PLACA", type="string", nullable=true)
     */
    protected $placa;

    /**
     * @Column(name="PEDIDO", type="string", nullable=true)
     */
    protected $pedido;

    /**
     * @Column(name="COD_PRACA", type="string", nullable=true)
     */
    protected $codPraca;

    /**
     * @Column(name="DSC_PRACA", type="string", nullable=true)
     */
    protected $dscPraca;

    /**
     * @Column(name="COD_ROTA", type="string", nullable=true)
     */
    protected $codRota;

    /**
     * @Column(name="DSC_ROTA", type="string", nullable=true)
     */
    protected $dscRota;

    /**
     * @Column(name="COD_CLIENTE", type="string", nullable=true)
     */
    protected $codCliente;

    /**
     * @Column(name="NOME", type="string", nullable=true)
     */
    protected $nomeCliente;

    /**
     * @Column(name="CPF_CNPJ", type="string", nullable=true)
     */
    protected $cpfCnpj;

    /**
     * @Column(name="TIPO_PESSOA", type="string", nullable=true)
     */
    protected $tipoPessoa;

    /**
     * @Column(name="LOGRADOURO", type="string", nullable=true)
     */
    protected $logradouro;

    /**
     * @Column(name="NUMERO", type="string", nullable=true)
     */
    protected $numero;

    /**
     * @Column(name="BAIRRO", type="string", nullable=true)
     */
    protected $bairro;

    /**
     * @Column(name="CIDADE", type="string", nullable=true)
     */
    protected $cidade;

    /**
     * @Column(name="UF", type="string", nullable=true)
     */
    protected $uf;

    /**
     * @Column(name="COMPLEMENTO", type="string", nullable=true)
     */
    protected $complemento;

    /**
     * @Column(name="CEP", type="string", nullable=true)
     */
    protected $cep;

    /**
     * @Column(name="PRODUTO", type="string", nullable=true)
     */
    protected $codProduto;

    /**
     * @Column(name="GRADE", type="string", nullable=true)
     */
    protected $grade;

    /**
     * @Column(name="REFERENCIA", type="string", nullable=true)
     */
    protected $referencia;

    /**
     * @Column(name="QTD", type="decimal", length=8, nullable=true)
     */
    protected $qtd;

    /**
     * @Column(name="VLR_VENDA", type="decimal", length=8, nullable=true)
     */
    protected $vlrVenda;

    /**
     * @var \DateTime
     * @Column(name="DTH", type="datetime", nullable=true)
     */
    protected $dth;

    /**
     * @param mixed $bairro
     */
    public function setBairro($bairro)
    {
        $this->bairro = $bairro;
    }

    /**
     * @return mixed
     */
    public function getBairro()
    {
        return $this->bairro;
    }

    /**
     * @param mixed $carga
     */
    public function setCarga($carga)
    {
        $this->carga = $carga;
    }

    /**
     * @return mixed
     */
    public function getCarga()
    {
        return $this->carga;
    }

    /**
     * @param mixed $cep
     */
    public function setCep($cep)
    {
        $this->cep = $cep;
    }

    /**
     * @return mixed
     */
    public function getCep()
    {
        return $this->cep;
    }

    /**
     * @param mixed $cidade
     */
    public function setCidade($cidade)
    {
        $this->cidade = $cidade;
    }

    /**
     * @return mixed
     */
    public function getCidade()
    {
        return $this->cidade;
    }

    /**
     * @param mixed $codCliente
     */
    public function setCodCliente($codCliente)
    {
        $this->codCliente = $codCliente;
    }

    /**
     * @return mixed
     */
    public function getCodCliente()
    {
        return $this->codCliente;
    }

    /**
     * @param mixed $codPraca
     */
    public function setCodPraca($codPraca)
    {
        $this->codPraca = $codPraca;
    }

    /**
     * @return mixed
     */
    public function getCodPraca()
    {
        return $this->codPraca;
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
     * @param mixed $codRota
     */
    public function setCodRota($codRota)
    {
        $this->codRota = $codRota;
    }

    /**
     * @return mixed
     */
    public function getCodRota()
    {
        return $this->codRota;
    }

    /**
     * @param mixed $complemento
     */
    public function setComplemento($complemento)
    {
        $this->complemento = $complemento;
    }

    /**
     * @return mixed
     */
    public function getComplemento()
    {
        return $this->complemento;
    }

    /**
     * @param mixed $cpfCnpj
     */
    public function setCpfCnpj($cpfCnpj)
    {
        $this->cpfCnpj = $cpfCnpj;
    }

    /**
     * @return mixed
     */
    public function getCpfCnpj()
    {
        return $this->cpfCnpj;
    }

    /**
     * @param mixed $dscPraca
     */
    public function setDscPraca($dscPraca)
    {
        $this->dscPraca = $dscPraca;
    }

    /**
     * @return mixed
     */
    public function getDscPraca()
    {
        return $this->dscPraca;
    }

    /**
     * @param mixed $dscRota
     */
    public function setDscRota($dscRota)
    {
        $this->dscRota = $dscRota;
    }

    /**
     * @return mixed
     */
    public function getDscRota()
    {
        return $this->dscRota;
    }

    /**
     * @param \DateTime $dth
     */
    public function setDth($dth)
    {
        $this->dth = $dth;
    }

    /**
     * @return \DateTime
     */
    public function getDth()
    {
        return $this->dth;
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
     * @param mixed $logradouro
     */
    public function setLogradouro($logradouro)
    {
        $this->logradouro = $logradouro;
    }

    /**
     * @return mixed
     */
    public function getLogradouro()
    {
        return $this->logradouro;
    }

    /**
     * @param mixed $nomeCliente
     */
    public function setNomeCliente($nomeCliente)
    {
        $this->nomeCliente = $nomeCliente;
    }

    /**
     * @return mixed
     */
    public function getNomeCliente()
    {
        return $this->nomeCliente;
    }

    /**
     * @param mixed $numero
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;
    }

    /**
     * @return mixed
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * @param mixed $pedido
     */
    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    /**
     * @return mixed
     */
    public function getPedido()
    {
        return $this->pedido;
    }

    /**
     * @param mixed $placa
     */
    public function setPlaca($placa)
    {
        $this->placa = $placa;
    }

    /**
     * @return mixed
     */
    public function getPlaca()
    {
        return $this->placa;
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
     * @param mixed $tipoPessoa
     */
    public function setTipoPessoa($tipoPessoa)
    {
        $this->tipoPessoa = $tipoPessoa;
    }

    /**
     * @return mixed
     */
    public function getTipoPessoa()
    {
        return $this->tipoPessoa;
    }

    /**
     * @param mixed $uf
     */
    public function setUf($uf)
    {
        $this->uf = $uf;
    }

    /**
     * @return mixed
     */
    public function getUf()
    {
        return $this->uf;
    }

    /**
     * @param mixed $vlrVenda
     */
    public function setVlrVenda($vlrVenda)
    {
        $this->vlrVenda = $vlrVenda;
    }

    /**
     * @return mixed
     */
    public function getVlrVenda()
    {
        return $this->vlrVenda;
    }

    /**
     * @param mixed $referencia
     */
    public function setReferencia($referencia)
    {
        $this->referencia = $referencia;
    }

    /**
     * @return mixed
     */
    public function getReferencia()
    {
        return $this->referencia;
    }

}