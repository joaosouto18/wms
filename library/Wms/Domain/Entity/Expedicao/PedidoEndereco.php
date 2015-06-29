<?php
namespace Wms\Domain\Entity\Expedicao;

/**
 * PedidoEndereco
 *
 * @Table(name="PEDIDO_ENDERECO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\PedidoEnderecoRepository")
 */
class PedidoEndereco
{

    /**
     * @var integer $id
     * @Column(name="COD_PEDIDO_ENDERECO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PEDIDO_ENDERECO_01", initialValue=1, allocationSize=1)
     */
    protected $id;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Pedido")
     * @JoinColumn(name="COD_PEDIDO", referencedColumnName="COD_PEDIDO")
     */
    protected $pedido;
    /**
     * @var integer $idTipo
     * @Column(name="COD_TIPO_ENDERECO", type="integer", nullable=false)
     */
    protected $idTipo;
    /**
     * @var integer $idLocalidade
     * @Column(name="COD_LOCALIDADE", type="integer", nullable=true)
     */
    protected $idLocalidade;
    /**
     * @var string $complemento
     * @Column(name="DSC_COMPLEMENTO", type="string", length=36, nullable=true)
     */
    protected $complemento;
    /**
     * @var string $descricao
     * @Column(name="DSC_ENDERECO", type="string", length=72, nullable=true)
     */
    protected $descricao;
    /**
     * @var string $pontoReferencia
     * @Column(name="DSC_PONTO_REFERENCIA", type="string", length=255, nullable=true)
     */
    protected $pontoReferencia;
    /**
     * @var string $isEct
     * @Column(name="IND_ENDERECO_ECT", type="string", length=1, nullable=true)
     */
    protected $isEct;
    /**
     * @var string $bairro
     * @Column(name="NOM_BAIRRO", type="string", length=72, nullable=true)
     */
    protected $bairro;
    /**
     * @var string $localidade
     * @Column(name="NOM_LOCALIDADE", type="string", length=72, nullable=true)
     */
    protected $localidade;
    /**
     * @var string $cep
     * @Column(name="NUM_CEP", type="string", length=10, nullable=true)
     */
    protected $cep;
    /**
     * @var string $numero
     * @Column(name="NUM_ENDERECO", type="string", length=6, nullable=true)
     */
    protected $numero;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_UF", referencedColumnName="COD_SIGLA")
     */
    protected $uf;
    /**
     * @var integer $idUf
     * @Column(name="COD_UF", type="integer", nullable=false)
     */
    protected $idUf;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Endereco\Tipo")
     * @JoinColumn(name="COD_TIPO_ENDERECO", referencedColumnName="COD_SIGLA")
     */
    protected $tipo;

    public function getId()
    {
	return $this->id;
    }

    public function getIdPessoa()
    {
	return $this->idPessoa;
    }

    public function setIdPessoa($idPessoa)
    {
	$this->idPessoa = $idPessoa;
        return $this;
    }

    public function getIdLocalidade()
    {
	return $this->idLocalidade;
    }

    public function setIdLocalidade($idLocalidade)
    {
	$this->idLocalidade = $idLocalidade;
        return $this;
    }

    public function getComplemento()
    {
	return $this->complemento;
    }

    public function setComplemento($complemento)
    {
	$this->complemento = mb_strtoupper($complemento, 'UTF-8');
        return $this;
    }

    public function getDescricao()
    {
	return $this->descricao;
    }

    public function setDescricao($descricao)
    {
	$this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

    public function getPontoReferencia()
    {
	return $this->pontoReferencia;
    }

    public function setPontoReferencia($pontoReferencia)
    {
	$this->pontoReferencia = mb_strtoupper($pontoReferencia, 'UTF-8');
        return $this;
    }

    public function getIsEct()
    {
	return $this->isEct;
    }

    public function setIsEct($isEct)
    {
	$this->isEct = $isEct;
        return $this;
    }

    public function getBairro()
    {
	return $this->bairro;
    }

    public function setBairro($bairro)
    {
	$this->bairro = mb_strtoupper($bairro, 'UTF-8');
        return $this;
    }

    public function getLocalidade()
    {
	return $this->localidade;
    }

    public function setLocalidade($localidade)
    {
	$this->localidade = mb_strtoupper($localidade, 'UTF-8');
        return $this;
    }

    public function getCep()
    {
	return $this->cep;
    }

    public function setCep($cep)
    {
	$this->cep = $cep;
        return $this;
    }

    public function getNumero()
    {
	return $this->numero;
    }

    public function setNumero($numero)
    {
	$this->numero = $numero;
        return $this;
    }

    public function getUf()
    {
	return $this->uf;
    }

    public function getIdUf()
    {
	return $this->idUf;
    }

    public function getTipo()
    {
	return $this->tipo;
    }

    public function setTipo($tipo)
    {
	$this->tipo = $tipo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPedido()
    {
        return $this->pedido;
    }

    /**
     * @param mixed $pedido
     */
    public function setPedido($pedido)
    {
        $this->pedido = $pedido;
    }

    public function setUf($uf)
    {
	$this->uf = $uf;
        return $this;
    }

    /**
     * @param int $idTipo
     */
    public function setIdTipo($idTipo)
    {
        $this->idTipo = $idTipo;
    }

    /**
     * @return int
     */
    public function getIdTipo()
    {
        return $this->idTipo;
    }

}