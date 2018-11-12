<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 29/06/2017
 * Time: 14:52
 */

namespace Wms\Domain\Entity\Integracao;


/**
 * @Table(name="ACAO_INTEGRACAO_FILTRO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltroRepository")
 */
class AcaoIntegracaoFiltro
{

    const DATA_ESPECIFICA   = 610;
    const CODIGO_ESPECIFICO = 611;
    const CONJUNTO_CODIGO   = 612;
    const INTERVALO_CODIGO  = 613;

    /**
     * @Id
     * @Column(name="COD_ACAO_INTEGRACAO_FILTRO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ACAO_INTEGRACAO_FILTRO_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Integracao\AcaoIntegracao")
     * @JoinColumn(name="COD_ACAO_INTEGRACAO", referencedColumnName="COD_ACAO_INTEGRACAO")
     */
    protected $acaoIntegracao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_TIPO_REGISTRO", referencedColumnName="COD_SIGLA")
     */
    protected $tipoRegistro;

    /**
     * @Column(name="DSC_FILTRO", type="string", nullable=true)
     * @var string
     */
    protected $filtro;

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
    public function getAcaoIntegracao()
    {
        return $this->acaoIntegracao;
    }

    /**
     * @param mixed $acaoIntegracao
     */
    public function setAcaoIntegracao($acaoIntegracao)
    {
        $this->acaoIntegracao = $acaoIntegracao;
    }

    /**
     * @return mixed
     */
    public function getTipoRegistro()
    {
        return $this->tipoRegistro;
    }

    /**
     * @param mixed $tipoRegistro
     */
    public function setTipoRegistro($tipoRegistro)
    {
        $this->tipoRegistro = $tipoRegistro;
    }

    /**
     * @return mixed
     */
    public function getFiltro()
    {
        return $this->filtro;
    }

    /**
     * @param mixed $filtro
     */
    public function setFiltro($filtro)
    {
        $this->filtro = $filtro;
    }

}