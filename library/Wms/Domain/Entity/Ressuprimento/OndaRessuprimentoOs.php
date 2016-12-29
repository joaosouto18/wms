<?php

namespace Wms\Domain\Entity\Ressuprimento;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Util\Sigla;

/**
 * @Table(name="ONDA_RESSUPRIMENTO_OS")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOsRepository")
 */
class OndaRessuprimentoOs
{
    const STATUS_ONDA_GERADA = 540;
    const STATUS_FINALIZADO = 541;
    const STATUS_DIVERGENTE = 546;
    const STATUS_CANCELADO = 547;
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
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $endereco;

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

    /**
     * @Column(name="SEQUENCIA", type="integer", nullable=false)
     */
    protected $sequencia;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOsProduto", mappedBy="ondaRessuprimentoOs", cascade={"persist", "remove"})
     * @var ArrayCollection volumes que compoem este produto
     */
    protected $produtos;

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

    /**
     * @return OrdemServico
     */
    public function getOs()
    {
        return $this->os;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return Sigla
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param \Wms\Domain\Entity\Ressuprimento\ArrayCollection $produtos
     */
    public function setProdutos($produtos)
    {
        $this->produtos = $produtos;
    }

    /**
     * @return \Wms\Domain\Entity\Ressuprimento\ArrayCollection
     */
    public function getProdutos()
    {
        return $this->produtos;
    }

    /**
     * @param mixed $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

    /**
     * @return mixed
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

    public function getNextSequenciaSQ(){
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $SQL = "SELECT SQ_SEQUENCIA_ONDA_OS.NEXTVAL FROM DUAL";
        $resultado = $em->getConnection()->query($SQL)-> fetchAll(\PDO::FETCH_ASSOC);
        return $resultado[0]['NEXTVAL'];

    }

}
