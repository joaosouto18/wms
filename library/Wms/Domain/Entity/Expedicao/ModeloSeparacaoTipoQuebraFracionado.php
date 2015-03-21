<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MODELO_SEPARACAO_TPQUEB_FRAC")
 * @Entity()
 */
class ModeloSeparacaoTipoQuebraFracionado
{

    /**
     * @Id
     * @Column(name="COD_MODELO_SEPARACAO", type="integer", nullable=false)
     * @var string Código do produto
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\ModeloSeparacao")
     * @JoinColumn(name="COD_MODELO_SEPARACAO", referencedColumnName="COD_MODELO_SEPARACAO")
     */
    protected $modeloSeparacao;

    /**
     * @Id
     * @Column(name="IND_TIPO_QUEBRA", type="string", nullable=true)
     */
    protected $tipoQuebra;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTipoQuebra()
    {
        return $this->tipoQuebra;
    }

    /**
     * @param mixed $tipoQuebra
     */
    public function setTipoQuebra($tipoQuebra)
    {
        $this->tipoQuebra = $tipoQuebra;
    }


}