<?php
namespace Wms\Domain\Entity\Armazenagem;

/**
 * Atividade
 *
 * @Table(name="LINHA_SEPARACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Armazenagem\LinhaSeparacaoRepository")
 */
class LinhaSeparacao
{

    /**
     * @Column(name="COD_LINHA_SEPARACAO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_LINHA_SEPARACAO_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @Column(name="DSC_LINHA_SEPARACAO", type="string", length=255, nullable=true)
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