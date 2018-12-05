<?php

namespace Wms\Domain\Entity\Pessoa\Fisica;

use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Pessoa\Fisica as FisicaEntity,
    Wms\Domain\Entity\Ator as AtorInterface;

/**
 * Conferente
 *
 * @Table(name="CONFERENTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\Pessoa\Fisica\ConferenteRepository")
 */
class Conferente implements AtorInterface
{

    /**
     * @Id
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa\Fisica", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_CONFERENTE", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * Retorna o ID do conferente
     * @return integer
     */
    public function getId()
    {
        return $this->pessoa->getId();
    }

    /**
     * Retorna a pessoa relacionada
     * @return Pessoa
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    /**
     * Atribrui uma pessoa  para o conferente
     * @param Pessoa $pessoa
     */
    public function setPessoa(FisicaEntity $pessoa)
    {
        $this->pessoa = $pessoa;
        return $this;
    }

}