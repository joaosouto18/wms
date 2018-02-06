<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MODELO_SEPARACAO_TPQUEB_EMB")
 * @Entity()
 */
class ModeloSeparacaoTipoQuebraEmbalado
{

    /**
     * @var ModeloSeparacao
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
     * @return ModeloSeparacao
     */
    public function getModeloSeparacao()
    {
        return $this->modeloSeparacao;
    }

    /**
     * @param ModeloSeparacao $modeloSeparacao
     */
    public function setModeloSeparacao($modeloSeparacao)
    {
        $this->modeloSeparacao = $modeloSeparacao;
    }


    /**
     * @return string
     */
    public function getTipoQuebra()
    {
        return $this->tipoQuebra;
    }

    /**
     * @param string $tipoQuebra
     */
    public function setTipoQuebra($tipoQuebra)
    {
        $this->tipoQuebra = $tipoQuebra;
    }


}