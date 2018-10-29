<?php

namespace Wms\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Parametro
 *
 * @Table(name="PARAMETRO")
 * @Entity(repositoryClass="Wms\Domain\Entity\RelatoriosSimplesRepository")
 */
class RelatoriosSimples
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



}