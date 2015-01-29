<?php
namespace Wms\Domain\Entity\Atividade;

/**
 * SetorOperacional
 *
 * @Table(name="SETOR_OPERACIONAL")
 * @Entity(repositoryClass="Wms\Domain\Entity\Atividade\SetorOperacionalRepository")
 */
class SetorOperacional
{
    /**
     * @Column(name="COD_SETOR_OPERACIONAL", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_SETOR_OPERACIONAL_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @Column(name="DSC_SETOR_OPERACIONAL", type="string", length=60, nullable=false)
     */
    protected $descricao;

    public function getId()
    {
	return $this->id;
    }

    public function getDescricao()
    {
	return $this->descricao;
    }

    public function setDescricao($descricao)
    {
	$this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

}