<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Usuario,
    Wms\Domain\Entity\Expedicao;

/**
 * Andamento
 *
 * @Table(name="MOTIVO_CORTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MotivoCorteRepository")
 */
class MotivoCorte
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_MOTIVO_CORTE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MOTIVO_CORTE_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * Descricao do andamento
     *
     * @var string $dscObservacao
     * @Column(name="DSC_MOTIVO_CORTE", type="string", nullable=false)
     */
    protected $dscMotivo;

    /**
     * @Column(name="COD_EXTERNO", type="string")
     * @var integer
     */
    protected $codExterno;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDscMotivo()
    {
        return $this->dscMotivo;
    }

    /**
     * @param string $dscMotivo
     */
    public function setDscMotivo($dscMotivo)
    {
        $this->dscMotivo = $dscMotivo;
    }

    /**
     * @return int
     */
    public function getCodExterno()
    {
        return $this->codExterno;
    }

    /**
     * @param int $codExterno
     */
    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

}
