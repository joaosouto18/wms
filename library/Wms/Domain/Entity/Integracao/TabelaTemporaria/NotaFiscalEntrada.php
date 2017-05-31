<?php

namespace Wms\Domain\Entity\Integracao\TabelaTemporaria;

use Wms\Domain\Configurator;

/**
 *
 * @Table(name="INTEGRACAO_NF_ENTRADA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\TabelaTemporaria\NotaFiscalEntradaRepository")
 */
class NotaFiscalEntrada
{
    /**
     * @Id
     * @Column(name="COD_INTEGRACAO_NF_ENTRADA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_INTEGRACAO_NF_ENTRADA_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @Column(name="COD_FORNECEDOR", type="string", nullable=true)
     */
    protected $codFornecedor;

    /**
     * @Column(name="NOM_FORNECEDOR", type="string", nullable=true)
     */
    protected $nomFornecedor;

    /**
     * @Column(name="CPF_CNPJ", type="string", nullable=true)
     */
    protected $cpfCnpj;

    /**
     * @Column(name="DSC_GRADE", type="string", nullable=true)
     */
    protected $grade;

    /**
     * @Column(name="INSCRICAO_ESTADUAL", type="string", nullable=true)
     */
    protected $inscricaoEstadual;

    /**
     * @Column(name="NUM_NOTA_FISCAL", type="string", nullable=true)
     */
    protected $numNF;

    /**
     * @Column(name="COD_PRODUTO", type="string", nullable=true)
     */
    protected $codProduto;

    /**
     * @Column(name="COD_SERIE_NOTA_FISCAL", type="string", nullable=true)
     */
    protected $serieNF;

    /**
     * @var \DateTime
     * @Column(name="DAT_EMISSAO", type="datetime", nullable=true)
     */
    protected $dthEmissao;

    /**
     * @Column(name="DSC_PLACA_VEICULO", type="string", nullable=true)
     */
    protected $veiculo;

    /**
     * @Column(name="QTD_ITEM", type="decimal", length=8, nullable=true)
     */
    protected $qtdItem;

    /**
     * @Column(name="VALOR_TOTAL", type="decimal", length=8, nullable=true)
     */
    protected $vlrTotal;

    /**
     * @var \DateTime
     * @Column(name="DTH", type="datetime", nullable=true)
     */
    protected $dth;

    /**
     * @param mixed $codFornecedor
     */
    public function setCodFornecedor($codFornecedor)
    {
        $this->codFornecedor = $codFornecedor;
    }

    /**
     * @return mixed
     */
    public function getCodFornecedor()
    {
        return $this->codFornecedor;
    }

    /**
     * @param mixed $cpfCnpj
     */
    public function setCpfCnpj($cpfCnpj)
    {
        $this->cpfCnpj = $cpfCnpj;
    }

    /**
     * @return mixed
     */
    public function getCpfCnpj()
    {
        return $this->cpfCnpj;
    }

    /**
     * @param \DateTime $dth
     */
    public function setDth($dth)
    {
        $this->dth = $dth;
    }

    /**
     * @return \DateTime
     */
    public function getDth()
    {
        return $this->dth;
    }

    /**
     * @param \DateTime $dthEmissao
     */
    public function setDthEmissao($dthEmissao)
    {
        $this->dthEmissao = $dthEmissao;
    }

    /**
     * @return \DateTime
     */
    public function getDthEmissao()
    {
        return $this->dthEmissao;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    }

    /**
     * @return mixed
     */
    public function getGrade()
    {
        return $this->grade;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $inscricaoEstadual
     */
    public function setInscricaoEstadual($inscricaoEstadual)
    {
        $this->inscricaoEstadual = $inscricaoEstadual;
    }

    /**
     * @return mixed
     */
    public function getInscricaoEstadual()
    {
        return $this->inscricaoEstadual;
    }

    /**
     * @param mixed $nomFornecedor
     */
    public function setNomFornecedor($nomFornecedor)
    {
        $this->nomFornecedor = $nomFornecedor;
    }

    /**
     * @return mixed
     */
    public function getNomFornecedor()
    {
        return $this->nomFornecedor;
    }

    /**
     * @param mixed $numNF
     */
    public function setNumNF($numNF)
    {
        $this->numNF = $numNF;
    }

    /**
     * @return mixed
     */
    public function getNumNF()
    {
        return $this->numNF;
    }

    /**
     * @param mixed $qtdItem
     */
    public function setQtdItem($qtdItem)
    {
        $this->qtdItem = $qtdItem;
    }

    /**
     * @return mixed
     */
    public function getQtdItem()
    {
        return $this->qtdItem;
    }

    /**
     * @param mixed $serieNF
     */
    public function setSerieNF($serieNF)
    {
        $this->serieNF = $serieNF;
    }

    /**
     * @return mixed
     */
    public function getSerieNF()
    {
        return $this->serieNF;
    }

    /**
     * @param mixed $veiculo
     */
    public function setVeiculo($veiculo)
    {
        $this->veiculo = $veiculo;
    }

    /**
     * @return mixed
     */
    public function getVeiculo()
    {
        return $this->veiculo;
    }

    /**
     * @param mixed $vlrTotal
     */
    public function setVlrTotal($vlrTotal)
    {
        $this->vlrTotal = $vlrTotal;
    }

    /**
     * @return mixed
     */
    public function getVlrTotal()
    {
        return $this->vlrTotal;
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
    public function getCodProduto()
    {
        return $this->codProduto;
    }


}