<?php

namespace Wms\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Ordem de Serviço
 *
 * @Table(name="ORDEM_SERVICO")
 * @Entity(repositoryClass="Wms\Domain\Entity\OrdemServicoRepository")

 */
class OrdemServico
{
    /**
     * Formas de conferencia 
     */
    const MANUAL = 'M';
    const COLETOR = 'C';
    
    /**
     * lista de tipos os status
     * @var array
     */
    public static $listaFormaConferencia = array(
        self::MANUAL => 'Manual',
        self::COLETOR => 'Coletor',
    );

    /**
     * @var integer $id
     *
     * @Column(name="COD_OS", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ORDEM_SERVICO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * Data e hora iniciou da atividade
     * 
     * @var datetime $dataInicial
     * @Column(name="DTH_INICIO_ATIVIDADE", type="datetime", nullable=false)
     */
    protected $dataInicial;

    /**
     * Data e hora final da atividade
     * 
     * @var datetime $dataFinal
     * @Column(name="DTH_FINAL_ATIVIDADE", type="datetime")
     */
    protected $dataFinal;

    /**
     * @var Wms\Domain\Entity\Pessoa $pessoa
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA") 
     */
    protected $pessoa;

    /**
     * @var smallint $idRecebimento
     *
     * @Column(name="COD_RECEBIMENTO", type="smallint", nullable=false)
     */
    protected $idRecebimento;

    /**
     * @var Wms\Domain\Entity\Recebimento $recebimento
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Recebimento")
     * @JoinColumn(name="COD_RECEBIMENTO", referencedColumnName="COD_RECEBIMENTO") 
     */
    protected $recebimento;

    /**
     * @var Wms\Domain\Entity\Atividade $atividade
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Atividade")
     * @JoinColumn(name="COD_ATIVIDADE", referencedColumnName="COD_ATIVIDADE") 
     */
    protected $atividade;
    
    /**
     * Número de série da nota fiscal
     * 
     * @Column(name="DSC_OBSERVACAO", type="string", length=255)
     * @var string
     */
    protected $dscObservacao;

    /**
     * Forma que a ordem de servico esta sendo conferida
     *  
     * @Column(name="COD_FORMA_CONFERENCIA", type="string", length=1)
     * @var string
     */
    protected $formaConferencia;
    
    /**
     * Conferencias da ordem de servico
     * 
     * @OneToMany(targetEntity="Wms\Domain\Entity\Recebimento\Conferencia", mappedBy="ordemServico", cascade={"all"})
     */
    protected $conferencias;

    /**
     * @var smallint $idExpedicao
     *
     * @Column(name="COD_EXPEDICAO", type="smallint", nullable=false)
     */
    protected $idExpedicao;

    /**
     * @var smallint $idEnderecamento
     *
     * @Column(name="COD_ENDERECAMENTO", type="smallint", nullable=false)
     */
    protected $idEnderecamento;

    /**
     * @var Wms\Domain\Entity\Expedicao $expedicao
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * Motivo de bloqueio da continuidade da ordem de serviço
     *
     * @Column(name="BLOQUEIO", type="string", length=255)
     * @var string
     */
    protected $bloqueio;
    
    public function __construct()
    {
        $this->conferencias = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDataInicial()
    {
        $data = $this->dataInicial;
        if ($data != null)
            return $data->format('d/m/Y à\s H:i:s');
    }

    public function setDataInicial($dataInicial)
    {
        $this->dataInicial = $dataInicial;
        return $this;
    }

    public function getDataFinal()
    {
        $data = $this->dataFinal;
        if ($data != null)
            return $data->format('d/m/Y à\s H:i:s');
    }

    public function setDataFinal($dataFinal)
    {
        $this->dataFinal = $dataFinal;
        return $this;
    }

    public function getPessoa()
    {
        return $this->pessoa;
    }

    public function setPessoa($pessoa = null)
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getIdRecebimento()
    {
        return $this->idRecebimento;
    }

    public function setIdRecebimento($idRecebimento)
    {
        $this->idRecebimento = $idRecebimento;
        return $this;
    }

    public function getRecebimento()
    {
        return $this->recebimento;
    }

    public function setRecebimento($recebimento)
    {
        $this->recebimento = $recebimento;
        return $this;
    }

    public function getAtividade()
    {
        return $this->atividade;
    }

    public function setAtividade($atividade)
    {
        $this->atividade = $atividade;
        return $this;
    }

    public function osConferencia($recebimento)
    {
        $this->setRecebimento($recebimento);
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
    
    public function getFormaConferencia()
    {
        return $this->formaConferencia;
    }

    public function setFormaConferencia($formaConferencia)
    {
        $this->formaConferencia = $formaConferencia;
        return $this;
    }

    /**
     * @param \Wms\Domain\Entity\Wms\Domain\Entity\Expedicao $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return \Wms\Domain\Entity\Wms\Domain\Entity\Expedicao
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }

    /**
     * @param string $bloqueio
     */
    public function setBloqueio($bloqueio)
    {
        $this->bloqueio = $bloqueio;
    }

    /**
     * @return string
     */
    public function getBloqueio()
    {
        return $this->bloqueio;
    }

    /**
     * @param \Wms\Domain\Entity\smallint $idExpedicao
     */
    public function setIdExpedicao($idExpedicao)
    {
        $this->idExpedicao = $idExpedicao;
    }

    /**
     * @return \Wms\Domain\Entity\smallint
     */
    public function getIdExpedicao()
    {
        return $this->idExpedicao;
    }

    /**
     * @param \Wms\Domain\Entity\smallint $idEnderecamento
     */
    public function setIdEnderecamento($idEnderecamento)
    {
        $this->idEnderecamento = $idEnderecamento;
    }

    /**
     * @return \Wms\Domain\Entity\smallint
     */
    public function getIdEnderecamento()
    {
        return $this->idEnderecamento;
    }

}