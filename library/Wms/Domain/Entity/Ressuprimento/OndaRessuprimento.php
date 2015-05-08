<?php

namespace Wms\Domain\Entity\Ressuprimento;
/**
 * @Table(name="ONDA_RESSUPRIMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository")
 */
class OndaRessuprimento
{
    /**
     * @Id
     * @Column(name="COD_ONDA_RESSUPRIMENTO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_ONDA_RESSUPRIMENTO", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @Column(name="DTH_CRIACAO", type="datetime", nullable=false)
     */
    protected $dataCriacao;

    /**
     * @Column(name="DSC_OBSERVACAO", type="string", nullable=false)
     */
    protected $dscObservacao;

    public function setDataCriacao($dataCriacao)
    {
        $this->dataCriacao = $dataCriacao;
    }

    public function getDataCriacao()
    {
        return $this->dataCriacao;
    }

    public function setDscObservacao($dscObservacao)
    {
        $this->dscObservacao = $dscObservacao;
    }

    public function getDscObservacao()
    {
        return $this->dscObservacao;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }


}
