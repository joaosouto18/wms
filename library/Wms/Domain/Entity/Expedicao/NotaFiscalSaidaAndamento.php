<?php

namespace Wms\Domain\Entity\Expedicao;

use Wms\Domain\Entity\Expedicao;

/**
 * Andamento
 *
 * @Table(name="NOTA_FISCAL_SAIDA_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository")
 */
class NotaFiscalSaidaAndamento
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_NF_SAIDA_ANDAMENTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NF_SAIDA_ANDAMENTO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\NotaFiscalSaida")
     * @JoinColumn(name="COD_NOTA_FISCAL_SAIDA", referencedColumnName="COD_NOTA_FISCAL_SAIDA")
     */
    protected $NotaFiscalSaida;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_STATUS", referencedColumnName="COD_SIGLA")
     */
    protected $status;

    /**
     * @Column(name="DATA", type="date")
     * @var date
     */
    protected $data;

    /**
     * @Column(name="OBSERVACAO", type="string")
     * @var string
     */
    protected $observacao;

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
     * @return mixed
     */
    public function getNotaFiscalSaida()
    {
        return $this->NotaFiscalSaida;
    }

    /**
     * @param mixed $NotaFiscalSaida
     */
    public function setNotaFiscalSaida($NotaFiscalSaida)
    {
        $this->NotaFiscalSaida = $NotaFiscalSaida;
    }

    /**
     * @return mixed
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }

    /**
     * @param mixed $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return mixed
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return date
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param date $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getObservacao()
    {
        return $this->observacao;
    }

    /**
     * @param string $observacao
     */
    public function setObservacao($observacao)
    {
        $this->observacao = $observacao;
    }

}
