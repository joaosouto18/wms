<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Pessoa;

/**
 *
 * @Table(name="MAPA_SEPARACAO_EMB_CLIENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository")
 */
class MapaSeparacaoEmbalado
{
    const CONFERENCIA_EMBALADO_INICIADO = 567;
    const CONFERENCIA_EMBALADO_FINALIZADO = 569;
    const CONFERENCIA_EMBALADO_FECHADO_FINALIZADO = 570;

    /**
     * @Id
     * @Column(name="COD_MAPA_SEPARACAO_EMB_CLIENTE", type="integer", nullable=false)
     */
    // * @GeneratedValue(strategy="SEQUENCE")
    // * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_EMBALADO_01", initialValue=1, allocationSize=1)
    protected $id;

    /**
     * @var Pessoa
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @var MapaSeparacao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacao")
     * @JoinColumn(name="COD_MAPA_SEPARACAO", referencedColumnName="COD_MAPA_SEPARACAO")
     */
    protected $mapaSeparacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="NUM_SEQUENCIA", type="string", nullable=false)
     */
    protected $sequencia;

    /**
     * @Column(name="IND_ULTIMO_VOLUME", type="string", nullable=true)
     */
    protected $ultimoVolume;

    /**
     * @var integer
     *
     * @Column(name="POS_VOLUME", type="integer", nullable=true)
     */
    protected $posVolume;

    /**
     * @var integer
     *
     * @Column(name="POS_ENTREGA", type="integer", nullable=true)
     */
    protected $posEntrega;

    /**
     * @var integer
     *
     * @Column(name="TOTAL_ENTREGA", type="integer", nullable=true)
     */
    protected $totalEntrega;

    /**
     * @var OrdemServico
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $os;

    /**
     * @Column(name="DTH_CONF_CHECKOUT", type="datetime", nullable=true)
     */
    protected $dataConferenciaCheckout;

    /**
     * @Column(name="COD_USUARIO_CONFERENCIA", type="integer", nullable=true)
     */
    protected $conferente;

    /**
     * Define o id da embalagem
     * @param $em EntityManager
     * @return MapaSeparacaoEmbalado
     *
     */
    public function generateId(EntityManager $em) {
        $sqcGenerator = new SequenceGenerator("SQ_MAPA_SEPARACAO_EMBALADO_01", 1);
        $this->id = "14".$sqcGenerator->generate($em, $this);
        return $this;
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
     * @return Pessoa
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    /**
     * @param mixed $pessoa
     */
    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
    }

    /**
     * @return MapaSeparacao
     */
    public function getMapaSeparacao()
    {
        return $this->mapaSeparacao;
    }

    /**
     * @param mixed $mapaSeparacao
     */
    public function setMapaSeparacao($mapaSeparacao)
    {
        $this->mapaSeparacao = $mapaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

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
    public function getUltimoVolume()
    {
        return $this->ultimoVolume;
    }

    /**
     * @param mixed $ultimoVolume
     */
    public function setUltimoVolume($ultimoVolume)
    {
        $this->ultimoVolume = $ultimoVolume;
    }

    /**
     * @return int
     */
    public function getPosVolume()
    {
        return $this->posVolume;
    }

    /**
     * @param int $posVolume
     */
    public function setPosVolume($posVolume)
    {
        $this->posVolume = $posVolume;
    }

    /**
     * @return OrdemServico
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param OrdemServico $os
     */
    public function setOs($os)
    {
        $this->os = $os;
    }

    /**
     * @return int
     */
    public function getPosEntrega()
    {
        return $this->posEntrega;
    }

    /**
     * @param int $posEntrega
     */
    public function setPosEntrega($posEntrega)
    {
        $this->posEntrega = $posEntrega;
    }

    /**
     * @return int
     */
    public function getTotalEntrega()
    {
        return $this->totalEntrega;
    }

    /**
     * @param int $totalEntrega
     */
    public function setTotalEntrega($totalEntrega)
    {
        $this->totalEntrega = $totalEntrega;
    }

    /**
     * @return mixed
     */
    public function getDataConferenciaCheckout()
    {
        return $this->dataConferenciaCheckout;
    }

    /**
     * @param mixed $dataConferenciaCheckout
     */
    public function setDataConferenciaCheckout($dataConferenciaCheckout)
    {
        $this->dataConferenciaCheckout = $dataConferenciaCheckout;
    }

    /**
     * @return mixed
     */
    public function getConferente()
    {
        return $this->conferente;
    }

    /**
     * @param mixed $conferente
     */
    public function setConferente($conferente)
    {
        $this->conferente = $conferente;
    }
}