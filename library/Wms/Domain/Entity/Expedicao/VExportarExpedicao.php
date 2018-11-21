<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="V_EXPORTAR_EXPEDICAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\VExportarExpedicaoRepository")
 */
class VExportarExpedicao
{
    /**
     * @Column(name="EXPEDICAO", type="integer", nullable=false)
     * @id
     */
    protected $codExpedicao;

    /**Placa da expedição
     * @Column(name="PLACAEXPEDICAO", type="integer", nullable=false)
     */
    protected $placaExpedicao;

    /**
     * Data inicio da expedição
     * @Column(name="DATAINICIOEXPEDICAO", type="string", nullable=false)
     */
    protected $dthinicio_expedicao;

    /**
     * Data final da expedição
     * @Column(name="DATAFINALEXPEDICAO", type="string", nullable=false)
     */
    protected $dthfinal_expedicao;

    /**
     * @Column(name="TIPOPEDIDO", type="string", nullable=false)
     */
    protected $tipoPedido;

    /**
     * Código da carga
     * @Column(name="CARGA", type="integer", nullable=false)
     */
    protected $codCarga;

    /**
     * @Column(name="CODTIPOCARGA", type="integer", nullable=false)
     */
    protected $codTipoCarga;

    /**
     * @Column(name="CODCARGAEXTERNO", type="integer", nullable=false)
     */
    protected $codCargaExterno;

    /**
     * Placa da carga
     * @Column(name="PLACACARGA", type="string", nullable=false)
     */
    protected $placaCarga;



    /**
     * @Column(name="LINHAENTREGA", type="string", nullable=false)
     */
    protected $linhaEntrega;

    /**
     * @Column(name="ITINERARIO", type="string", nullable=false)
     */
    protected $itinerario;

    /**
     * @Column(name="CODCLIENTEEXTERNO", type="string", nullable=false)
     */
    protected $codClienteExterno;

    /**
     * @Column(name="CLIENTE", type="string", nullable=false)
     */
    protected $cliente;

    /**
     * @Column(name="CODPRODUTO", type="integer", nullable=false)
     */
    protected $codProduto;

    /**
     * @Column(name="PRODUTO", type="string", nullable=false)
     */
    protected $produto;

    /**
     * @Column(name="GRADE", type="string", nullable=false)
     */
    protected $grade;

    /**
     * Faz join com a tabela de fabricante
     *
     * @Column(name="FORNECEDOR", type="string", nullable=false)
     */
    protected $fornecedor;

    /**
     * @Column(name="TIPOCOMERCIALIZACAO", type="string", nullable=false)
     */
    protected $tipoComercializacao;

    /**
     * Descrição do endereço do deposito
     * @Column(name="ENDERECO", type="string", nullable=false)
     */
    protected $endereco;

    /**
     * @Column(name="LINHASEPARACAO", type="string", nullable=false)
     */
    protected $linhaSeparacao;

    /**
     * @Column(name="ESTOQUE", type="integer", nullable=false)
     */
    protected $codEstoque;

    /**
     * @Column(name="PONTOTRANSBORDO", type="integer", nullable=false)
     */
    protected $pontoTransbordo;

    /**
     * @Column (name="STATUS", type="integer",nullable=false)
     */
    protected $codStatus;

    /**
     * @Column (name="DTHCONFERENCIA", type="string" ,nullable=true)
     */
    protected $dthConferencia;

    /**
     * @Column (name="REIMPRESSAO", type="string" ,nullable=true)
     */
    protected $reimpressao;

      /**
     * @Column(name="CODBARRASPRODUTO", type="string", nullable=false)
     */
    protected $codBarrasProduto;

    public function setCodBarras($codBarras)
    {
        $this->codBarras = $codBarras;
    }

    public function getCodBarras()
    {
        return $this->codBarras;
    }

    public function setCliente($cliente)
    {
        $this->cliente = $cliente;
    }

    public function getCliente()
    {
        return $this->cliente;
    }

    public function setCodCarga($codCarga)
    {
        $this->codCarga = $codCarga;
    }

    public function getCodCarga()
    {
        return $this->codCarga;
    }

    public function setCodEntrega($codEntrega)
    {
        $this->codEntrega = $codEntrega;
    }

    public function getCodEntrega()
    {
        return $this->codEntrega;
    }

    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

    public function getCodExpedicao()
    {
        return $this->codExpedicao;
    }

    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    public function getCodProduto()
    {
        return $this->codProduto;
    }

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setEstoque($estoque)
    {
        $this->estoque = $estoque;
    }

    public function getEstoque()
    {
        return $this->estoque;
    }

    public function setFornecedor($fornecedor)
    {
        $this->fornecedor = $fornecedor;
    }

    public function getFornecedor()
    {
        return $this->fornecedor;
    }

    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setItinerario($itinerario)
    {
        $this->itinerario = $itinerario;
    }

    public function getItinerario()
    {
        return $this->itinerario;
    }

    public function setCodStatus($codStatus)
    {
        $this->codStatus = $codStatus;
    }

    public function getCodStatus()
    {
        return $this->codStatus;
    }

    public function setLinhaEntrega($linhaEntrega)
    {
        $this->linhaEntrega = $linhaEntrega;
    }

    public function getLinhaEntrega()
    {
        return $this->linhaEntrega;
    }

    public function setLinhaSeparacao($linhaSeparacao)
    {
        $this->linhaSeparacao = $linhaSeparacao;
    }

    public function getLinhaSeparacao()
    {
        return $this->linhaSeparacao;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setTipoComercializacao($tipoComercializacao)
    {
        $this->tipoComercializacao = $tipoComercializacao;
    }

    public function getTipoComercializacao()
    {
        return $this->tipoComercializacao;
    }

}