<?php

namespace Wms\Domain\Entity\Sistema;

/**
 * Parametro
 *
 * @Table(name="PARAMETRO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\ParametroRepository")
 */
class Parametro
{

    /**
     * @Id
     * @Column(name="COD_PARAMETRO", type="smallint", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PARAMETRO_01", allocationSize=1, initialValue=1)
     * @var smallint $id
     */
    protected $id;

    /**
     * @var smallint $idContextoParametro
     *
     * @Column(name="COD_CONTEXTO_PARAMETRO", type="smallint", nullable=false)
     */
    protected $idContexto;

    /**
     * @var string $idTipoAtributo
     *
     * @Column(name="COD_TIPO_ATRIBUTO", type="string", length=1, nullable=true)
     */
    protected $idTipoAtributo;

    /**
     * @var string $descricao
     *
     * @Column(name="DSC_PARAMETRO", type="string", length=60, nullable=true)
     */
    protected $constante;

    /**
     * @var string $titulo
     *
     * @Column(name="DSC_TITULO_PARAMETRO", type="string", length=60, nullable=true)
     */
    protected $titulo;

    /**
     * @var Wms\Domain\Entity\Sistema\Parametro\Contexto $contextoParametro
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Sistema\Parametro\Contexto")
     * @JoinColumn(name="COD_CONTEXTO_PARAMETRO", referencedColumnName="COD_CONTEXTO_PARAMETRO") 
     */
    protected $contexto;
    
    /**
     * @var string $valor
     * @Column(name="DSC_VALOR_PARAMETRO", type="string", length=120, nullable=true)
     */
    protected $valor;

    /**
     * lista de tipos de atributos
     * @var array
     */
    public static $listaTipoAtributo = array(
        'A' => 'Alfanumérico',
        'D' => 'Data',
        'I' => 'Inteiro',
        'R' => 'Real',
    );

    public function getId()
    {
        return $this->id;
    }

    public function getIdContexto()
    {
        return $this->idContexto;
    }

    public function setIdContexto($idContexto)
    {
        $this->idContexto = $idContexto;
        return $this;
    }

    public function getIdTipoAtributo()
    {
        return $this->idTipoAtributo;
    }

    public function setIdTipoAtributo($idTipoAtributo)
    {
        $this->idTipoAtributo = $idTipoAtributo;
        return $this;
    }

    public function getConstante()
    {
        return $this->constante;
    }

    public function setConstante($constante)
    {
        $this->constante = mb_strtoupper($constante, 'UTF-8');
        return $this;
    }

    public function getTitulo()
    {
        return $this->titulo;
    }

    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function getContexto()
    {
        return $this->contexto;
    }

    public function setContexto($contexto)
    {
        $this->contexto = $contexto;
        return $this;
    }

    public function getValor()
    {
        return $this->valor;
    }

    public function setValor($valor)
    {
        $this->valor = $valor;
        return $this;
    }

}