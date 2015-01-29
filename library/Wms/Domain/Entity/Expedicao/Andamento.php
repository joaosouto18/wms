<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Usuario,
    Wms\Domain\Entity\Expedicao;

/**
 * Andamento
 *
 * @Table(name="EXPEDICAO_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\AndamentoRepository")
 */
class Andamento
{

    /**
     * @var integer $id
     *
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EXP_ANDAMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * Data e hora andamento
     *
     * @var datetime $dataAndamento
     * @Column(name="DTH_ANDAMENTO", type="datetime", nullable=false)
     */
    protected $dataAndamento;

    /**
     * @var Wms\Domain\Entity\Usuario $usuario
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * Descricao do andamento
     *
     * @var string $dscObservacao
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=false)
     */
    protected $dscObservacao;

    /**
     * @param \Wms\Domain\Entity\Expedicao\datetime $dataAndamento
     */
    public function setDataAndamento($dataAndamento)
    {
        $this->dataAndamento = $dataAndamento;
        return $this;
    }

    /**
     * @return \Wms\Domain\Entity\Expedicao\datetime
     */
    public function getDataAndamento()
    {
        return $this->dataAndamento;
    }

    /**
     * @param string $dscObservacao
     */
    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
        return $this;
    }

    /**
     * @return string
     */
    public function getDscObservacao()
    {
        return $this->dscObservacao;
    }

    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
        return $this;
    }

    public function getExpedicao()
    {
        return $this->expedicao;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \Wms\Domain\Entity\Expedicao\Wms\Domain\Entity\Usuario $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
        return $this;
    }

    /**
     * @return \Wms\Domain\Entity\Expedicao\Wms\Domain\Entity\Usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }


}
