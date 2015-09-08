<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="V_ETIQUETA_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\VEtiquetaSeparacaoRepository")
 */
class VEtiquetaSeparacao
{
    /**
     * @Column(name="CODBARRAS", type="bigint", nullable=false)
     * @id 
     */
    protected $codBarras;

    /**
     * @Column(name="EXPEDICAO", type="integer", nullable=false)
     */
    protected $codExpedicao;

    /**
     * Código do pedido
     * @Column(name="ENTREGA", type="integer", nullable=false)
     */
    protected $codEntrega;

    /**
     * @Column(name="CODTIPOPEDIDO", type="integer", nullable=false)
     */
    protected $codTipoPedido;

    /**
     * @Column(name="TIPOPEDIDO", type="string", nullable=false)
     */
    protected $tipoPedido;

    /**
     * @Column(name="CARGA", type="integer", nullable=false)
     */
    protected $codCarga;

    /**
     * @Column(name="CODTIPOCARGA", type="integer", nullable=false)
     */
    protected $codTipoCarga;

    /**
     * @Column(name="CODCARGAEXTERNO", type="string", nullable=false)
     */
    protected $codCargaExterno;

    /**
     * @Column(name="TIPOCARGA", type="string", nullable=false)
     */
    protected $tipoCarga;

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
     * @Column(name="CODPRODUTO", type="string", nullable=false)
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
     * @Column (name="PLACAEXPEDICAO", type="string" ,nullable=true)
     */
    protected $placaExpedicao;

    /**
     * @Column (name="PLACACARGA", type="string" ,nullable=true)
     */
    protected $placaCarga;

    /**
     * @Column(name="CODBARRASPRODUTO", type="string", nullable=false)
     */
    protected $codBarrasProduto;

    /**
     * @param mixed $cliente
     */
    public function setCliente($cliente)
    {
        $this->cliente = $cliente;
    }

    /**
     * @return mixed
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param mixed $codBarras
     */
    public function setCodBarras($codBarras)
    {
        $this->codBarras = $codBarras;
    }

    /**
     * @return mixed
     */
    public function getCodBarras()
    {
        return $this->codBarras;
    }

    /**
     * @param mixed $codBarrasProduto
     */
    public function setCodBarrasProduto($codBarrasProduto)
    {
        $this->codBarrasProduto = $codBarrasProduto;
    }

    /**
     * @return mixed
     */
    public function getCodBarrasProduto()
    {
        return $this->codBarrasProduto;
    }

    /**
     * @param mixed $codCarga
     */
    public function setCodCarga($codCarga)
    {
        $this->codCarga = $codCarga;
    }

    /**
     * @return mixed
     */
    public function getCodCarga()
    {
        return $this->codCarga;
    }

    /**
     * @param mixed $codCargaExterno
     */
    public function setCodCargaExterno($codCargaExterno)
    {
        $this->codCargaExterno = $codCargaExterno;
    }

    /**
     * @return mixed
     */
    public function getCodCargaExterno()
    {
        return $this->codCargaExterno;
    }

    /**
     * @param mixed $codClienteExterno
     */
    public function setCodClienteExterno($codClienteExterno)
    {
        $this->codClienteExterno = $codClienteExterno;
    }

    /**
     * @return mixed
     */
    public function getCodClienteExterno()
    {
        return $this->codClienteExterno;
    }

    /**
     * @param mixed $codEntrega
     */
    public function setCodEntrega($codEntrega)
    {
        $this->codEntrega = $codEntrega;
    }

    /**
     * @return mixed
     */
    public function getCodEntrega()
    {
        return $this->codEntrega;
    }

    /**
     * @param mixed $codEstoque
     */
    public function setCodEstoque($codEstoque)
    {
        $this->codEstoque = $codEstoque;
    }

    /**
     * @return mixed
     */
    public function getCodEstoque()
    {
        return $this->codEstoque;
    }

    /**
     * @param mixed $codExpedicao
     */
    public function setCodExpedicao($codExpedicao)
    {
        $this->codExpedicao = $codExpedicao;
    }

