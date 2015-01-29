<?php

namespace Wms\Domain\Entity\Util\Sigla;

/**
 * TipoSigla
 *
 * @Table(name="TIPO_SIGLA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Util\Sigla\TipoRepository")
 */
class Tipo
{

    /**
     * @Id
     * @var smallint $id
     * @Column(name="COD_TIPO_SIGLA", type="smallint", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_SIGLA_01", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string $descricao
     *
     * @Column(name="DSC_TIPO_SIGLA", type="string", length=60, nullable=true)
     */
    private $descricao;

    /**
     * @var string $isSistema
     *
     * @Column(name="IND_SIGLA_SISTEMA", type="string", length=1, nullable=true)
     */
    private $isSistema;

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

    public function getIsSistema()
    {
        return $this->isSistema;
    }

    public function setIsSistema($isSistema)
    {
        $this->isSistema = $isSistema;
        return $this;
    }

}