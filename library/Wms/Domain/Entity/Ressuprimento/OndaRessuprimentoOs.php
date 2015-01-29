<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="ONDA_RESSUPRIMENTO_OS")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOsRepository")
 */
class OndaRessuprimentoOs
{
    const STATUS_ONDA_GERADA = 540;
    const STATUS_FINALIZADO = 541;

    /**
     * @Id
     * @Column(name="COD_ONDA_RESSUPRIMENTO_OS", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ONDA_RESSUPRIMENTO_OS", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\OndaRessuprimento")
     * @JoinColumn(name="COD_ONDA_RESSUPRIMENTO", referencedColumnName="COD_ONDA_RESSUPRIMENTO")
     */
    protected $ondaRessuprimento;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto")
     * @JoinColumns({
     *  @JoinColumn(name="COD_PRODUTO", referencedColumnName="COD_PRODUTO"),
     *  @JoinColumn(name="DSC_GRADE", referencedColumnName="DSC_GRADE")
     * })
     */
    protected $produto;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $endereco;

    /**
     * @Column(name="QTD", type="integer", nullable=false)
     */
    protected $qtd;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $os;

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOndaRessuprimento($ondaRessuprimento)
    {
        $this->ondaRessuprimento = $ondaRessuprimento;
    }

    public function getOndaRessuprimento()
    {
        return $this->ondaRessuprimento;
    }

    public function setOs($os)
    {
        $this->os = $os;
    }

    public function getOs()
    {
        return $this->os;
    }

    public function setProduto($produto)
    {
        $this->produto = $produto;
    }

    public function getProduto()
    {
        return $this->produto;
    }

    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
    }

    public function getQtd()
    {
        return $this->qtd;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

}
