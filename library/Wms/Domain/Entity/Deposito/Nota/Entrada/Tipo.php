<?php

namespace Wms\Domain\Entity\Deposito\Nota\Entrada;

/**
 * TipoNotaEntrada
 *
 * @Table(name="TIPO_NOTA_ENTRADA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\Nota\Entrada\TipoRepository")
 */
class Tipo
{

    /**
     * @Column(name="COD_TIPO_NOTA_ENTRADA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_TIPO_NOTA_ENTRADA_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_TIPO_NOTA_ENTRADA", type="string", length=255, nullable=false)
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