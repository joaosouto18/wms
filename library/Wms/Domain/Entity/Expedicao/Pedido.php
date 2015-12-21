<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 * @Table(name="PEDIDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\PedidoRepository")
 */
class Pedido
{
    /**
     * @Column(name="COD_PEDIDO", type="integer", nullable=false)
     * @Id
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_TIPO_PEDIDO", referencedColumnName="COD_SIGLA")
     */
    protected $tipoPedido;

    /**
     * @Column(name="DSC_LINHA_ENTREGA", type="string",nullable=false)
     */
    protected $linhaEntrega;

    /**
     * Central de Entrega equivale ao código do CD
     * @Column(name="CENTRAL_ENTREGA", type="integer", nullable=false)
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
     * @Column(name="PONTO_TRANSBORDO", type="integer", nullable=false)
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

    public function getCarga()
    {
        return $this->carga;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setItinerario($itinerario)
    {
        $this->itinerario = $itinerario;
    }

    public function getItinerario()
    {
        return $this->itinerario;
    }

    public function setLinhaEntrega($linhaEntrega)
    {
        $this->linhaEntrega = $linhaEntrega;
    }

    public function getLinhaEntrega()
    {
        return $this->linhaEntrega;
    }

    public function getPessoa()
    {
        return $this->pessoa;
    }

    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
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
        /*if ($pontoTransbordo == 1) {
            //COMENTAR ESTA LINHA SE FOR IMPLANTAR EM OUTRO CLIENTE
            //VALIDO APENAS PARA SIMONETTI
            $pontoTransbordo = 104;
        }*/
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

}