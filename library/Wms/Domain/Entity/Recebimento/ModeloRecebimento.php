<?php

namespace Wms\Domain\Entity\Recebimento;

use Wms\Domain\Entity\Recebimento;

/**
 * @Table(name="MODELO_RECEBIMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\ModeloRecebimentoRepository")
 */
class ModeloRecebimento
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_MODELO_RECEBIMENTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MODELO_RECEBIMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DESCRICAO", type="string")
     * @var string
     */
    protected $descricao;

    /**
     * @Column(name="CONTROLE_VALIDADE", type="string", nullable=false)
     * @var string
     */
    protected $controleValidade;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getControleValidade()
    {
        return $this->controleValidade;
    }

    /**
     * @param string $controleValidade
     */
    public function setControleValidade($controleValidade)
    {
        $this->controleValidade = $controleValidade;
    }

    /**
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param string $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

}
