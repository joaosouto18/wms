<?php

namespace Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;

/**
 * @Table(name="PEDIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\PedidoRepository")
 */
class Pedido
{
    /**
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PEDIDO_01", allocationSize=1, initialValue=1)
     * @Column(name="COD_PEDIDO", type="string", nullable=false)
     * @Id
     */
    protected $id;

    /**
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    /**
     * @Column(name="NUM_SEQUENCIAL", type="integer", nullable=false)
     */
    protected $numSequencial;

    /**
     * @var TipoPedido
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\TipoPedido")
     * @JoinColumn(name="COD_TIPO_PEDIDO", referencedColumnName="COD_TIPO_PEDIDO_EXPEDICAO")
     */
    protected $tipoPedido;

    /**
     * @Column(name="COD_TIPO_PEDIDO", type="integer", nullable=false)
     */
    protected $codTipoPedido;

    /**
     * @Column(name="DSC_LINHA_ENTREGA", type="string",nullable=false)
     */
    protected $linhaEntrega;

    /**
     * Central de Entrega equivale ao código do CD
     * @Column(name="CENTRAL_ENTREGA", type="string", nullable=false)
     */
    protected $centralEntrega;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Itinerario")
     * @JoinColumn(name="COD_ITINERARIO", referencedColumnName="COD_ITINERARIO")
     */
    protected $itinerario;

    /**
     * @Column(name="COD_CARGA", type="integer", nullable=false)
     */
    protected $codCarga;

    /**
     * @Column(name="PONTO_TRANSBORDO", type="string", nullable=false)
     */
    protected $pontoTransbordo;

    /**
     * @Column(name="ENVIO_PARA_LOJA", type="integer", nullable=false)
     */
    protected $envioParaLoja;

    /**
     * @Column(name="CONFERIDO", type="integer", nullable=false)
     */
    protected $conferido;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\Carga")
     * @JoinColumn(name="COD_CARGA", referencedColumnName="COD_CARGA")
     */
    protected $carga;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Papel\Cliente")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @Column(name="COD_PESSOA_PROPRIETARIO", type="integer", nullable=true)
     */
    protected $proprietario;

    /**
     * @Column(name="SEQUENCIA", type="integer", nullable=false)
     */
    protected $sequencia;

    /**
     * @Column(name="DTH_CANCELAMENTO", type="datetime", nullable=true)
     */
    protected $dataCancelamento;

    /**
     * @Column(name="IND_ETIQUETA_MAPA_GERADO", type="string", nullable=true)
     */
    protected $indEtiquetaMapaGerado;

    /**
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=true)
     */
    protected $observacao;

    /**
     * @Column(name="IND_FATURADO", type="string", nullable=false)
     */
    protected $faturado;

    /**
     * @param mixed $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

    /**
     * @return mixed
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

    public function setTipoPedido($tipoPedido)
    {
        $this->tipoPedido = $tipoPedido;
    }

    public function getTipoPedido()
    {
        return $this->tipoPedido;
    }

    public function setCarga($carga)
    {
        $this->carga = $carga;
    }

    /**
     * @return Carga
     */
    public function getCarga()
    {
        return $this->carga;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getCodExterno()
    {
        return $this->codExterno;
    }

    /**
     * @param mixed $codExterno
     */
    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

    /**
     * @return mixed
     */
    public function getNumSequencial()
    {
        return $this->numSequencial;
    }

    /**
     * @param mixed $numSequencial
     */
    public function setNumSequencial($numSequencial)
    {
        $this->numSequencial = $numSequencial;
    }

    /**
     * @return mixed
     */
    public function getPedido()
    {
        if(!empty($this->numSequencial)) {
            return $this->codExterno . ' - ' . $this->numSequencial;
        }else{
            return $this->codExterno;
        }
    }

    public function setItinerario($itinerario)
    {
        $this->itinerario = $itinerario;
    }

    /**
     * @return Itinerario
     */
    public function getItinerario()
    {
        return $this->itinerario;
    }

    public function setLinhaEntrega($linhaEntrega)
    {
        $this->linhaEntrega = $linhaEntrega;
    }

    /**
     * @return string
     */
    public function getLinhaEntrega()
    {
        return $this->linhaEntrega;
    }

    /**
     * @return Cliente
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
    }

    public function getProprietario()
    {
        return $this->proprietario;
    }

    public function setProprietario($proprietario)
    {
        $this->proprietario = $proprietario;
    }

    public function setCodCarga($codCarga)
    {
        $this->codCarga = $codCarga;
    }

    public function getCodCarga()
    {
        return $this->codCarga;
    }

    public function setDataCancelamento($dataCancelamento)
    {
        $this->dataCancelamento = $dataCancelamento;
    }

    public function getDataCancelamento()
    {
        return $this->dataCancelamento;
    }

    public function setCentralEntrega($centralEntrega)
    {
        $this->centralEntrega = $centralEntrega;
    }

    public function getCentralEntrega()
    {
        return $this->centralEntrega;
    }

    public function setConferido($conferido)
    {
        $this->conferido = $conferido;
    }

    public function getConferido()
    {
        return $this->conferido;
    }

    public function setEnvioParaLoja($envioParaLoja)
    {
        $this->envioParaLoja = $envioParaLoja;
    }

    public function getEnvioParaLoja()
    {
        return $this->envioParaLoja;
    }

    public function setPontoTransbordo($pontoTransbordo)
    {
        $this->pontoTransbordo = $pontoTransbordo;
    }

    public function getPontoTransbordo()
    {
        return $this->pontoTransbordo;
    }

    /**
     * @param mixed $indEtiquetaMapaGerado
     */
    public function setIndEtiquetaMapaGerado($indEtiquetaMapaGerado)
    {
        $this->indEtiquetaMapaGerado = $indEtiquetaMapaGerado;
    }

    /**
     * @return mixed
     */
    public function getIndEtiquetaMapaGerado()
    {
        return $this->indEtiquetaMapaGerado;
    }

    /**
     * @return mixed
     */
    public function getCodTipoPedido()
    {
        return $this->codTipoPedido;
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
    public function getObservacao()
    {
        return $this->observacao;
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
    public function getFaturado()
    {
        return $this->faturado;
    }

    /**
     * @param mixed $faturado
     */
    public function setFaturado($faturado)
    {
        $this->faturado = $faturado;
    }

}