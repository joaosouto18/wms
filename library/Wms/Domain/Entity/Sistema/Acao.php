<?php

namespace Wms\Domain\Entity\Sistema;

/**
 * Acao
 *
 * @Table(name="ACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\AcaoRepository")
 */
class Acao
{

    /**
     * @var integer $id
     * @Column(name="COD_ACAO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ACAO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $descricao
     * @Column(name="DSC_ACAO", type="string", length=2000, nullable=true)
     */
    protected $descricao;

    /**
     * @var string $nome
     * @Column(name="NOM_ACAO", type="string", length=2000, nullable=true)
     */
    protected $nome;

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
        $this->descricao = $descricao;
        return $this;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = mb_strtoupper($nome, 'UTF-8');
        return $this;
    }

}