    /**
     * @return mixed
     */
    public function getCodExpedicao()
    {
        return $this->codExpedicao;
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
     * @param mixed $codStatus
     */
    public function setCodStatus($codStatus)
    {
        $this->codStatus = $codStatus;
    }

    /**
     * @return mixed
     */
    public function getCodStatus()
    {
        return $this->codStatus;
    }

    /**
     * @param mixed $codTipoCarga
     */
    public function setCodTipoCarga($codTipoCarga)
    {
        $this->codTipoCarga = $codTipoCarga;
    }

    /**
     * @return mixed
     */
    public function getCodTipoCarga()
    {
        return $this->codTipoCarga;
    }

    /**
     * @param mixed $codTipoPedido
     */
    public function setCodTipoPedido($codTipoPedido)
    {
        $this->codTipoPedido = $codTipoPedido;
    }

    /**
     * @return mixed
     */
    public function getCodTipoPedido()
    {
        return $this->codTipoPedido;
    }

    /**
     * @param mixed $dthConferencia
     */
    public function setDthConferencia($dthConferencia)
    {
        $this->dthConferencia = $dthConferencia;
    }

    /**
     * @return mixed
     */
    public function getDthConferencia()
    {
        return $this->dthConferencia;
    }

    /**
     * @param mixed $endereco
     */
    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    /**
     * @return mixed
     */
    public function getEndereco()
    {
        return $this->endereco;
    }

    /**
     * @param mixed $fornecedor
     */
    public function setFornecedor($fornecedor)
    {
        $this->fornecedor = $fornecedor;
    }

    /**
     * @return mixed
     */
    public function getFornecedor()
    {
        return $this->fornecedor;
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
     * @param mixed $itinerario
     */
    public function setItinerario($itinerario)
    {
        $this->itinerario = $itinerario;
    }

    /**
     * @return mixed
     */
    public function getItinerario()
    {
        return $this->itinerario;
    }

    /**
     * @param mixed $linhaEntrega
     */
    public function setLinhaEntrega($linhaEntrega)
    {
        $this->linhaEntrega = $linhaEntrega;
    }

    /**
     * @return mixed
     */
    public function getLinhaEntrega()
    {
        return $this->linhaEntrega;
    }

    /**
     * @param mixed $linhaSeparacao
     */
    public function setLinhaSeparacao($linhaSeparacao)
    {
        $this->linhaSeparacao = $linhaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getLinhaSeparacao()
    {
        return $this->linhaSeparacao;
    }

    /**
     * @param mixed $placaCarga
     */
    public function setPlacaCarga($placaCarga)
    {
        $this->placaCarga = $placaCarga;
    }

    /**
     * @return mixed
     */
    public function getPlacaCarga()
    {
        return $this->placaCarga;
    }

    /**
     * @param mixed $placaExpedicao
     */
    public function setPlacaExpedicao($placaExpedicao)
    {
        $this->placaExpedicao = $placaExpedicao;
    }

    /**
     * @return mixed
     */
    public function getPlacaExpedicao()
    {
        return $this->placaExpedicao;
    }

    /**
     * @param mixed $pontoTransbordo
     */
    public function setPontoTransbordo($pontoTransbordo)
    {
        $this->pontoTransbordo = $pontoTransbordo;
    }

    /**
     * @return mixed
     */
    public function getPontoTransbordo()
    {
        return $this->pontoTransbordo;
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
     * @param mixed $reimpressao
     */
    public function setReimpressao($reimpressao)
    {
        $this->reimpressao = $reimpressao;
    }

    /**
     * @return mixed
     */
    public function getReimpressao()
    {
        return $this->reimpressao;
    }

    /**
     * @param mixed $tipoCarga
     */
    public function setTipoCarga($tipoCarga)
    {
        $this->tipoCarga = $tipoCarga;
    }

    /**
     * @return mixed
     */
    public function getTipoCarga()
    {
        return $this->tipoCarga;
    }

    /**
     * @param mixed $tipoComercializacao
     */
    public function setTipoComercializacao($tipoComercializacao)
    {
        $this->tipoComercializacao = $tipoComercializacao;
    }

    /**
     * @return mixed
     */
    public function getTipoComercializacao()
    {
        return $this->tipoComercializacao;
    }

    /**
     * @param mixed $tipoPedido
     */
    public function setTipoPedido($tipoPedido)
    {
        $this->tipoPedido = $tipoPedido;
    }

    /**
     * @return mixed
     */
    public function getTipoPedido()
    {
        return $this->tipoPedido;
    }



}