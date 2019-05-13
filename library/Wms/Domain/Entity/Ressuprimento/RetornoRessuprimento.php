<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 22/03/2019
 * Time: 14:38
 */

namespace Wms\Domain\Entity\Ressuprimento;


use Wms\Domain\Entity\Deposito;
use Bisna\Base\Domain\Entity\EntityService;
use Doctrine\Common\Collections\Criteria;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Enderecamento\EstoqueRepository;
use Wms\Domain\Entity\Enderecamento\HistoricoEstoque;
use Wms\Domain\Entity\InventarioNovo;
use Wms\Domain\Entity\InventarioNovoRepository;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\OrdemServicoRepository;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Usuario;


/**
 * @Table(name="RETORNO_RESSUPRIMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\RetornoRessuprimento")
 */
class RetornoRessuprimento
{
    /**
     * @Id
     * @Column(name="COD_RETORNO_RESSUPRIMENTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RETORNO_RESSUPRIMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var OndaRessuprimentoOs $ondaRessuprimentoOs
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs")
     * @JoinColumn(name="COD_ONDA_RESSUPRIMENTO_OS", referencedColumnName="COD_ONDA_RESSUPRIMENTO_OS")
     */
    protected $ondaRessuprimentoOs;

    /**
     * @var OrdemServico $codOs
     * @ManyToOne(targetEntity="Wms\Domain\Entity\OrdemServico")
     * @JoinColumn(name="COD_OS", referencedColumnName="COD_OS")
     */
    protected $codOs;

    /**
     * @var Deposito\Endereco $depositoEndereco
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito\Endereco")
     * @JoinColumn(name="COD_DEPOSITO_ENDERECO", referencedColumnName="COD_DEPOSITO_ENDERECO")
     */
    protected $depositoEndereco;

    /**
     * @Column(name="DTH_MOVIMENTACAO", type="datetime", nullable=false)
     */
    protected $dataMovimentacao;


    /**
     * @param mixed $ondaRessuprimentoOs
     */
    public function setOndaRessuprimentoOs($ondaRessuprimentoOs)
    {
        $this->ondaRessuprimentoOs = $ondaRessuprimentoOs;
    }

    /**
     * @return mixed
     */
    public function getOndaRessuprimentoOs()
    {
        return $this->ondaRessuprimentoOs;
    }

    /**
     * @param mixed $codOs
     */
    public function setCodOs($codOs)
    {
        $this->codOs = $codOs;
    }

    /**
     * @return mixed
     */
    public function getCodOs()
    {
        return $this->codOs;
    }

    /**
     * @param mixed $depositoEndereco
     */
    public function setDepositoEndereco($depositoEndereco)
    {
        $this->depositoEndereco = $depositoEndereco;
    }

    /**
     * @return mixed
     */
    public function getDepositoEndereco()
    {
        return $this->depositoEndereco;
    }

    /**
     * @param mixed $dataMovimentacao
     */
    public function setDataMovimentacao($dataMovimentacao)
    {
        $this->dataMovimentacao = $dataMovimentacao;
    }

    /**
     * @return mixed
     */
    public function getDataMovimentacao()
    {
        return $this->dataMovimentacao;
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

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }


}
