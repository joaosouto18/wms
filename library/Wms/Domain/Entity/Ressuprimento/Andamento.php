<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="RESSUPRIMENTO_ANDAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\AndamentoRepository")
 */
class Andamento
{
    const STATUS_DIVERGENTE = 548;
    const STATUS_CANCELADO = 549;
    const STATUS_LIBERADO = 550;

    /**
     * @Id
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_RESSU_ANDAMENTO", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @Column(name="COD_ONDA_RESSUPRIMENTO_OS", type="integer", nullable=false)
     */
    protected $codOndaRessuprimentoOs;

    /**
     * @Column(name="DTH_ANDAMENTO", type="datetime", nullable=false)
     */
    protected $dataAndamento;

    /**
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=false)
     */
    protected $dscObservacao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_TIPO", referencedColumnName="COD_SIGLA")
     */
    protected $tipo;

    /**
     * @param mixed $codOndaRessuprimentoOs
     */
    public function setCodOndaRessuprimentoOs($codOndaRessuprimentoOs)
    {
        $this->codOndaRessuprimentoOs = $codOndaRessuprimentoOs;
    }

    /**
     * @return mixed
     */
    public function getCodOndaRessuprimentoOs()
    {
        return $this->codOndaRessuprimentoOs;
    }

    /**
     * @param mixed $dataAndamento
     */
    public function setDataAndamento($dataAndamento)
    {
        $this->dataAndamento = $dataAndamento;
    }

    /**
     * @return mixed
     */
    public function getDataAndamento()
    {
        return $this->dataAndamento;
    }

    /**
     * @param mixed $dscObservacao
     */
    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
    }

    /**
     * @return mixed
     */
    public function getDscObservacao()
    {
        return $this->dscObservacao;
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
     * @param mixed $tipo
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    /**
     * @return mixed
     */
    public function getTipo()
    {
        return $this->tipo;
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
    public function getUsuario()
    {
        return $this->usuario;
    }

}
