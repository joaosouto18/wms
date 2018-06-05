<?php

namespace Wms\Domain\Entity;

/**
 * Ajuda
 *
 * @Table(name="AJUDA")
 * @Entity(repositoryClass="Wms\Domain\Entity\AjudaRepository")
 */
class Ajuda
{
    /**
     * @Id
     * @var integer $id
     * @Column(name="COD_AJUDA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_AJUDA_01", initialValue=1, allocationSize=100)
     */
    protected $id;
    
    /**
     * @var string Descricao da ajuda
     * @Column(name="DSC_AJUDA", type="string", length=60, nullable=true)
     */
    protected $dscAjuda;
    
    /**
     * @var string Peso de ordenacao
     * @Column(name="NUM_PESO", type="integer", length=1, nullable=true)
     */
    protected $numPeso;
    
    /**
     * @var string Descricao do conteudo
     * @Column(name="DSC_CONTEUDO", type="string", length=2000, nullable=true)
     */
    protected $dscConteudo;

    /**
     * @var string Codigo do pai
     * @Column(name="COD_AJUDA_PAI", type="integer", length=8, nullable=true)
     */
    protected $idAjudaPai;
    
    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Sistema\Recurso\Vinculo", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_RECURSO_ACAO", referencedColumnName="COD_RECURSO_ACAO")
     * @var Wms\Domain\Entity\Sistema\Recurso\Vinculo recurso e ação que essa ajuda pertence
     */
    protected $recursoAcao;
    

    public function getId()
    {
        return $this->id;
    }
    
    public function getDscAjuda()
    {
        return $this->dscAjuda;
    }

    public function setDscAjuda($dscAjuda)
    {
        $this->dscAjuda = $dscAjuda;
        return $this;
    }

    public function getNumPeso()
    {
        return $this->numPeso;
    }

    public function setNumPeso($numPeso)
    {
        $this->numPeso = $numPeso;
        return $this;
    }

    public function getDscConteudo()
    {
        return $this->dscConteudo;
    }

    public function setDscConteudo($dscConteudo)
    {
        $this->dscConteudo = $dscConteudo;
        return $this;
    }

    public function getIdAjudaPai()
    {
        return $this->idAjudaPai;
    }

    public function setIdAjudaPai($idAjudaPai)
    {
        $this->idAjudaPai = $idAjudaPai;
        return $this;
    }

    public function getRecursoAcao()
    {
        return $this->recursoAcao;
    }

    public function setRecursoAcao($recursoAcao)
    {
        $this->recursoAcao = $recursoAcao;
        return $this;
    }

}