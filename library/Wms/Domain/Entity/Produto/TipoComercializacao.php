<?php
namespace Wms\Domain\Entity\Produto;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="V_TIPO_COMERCIALIZACAO")
 * @Entity(repositoryClass="Wms\Domain\EntityRepository")
 */
class TipoComercializacao
{

    /**
     * @Id
     * @Column(name="COD_TIPO_COMERCIALIZACAO", type="integer", nullable=false)
     * @var string
     */
    protected $id;
    /**
     * @Column(name="DSC_TIPO_COMERCIALIZACAO", type="string", length=60, nullable=false)
     */
    protected $descricao;

    public function getId()
    {
	return $this->id;
    }

    public function setId($id)
    {
	$this->id = $id;
        return $this;
    }
    public function getDescricao()
    {
        return $this->descricao;
    }

    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

}