<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 16:02
 */

namespace Wms\Domain\Entity\InventarioNovo;

/**
 * @Table(name="INVENTARIO_CONT_END_OS")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioContEndOsRepository")
 */
class InventarioContEndOs
{
    /**
     * @Column(name="COD_INVENT_CONT_END_OS", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_COD_INVENT_CONT_END_OS_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioContEnd")
     * @JoinColumn(name="COD_INV_CONT_END", referencedColumnName="COD_INV_CONT_END")
     */
    protected $invContEnd;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $codOs;


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
    public function getInvContEnd()
    {
        return $this->invContEnd;
    }

    /**
     * @param mixed $invContEnd
     */
    public function setInvContEnd($invContEnd)
    {
        $this->invContEnd = $invContEnd;
    }

    /**
     * @return mixed
     */
    public function getCodOs()
    {
        return $this->codOs;
    }

    /**
     * @param mixed $codOs
     */
    public function setCodOs($codOs)
    {
        $this->codOs = $codOs;
    }

}