<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="EXPEDICAO_VOLUME_PATRIMONIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository")
 */
class ExpedicaoVolumePatrimonio
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_EXPEDICAO_VOLUME", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_EXP_VOLUME_PAT_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\VolumePatrimonio")
     * @JoinColumn(name="COD_VOLUME_PATRIMONIO", referencedColumnName="COD_VOLUME_PATRIMONIO")
     */
    protected $volumePatrimonio;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @Column(name="DTH_FECHAMENTO", type="datetime", nullable=true)
     */
    protected $dataFechamento;

    /**
     * @Column(name="DTH_CONFERIDO", type="datetime", nullable=true)
     */
    protected $dataConferencia;

    /**
     * @Column(name="COD_TIPO_VOLUME", type="integer")
     */
    protected $tipoVolume;

    /**
     * @var Wms\Domain\Entity\Usuario $usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @var Wms\Domain\Entity\Usuario $usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_USUARIO_LACRE", referencedColumnName="COD_USUARIO")
     */
    protected $usuarioLacre;

    /**
     * @Column(name="NUM_LACRE", type="string")
     * @var string
     */
    protected $lacre;

    /**
     * @Column(name="DTH_VINCULO_LACRE", type="datetime")
     */
    protected $dataVinculoLacre;

    /**
     * @param mixed $tipoVolume
     */
    public function setTipoVolume($tipoVolume)
    {
        $this->tipoVolume = $tipoVolume;
    }

    /**
     * @return mixed
     */
    public function getTipoVolume()
    {
        return $this->tipoVolume;
    }

    public function setDataConferencia($dataConferencia)
    {
        $this->dataConferencia = $dataConferencia;
    }

    public function getDataConferencia()
    {
        return $this->dataConferencia;
    }

    public function setDataFechamento($dataFechamento)
    {
        $this->dataFechamento = $dataFechamento;
    }

    public function getDataFechamento()
    {
        return $this->dataFechamento;
    }

    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    public function getExpedicao()
    {
        return $this->expedicao;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setVolumePatrimonio($volumePatrimonio)
    {
        $this->volumePatrimonio = $volumePatrimonio;
    }

    public function getVolumePatrimonio()
    {
        return $this->volumePatrimonio;
    }

    /**
     * @return Wms\Domain\Entity\Usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param Wms\Domain\Entity\Usuario $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return string
     */
    public function getLacre()
    {
        return $this->lacre;
    }

    /**
     * @param string $lacre
     */
    public function setLacre($lacre)
    {
        $this->lacre = $lacre;
    }

    /**
     * @return mixed
     */
    public function getDataVinculoLacre()
    {
        return $this->dataVinculoLacre;
    }

    /**
     * @param mixed $dataVinculoLacre
     */
    public function setDataVinculoLacre($dataVinculoLacre)
    {
        $this->dataVinculoLacre = $dataVinculoLacre;
    }

    /**
     * @return Wms\Domain\Entity\Usuario
     */
    public function getUsuarioLacre()
    {
        return $this->usuarioLacre;
    }

    /**
     * @param Wms\Domain\Entity\Usuario $usuarioLacre
     */
    public function setUsuarioLacre($usuarioLacre)
    {
        $this->usuarioLacre = $usuarioLacre;
    }

}