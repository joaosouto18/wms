<?php

namespace Wms\Domain\Entity\Enderecamento;

use Wms\Domain\Entity\Filial;
use Wms\Domain\Entity\NotaFiscal;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Recebimento;
use Wms\Domain\Entity\Usuario;

/**
 *
 * @Table(name="RESERVA_ESTOQUE_PROPRIETARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Enderecamento\ReservaEstoqueProprietarioRepository")
 */
class ReservaEstoqueProprietario
{

    /**
     * @var integer
     * @Column(name="COD_RESERVA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RES_ESTQ_PROP_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string
     * @Column(name="COD_PRODUTO", type="string", nullable=false)
     */
    protected $codProduto;

    /**
     * @var string
     * @Column(name="DSC_GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * @var Produto
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @var float
     * @Column(name="QTD", type="decimal", nullable=false)
     */
    protected $qtd;

    /**
     * @var Filial
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Filial")
     * @JoinColumn(name="COD_PROPRIETARIO", referencedColumnName="COD_FILIAL")
     */
    protected $proprietario;

    /**
     * @var Recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO")
     */
    protected $recebimento;

    /**
     * @var NotaFiscal
     * @ManyToOne(targetEntity="Wms\Domain\Entity\NotaFiscal")
     * @JoinColumn(name="COD_NOTA_FISCAL", referencedColumnName="COD_NOTA_FISCAL")
     */
    protected $notaFiscal;

    /**
     * @var string
     * @Column(name="IND_APLICADO", type="string", length=1, nullable=false)
     */
    protected $indAplicado;

    /**
     * @var \DateTime
     * @Column(name="DTH_RESERVA", type="datetime", nullable=false)
     */
    protected $dthReserva;

    /**
     * @var \DateTime
     * @Column(name="DTH_APLICACAO", type="datetime", nullable=true)
     */
    protected $dthAplicacao;

    /**
     * @var Usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO_APLICACAO", referencedColumnName="COD_USUARIO")
     */
    protected $usuarioAplicacao;

    public function __construct()
    {
        self::setDthReserva();
        self::setIndAplicado();
    }

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
     * @return string
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param string $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return Produto
     */
    public function getProduto()
    {
        return $this->produto;
    }

    /**
     * @param Produto $produto
     */
    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    /**
     * @return float
     */
    public function getQtd()
    {
        return $this->qtd;
    }

    /**
     * @param float $qtd
     */
    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    /**
     * @return Filial
     */
    public function getProprietario()
    {
        return $this->proprietario;
    }

    /**
     * @param Filial $proprietario
     */
    public function setProprietario($proprietario)
    {
        $this->proprietario = $proprietario;
    }

    /**
     * @return Recebimento
     */
    public function getRecebimento()
    {
        return $this->recebimento;
    }

    /**
     * @param Recebimento $recebimento
     */
    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
    }

    /**
     * @return NotaFiscal
     */
    public function getNotaFiscal()
    {
        return $this->notaFiscal;
    }

    /**
     * @param NotaFiscal $notaFiscal
     */
    public function setNotaFiscal($notaFiscal)
    {
        $this->notaFiscal = $notaFiscal;
    }

    /**
     * @return string
     */
    public function getIndAplicado()
    {
        return $this->indAplicado;
    }

    /**
     * @param string|bool $indAplicado
     */
    public function setIndAplicado($indAplicado = false)
    {
        $this->indAplicado = (is_string($indAplicado))? $indAplicado: (is_bool($indAplicado) && $indAplicado) ? 'S': 'N';
    }

    /**
     * @return bool
     */
    public function isAplicado()
    {
        return ($this->indAplicado === 'S');
    }

    /**
     * @return \DateTime
     */
    public function getDthReserva()
    {
        return $this->dthReserva;
    }

    private function setDthReserva()
    {
        $this->dthReserva = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getDthAplicacao()
    {
        return $this->dthAplicacao;
    }

    /**
     * @param \DateTime $dthAplicacao
     */
    public function setDthAplicacao($dthAplicacao)
    {
        $this->dthAplicacao = $dthAplicacao;
    }

    /**
     * @return Usuario
     */
    public function getUsuarioAplicacao()
    {
        return $this->usuarioAplicacao;
    }

    /**
     * @param Usuario $usuarioAplicacao
     */
    public function setUsuarioAplicacao($usuarioAplicacao)
    {
        $this->usuarioAplicacao = $usuarioAplicacao;
    }
}