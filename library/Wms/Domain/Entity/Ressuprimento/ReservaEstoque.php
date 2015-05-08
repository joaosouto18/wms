<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="RESERVA_ESTOQUE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository")
 */
class ReservaEstoque
{

    /**
     * @Id
     * @Column(name="COD_RESERVA_ESTOQUE", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RESERVA_ESTOQUE", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $endereco;

    /**
     * Tipo de Reserva (E - Entrada, S - Saida)
     * @Column(name="TIPO_RESERVA", type="string", nullable=false)
     */
    protected $tipoReserva;

    /**
     * Indicativo para falar se já foi efetivada ou não (S - Sim, N - Não)
     * @Column(name="IND_ATENDIDA", type="string", nullable=false)
     */
    protected $atendida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO_ATENDIMENTO", referencedColumnName="COD_USUARIO")
     */
    protected $usuarioAtendimento;

    /**
     * @Column(name="DTH_RESERVA", type="datetime", nullable=false)
     */
    protected $dataReserva;

    /**
     * @Column(name="DTH_ATENDIMENTO", type="datetime", nullable=false)
     */
    protected $dataAtendimento;

    /**
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=false)
     */
    protected $dscObservacao;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Ressuprimento\ReservaEstoqueProduto", mappedBy="reservaEstoque", cascade={"persist", "remove"})
     * @var ArrayCollection volumes que compoem este produto
     */
    protected $produtos;

    public function setAtendida($atendida)
    {
        $this->atendida = $atendida;
    }

    public function getAtendida()
    {
        return $this->atendida;
    }

    public function setDataAtendimento($dataAtendimento)
    {
        $this->dataAtendimento = $dataAtendimento;
    }

    public function getDataAtendimento()
    {
        return $this->dataAtendimento;
    }

    public function setDataReserva($dataReserva)
    {
        $this->dataReserva = $dataReserva;
    }

    public function getDataReserva()
    {
        return $this->dataReserva;
    }

    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
    }

    public function getDscObservacao()
    {
        return $this->dscObservacao;
    }

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTipoReserva($tipoReserva)
    {
        $this->tipoReserva = $tipoReserva;
    }

    public function getTipoReserva()
    {
        return $this->tipoReserva;
    }

    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }

    public function setUsuarioAtendimento($usuarioAtendimento)
    {
        $this->usuarioAtendimento = $usuarioAtendimento;
    }

    public function getUsuarioAtendimento()
    {
        return $this->usuarioAtendimento;
    }

    /**
     * @param \Wms\Domain\Entity\Ressuprimento\ArrayCollection $produtos
     */
    public function setProdutos($produtos)
    {
        $this->produtos = $produtos;
    }

    /**
     * @return \Wms\Domain\Entity\Ressuprimento\ArrayCollection
     */
    public function getProdutos()
    {
        return $this->produtos;
    }

}
