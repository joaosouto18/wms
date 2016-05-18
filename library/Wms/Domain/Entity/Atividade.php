<?php

namespace Wms\Domain\Entity;

/**
 * Atividade
 *
 * @Table(name="ATIVIDADE")
 * @Entity(repositoryClass="Wms\Domain\Entity\AtividadeRepository")
 */
class Atividade
{

    const CONFERIR_PRODUTO = 1;
    const DESCARREGAR_VEICULO = 2;
    const SEPARACAO = 9;
    const CONFERIR_EXPEDICAO = 11;
    const ENDERECAMENTO = 12;
    const RESSUPRIMENTO = 13;
    const INVENTARIO = 14;

    /**
     * @Column(name="COD_ATIVIDADE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ATIVIDADE_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DSC_ATIVIDADE", type="string", length=255, nullable=false)
     */
    protected $descricao;

    /**
     * @var Wms\Domain\Entity\Atividade\SetorOperacional $setorOperacional
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Atividade\SetorOperacional")
     * @JoinColumn(name="COD_SETOR_OPERACIONAL", referencedColumnName="COD_SETOR_OPERACIONAL") 
     */
    protected $setorOperacional;

    /**
     * lista de tipos os status
     * @var array
     */
    public static $listaStatus = array(
        self::CONFERIR_PRODUTO => 'Conferir Produto',
        self::DESCARREGAR_VEICULO => 'Descarregar Veículo',
        self::SEPARACAO => 'Separação',
    );

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

    public function getSetorOperacional()
    {
        return $this->setorOperacional;
    }

    public function setSetorOperacional($setorOperacional)
    {
        $this->setorOperacional = $setorOperacional;
        return $this;
    }

}