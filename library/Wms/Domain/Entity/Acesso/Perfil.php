<?php

namespace Wms\Domain\Entity\Acesso;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="PERFIL_USUARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Acesso\PerfilRepository")
 */
class Perfil
{
    /**
     * @Column(name="COD_PERFIL_USUARIO", type="smallint", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PERFIL_USUARIO_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @Column(name="NOM_PERFIL_USUARIO", type="string", length=20, nullable=false)
     */
    protected $nome;
    /**
     * @Column(name="DSC_PERFIL_USUARIO", type="string", length=60, nullable=false)
     */
    protected $descricao;
    /**
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Sistema\Recurso\Vinculo")
     * @JoinTable(name="PERFIL_USUARIO_RECURSO_ACAO",
     *   joinColumns={@JoinColumn(name="COD_PERFIL_USUARIO", referencedColumnName="COD_PERFIL_USUARIO")},
     *   inverseJoinColumns={@JoinColumn(name="COD_RECURSO_ACAO", referencedColumnName="COD_RECURSO_ACAO")}
     * )
     */
    protected $acoes;
    /**
     * Usuários que tem estes perfis
     * 
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Usuario", mappedBy="perfis", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $usuarios;

    public function __construct()
    {
	$this->acoes = new ArrayCollection;
        $this->usuarios = new ArrayCollection;
    }
    

    public function addUsuario(\Wms\Domain\Entity\Usuario $usuario)
    {
	$this->usuarios[] = $usuario;
    }

    public function getId()
    {
	return $this->id;
    }

    public function getDescricao()
    {
	return $this->descricao;
    }

    public function setDescricao($descricao)
    {
	$this->descricao = $descricao;
        return $this;
    }

    public function getNome()
    {
	return $this->nome;
    }

    public function setNome($nome)
    {
	$this->nome = mb_strtoupper($nome, 'UTF-8');
        return $this;
    }

    public function getUsuarios()
    {
	return $this->usuarios;
    }

    public function setUsuarios($usuarios)
    {
	$this->usuarios = $usuarios;
        return $this;
    }

    public function getAcoes()
    {
	return $this->acoes;
    }

    public function setAcoes($acoes)
    {
	$this->acoes = $acoes;
        return $this;
    }

}