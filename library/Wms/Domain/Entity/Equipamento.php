<?php

namespace Wms\Domain\Entity;

/**
 *
 * @Table(name="EQUIPAMENTO")
 * @Entity(repositoryClass="Wms\Domain\Entity\EquipamentoRepository")
 */
class Equipamento
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_EQUIPAMENTO", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_EQUIPAMENTO", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @Column(name="DESCRICAO", type="string", nullable=false)
     * @var string
     */
    protected $descricao;

    /**
     * @Column(name="MODELO", type="string")
     * @var string
     */
    protected $modelo;

    /**
     * @Column(name="MARCA", type="string")
     * @var string
     */
    protected $marca;

    /**
     * @Column(name="NUM_PATRIMONIO", type="string")
     * @var string
     */
    protected $patrimonio;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param string $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    /**
     * @return string
     */
    public function getModelo()
    {
        return $this->modelo;
    }

    /**
     * @param string $modelo
     */
    public function setModelo($modelo)
    {
        $this->modelo = $modelo;
    }

    /**
     * @return string
     */
    public function getMarca()
    {
        return $this->marca;
    }

    /**
     * @param string $marca
     */
    public function setMarca($marca)
    {
        $this->marca = $marca;
    }

    /**
     * @return string
     */
    public function getPatrimonio()
    {
        return $this->patrimonio;
    }

    /**
     * @param string $patrimonio
     */
    public function setPatrimonio($patrimonio)
    {
        $this->patrimonio = $patrimonio;
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

}