<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 23/11/2018
 * Time: 16:02
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Wms\Domain\Entity\OrdemServico;

/**
 * @Table(name="INVENTARIO_CONT_END_OS")
 * @Entity(repositoryClass="Wms\Domain\Entity\InventarioNovo\InventarioContEndOsRepository")
 */
class InventarioContEndOs
{
    /**
     * @Column(name="COD_INV_CONT_END_OS", type="integer", length=8, nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_N_INV_CONT_END_OS_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var InventarioContEnd $invContEnd
     * @ManyToOne(targetEntity="Wms\Domain\Entity\InventarioNovo\InventarioContEnd")
     * @JoinColumn(name="COD_INV_CONT_END", referencedColumnName="COD_INV_CONT_END")
     */
    protected $invContEnd;

    /**
     * @var OrdemServico $codOs
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $ordemServico;

    /**
     * @var bool
     * @Column(name="IND_ATIVO", type=int, size=1)
     */
    protected $indAtivo;

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
     * @return InventarioContEnd
     */
    public function getInvContEnd()
    {
        return $this->invContEnd;
    }

    /**
     * @param InventarioContEnd $invContEnd
     */
    public function setInvContEnd($invContEnd)
    {
        $this->invContEnd = $invContEnd;
    }

    /**
     * @return OrdemServico
     */
    public function getOrdemServico()
    {
        return $this->ordemServico;
    }

    /**
     * @param OrdemServico $ordemServico
     */
    public function setOrdemServico($ordemServico)
    {
        $this->ordemServico = $ordemServico;
    }

    /**
     * @return bool
     */
    public function isIndAtivo()
    {
        return $this->indAtivo;
    }

    /**
     * @param bool $indAtivo
     */
    public function setIndAtivo($indAtivo)
    {
        $this->indAtivo = $indAtivo;
    }

}