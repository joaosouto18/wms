<?php
namespace Wms\Domain\Entity;

use Wms\Domain\Entity\Pessoa as Pessoa,
    Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Ator;

/**
 * Usuario
 *
 * @Table(name="USUARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\UsuarioRepository")
 */
class Usuario implements \Zend_Acl_Role_Interface, Ator
{
    /**
     * @Id
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;
    /**
     * @var string $login
     * @Column(name="DSC_IDENTIFICACAO_USUARIO", type="string", length=20, nullable=false)
     */
    protected $login;
    /**
     * @var string $senha
     * @Column(name="DSC_SENHA_ACESSO", type="string", length=32, nullable=false)
     */
    protected $senha;
    /**
     * @var string $isAtivo
     * @Column(name="IND_ATIVO", type="string", length=1, nullable=false)
     */
    protected $isAtivo;
    /**
     * Papel (perfil) do usuário. Criado para uso junto a Zend_Acl
     * @var string
     */
    protected $roleId;
    /**   
     * @var string $isAtivo
     * @Column(name="IND_SENHA_PROVISORIA", type="string", length=1, nullable=false)
     */
    protected $isSenhaProvisoria;
    /**
     * Depósitos que o usuário possui acesso
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Deposito", inversedBy="usuarios", cascade={"persist"})
     * @JoinTable(name="USUARIO_DEPOSITO",
     *      joinColumns={@JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")},
     *      inverseJoinColumns={@JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO", unique=true)}
     *      )
     * @var ArrayCollection
     */
    protected $depositos;
    
    /**
     * Relacao de perfis do usuario
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Acesso\Perfil", inversedBy="usuarios", cascade={"persist"})
     * @JoinTable(name="USUARIO_PERFIL_USUARIO",
     *      joinColumns={@JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")},
     *      inverseJoinColumns={@JoinColumn(name="COD_PERFIL_USUARIO", referencedColumnName="COD_PERFIL_USUARIO", unique=true)}
     * )
     * @var ArrayCollection
     */
    protected $perfis;
    

    public function __construct()
    {
	$this->depositos = new ArrayCollection;
        $this->perfis = new ArrayCollection;
    }
    
    public function addDeposito(\Wms\Domain\Entity\Deposito $deposito) 
    {
	$deposito->addUsuario($this);
	$this->depositos[] = $deposito;
    }
    
    public function addPerfil(\Wms\Domain\Entity\Acesso\Perfil $perfil)
    {
        $perfil->addUsuario($this);
        $this->perfis[] = $perfil;
    }

    /**
     * Retorna o ID do usuário
     * @return integer
     */
    public function getId()
    {
	return $this->pessoa->getId();
    }

    public function getLogin()
    {
	return $this->login;
    }

    public function setLogin($login)
    {
	$this->login = $login;
        return $this;
    }

    public function getSenha()
    {
	return $this->senha;
    }

    /**
     * Atribui e criptografa a senha do usuário
     * @param string $senha 
     */
    public function setSenha($senha)
    {
	$this->senha = $this->criptografaSenha($senha);
        return $this;
    }

    /**
     * Criptografa uma senha
     * @param type $senha 
     */
    public function criptografaSenha($senha)
    {
	return sha1(sha1(sha1($senha)));
    }

    /**
     * Retorna se o usuário está ativo ou não (S => SIM, N => NÃO)
     * @return string 
     */
    public function getIsAtivo()
    {
	return $this->isAtivo;
    }

    /**
     * Ativa/Inativa o usuario
     * @param string $isAtivo 
     */
    public function setIsAtivo($isAtivo)
    {
        $this->isAtivo = ($isAtivo) ? 'S' : 'N';
        return $this;
    }
    
    /**
     * Retorna a pessoa relacionada
     * @return Pessoa
     */
    public function getPessoa()
    {
	return $this->pessoa;
    }

    /**
     * Atribrui uma pessoa  para o usuário
     * @param Pessoa $pessoa
     */
    public function setPessoa(Pessoa $pessoa)
    {
	$this->pessoa = $pessoa;
        return $this;
    }

    public function setRoleId($roleId)
    {
	$this->roleId = $roleId;
        return $this;
    }

    public function getRoleId()
    {
	return $this->roleId;
    }

    /**
     * Retorna se a senha deste usuário é provisória ou não
     */
    public function getIsSenhaProvisoria()
    {
	return $this->isSenhaProvisoria;
    }

    /**
     * Informa se a senha é provisória ou não
     * @return void 
     */
    public function setIsSenhaProvisoria($isSenhaProvisoria)
    {
	if (!in_array($isSenhaProvisoria, array('S', 'N'))) {
	    throw new \InvalidArgumentException($isSenhaProvisoria);
	}
	$this->isSenhaProvisoria = $isSenhaProvisoria;
        return $this;
    }

    /**
     * Reseta a senha do usuário. A senha nova será = ao login do usuário
     * @return void
     */
    public function resetarSenha()
    {
	$this->setIsSenhaProvisoria('S');
	$this->setSenha($this->getLogin());
    }

    /**
     * Seta a senha real do usuário e informa que não está usando a senha provisória
     * @param string $senha 
     */
    public function setSenhaReal($senha)
    {
	$this->setIsSenhaProvisoria('N');
	$this->setSenha($senha);
        return $this;
    }

    /**
     * Retorna todos os depósitos de este usuário tem acesso
     * @return ArrayCollection
     */
    public function getDepositos()
    {
	return $this->depositos;
    }

    /**
     * Retorna os ids dos depósitos que este usuário tem acesso
     * @return array
     */
    public function getIdsDepositos()
    {
	$ids = array();
	foreach ($this->getDepositos()->toArray() as $deposito) {
	    $ids[] = $deposito->getId();
	}
	return $ids;
    }
    
    /**
     * Retorna os perfis que o usuario possue
     * @return ArrayCollection
     */
    public function getPerfis() {
        return $this->perfis;
    }
    
    /**
     * Retorna os ids dos perfis que o usuario possue
     * @return array 
     */
    public function getIdsPerfis()
    {
        $ids = array();
        foreach ($this->getPerfis()->toArray() as $perfil) {
            $ids[] = $perfil->getId();
        }
        return $ids;
    }


}
