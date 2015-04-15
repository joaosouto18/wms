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
    public function getModeloSeparacao()
    {
        return $this->modeloSeparacao;
    }

    /**
     * @param mixed $modeloSeparacao
     */
    public function setModeloSeparacao($modeloSeparacao)
    {
        $this->modeloSeparacao = $modeloSeparacao;
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