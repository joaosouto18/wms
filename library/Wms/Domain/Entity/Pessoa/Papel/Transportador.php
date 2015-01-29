<?php

namespace Wms\Domain\Entity\Pessoa\Papel;

use Wms\Domain\Entity\Ator as AtorInterface,
    Wms\Domain\Entity\Pessoa\Juridica as JuridicaEntity;

/**
 * 
 * @Table(name="TRANSPORTADOR")
 * @Entity(repositoryClass="Wms\Domain\Entity\Pessoa\Papel\TransportadorRepository")
 */
class Transportador implements AtorInterface
{

    /**
     * @Id
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa\Juridica", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_TRANSPORTADOR", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @var integer Código do transportador vindo da integração
     * @Column(name="COD_TRANSPORTADOR_INTEGRACAO", type="string", nullable=false)
     */
    protected $idExterno;

    /**
     * @Column(name="IND_ATIVO", type="string", length=1, nullable=true)
     * @var string se está ativo
     */
    protected $isAtivo;

    public function getId()
    {
        return $this->pessoa->getId();
    }

    public function getPessoa()
    {
        return $this->pessoa;
    }

    public function setPessoa(JuridicaEntity $pessoa)
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getIdExterno()
    {
        return $this->idExterno;
    }

    public function setIdExterno($idExterno)
    {
        $this->idExterno = $idExterno;
        return $this;
    }

    public function getIsAtivo()
    {
        return ($this->isAtivo == 'S');
    }

    public function setIsAtivo($isAtivo)
    {
        $this->isAtivo = ($isAtivo) ? 'S' : 'N';
        return $this;
    }

}