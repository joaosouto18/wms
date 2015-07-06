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
     * @Column(name="CODBARRAS", type="integer", nullable=false)
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
     * @Column(name="CODCARGAEXTERNO", type="integer", nullable=false)
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

}