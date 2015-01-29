<?php

namespace Wms\Domain\Entity\Sistema\Recurso;

/**
 * Vinculo
 * 
 * @Table(name="RECURSO_ACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\Recurso\VinculoRepository")
 */
class Vinculo
{

    /**
     * @var smallint $id
     *
     * @Column(name="COD_RECURSO_ACAO", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RECURSO_ACAO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string $nome
     * @Column(name="DSC_RECURSO_ACAO", type="string", length=100, nullable=true)
     */
    protected $nome;

    /**
     * @var Recurso
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Sistema\Recurso")
     * @JoinColumn(name="COD_RECURSO", referencedColumnName="COD_RECURSO")
     */
    protected $recurso;

    /**
     * @var Acao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Sistema\Acao")
     * @JoinColumn(name="COD_ACAO", referencedColumnName="COD_ACAO")
     */
    protected $acao;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Ajuda", mappedBy="recursoAcao")
     */
    protected $ajuda;
    
    
    public function getId()
    {
        return $this->id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
        return $this;
    }

    public function getRecurso()
    {
        return $this->recurso;
    }

    public function setRecurso($recurso)
    {
        $this->recurso = $recurso;
        return $this;
    }

    public function getAcao()
    {
        return $this->acao;
    }

    public function setAcao($acao)
    {
        $this->acao = $acao;
        return $this;
    }

    public function getAjuda()
    {
        return $this->ajuda;
    }

    public function setAjuda($ajuda)
    {
        $this->ajuda = $ajuda;
        return $this;
    }

}