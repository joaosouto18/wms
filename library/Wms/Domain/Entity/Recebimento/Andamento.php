<?php

namespace Wms\Domain\Entity\Recebimento;

use Wms\Domain\Entity\Usuario,
    Wms\Domain\Entity\Recebimento;

/**
 * Andamento
 *
 * @Table(name="RECEBIMENTO_ANDAMENTO")
 * @Entity(repositoryClass="Bisna\Base\Domain\Entity\Repository")
 */
class Andamento
{

    /**
     * @var integer $id
     *
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ANDAMENTO_RECEBIMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var Wms\Domain\Entity\Recebimento $recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento", cascade={"persist"})
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * Data e hora andamento
     * 
     * @var datetime $dataAndamento
     * @Column(name="DTH_ANDAMENTO", type="datetime", nullable=false)
     */
    protected $dataAndamento;

    /**
     * @var Wms\Domain\Entity\Util\Sigla $tipoAndamento
     * Código da sigla do status do recebimento
     * @OneToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_TIPO_ANDAMENTO", referencedColumnName="COD_SIGLA")
     */
    protected $tipoAndamento;

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
     * @Column(name="COD_PRODUTO", type="string", nullable=true)
     */
    protected $codProduto;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=true)
     */
    protected $dscGrade;

    /**
     * @Column(name="DTH_VALIDADE", type="datetime", nullable=true)
     */
    protected $dataValidade;

    /**
     * @Column(name="DIAS_SHELF_LIFE", type="integer", nullable=true)
     */
    protected $diasShelflife;

    /**
     * @Column(name="QTD_CONFERIDA", type="string", nullable=true)
     */
    protected $qtdConferida;


    public function getId()
    {
        return $this->id;
    }

    public function getRecebimento()
    {
        return $this->recebimento;
    }

    public function setRecebimento(Recebimento $recebimento)
    {
        $this->recebimento = $recebimento;
        return $this;
    }

    public function getDataAndamento()
    {
        return $this->dataAndamento;
    }

    public function setDataAndamento($dataAndamento)
    {
        $this->dataAndamento = $dataAndamento;
        return $this;
    }

    public function getTipoAndamento()
    {
        return $this->tipoAndamento;
    }

    public function setTipoAndamento($tipoAndamento)
    {
        $this->tipoAndamento = $tipoAndamento;
        return $this;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario)
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getIdUsuario()
    {
        return $this->idUsuario;
    }

    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
        return $this;
    }

    public function getDscObservacao()
    {
        return $this->dscObservacao;
    }

    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCodProduto()
    {
        return $this->codProduto;
    }

    /**
     * @param mixed $codProduto
     */
    public function setCodProduto($codProduto)
    {
        $this->codProduto = $codProduto;
    }

    /**
     * @return mixed
     */
    public function getDscGrade()
    {
        return $this->dscGrade;
    }

    /**
     * @param mixed $dscGrade
     */
    public function setDscGrade($dscGrade)
    {
        $this->dscGrade = $dscGrade;
    }

    /**
     * @return mixed
     */
    public function getDataValidade()
    {
        return $this->dataValidade;
    }

    /**
     * @param mixed $dataValidade
     */
    public function setDataValidade($dataValidade)
    {
        $this->dataValidade = $dataValidade;
    }

    /**
     * @return mixed
     */
    public function getDiasShelflife()
    {
        return $this->diasShelflife;
    }

    /**
     * @param mixed $diasShelflife
     */
    public function setDiasShelflife($diasShelflife)
    {
        $this->diasShelflife = $diasShelflife;
    }

    /**
     * @return mixed
     */
    public function getQtdConferida()
    {
        return $this->qtdConferida;
    }

    /**
     * @param mixed $qtdConferida
     */
    public function setQtdConferida($qtdConferida)
    {
        $this->qtdConferida = $qtdConferida;
    }


}
