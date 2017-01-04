<?php

namespace Wms\Domain\Entity\Integracao;

use Wms\Domain\Configurator;

/**
 *
 * @Table(name="CONEXAO_INTEGRACAO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository")
 */
class ConexaoIntegracao
{

    /**
     * @Id
     * @Column(name="COD_CONEXAO_INTEGRACAO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_CONEXAO_INTEGRACAO_01", initialValue=1, allocationSize=100)
     */
    protected $id;
    
    /**
     * @Column(name="DSC_CONEXAO_INTEGRACAO", type="string", nullable=true)
     */
    protected $descricao;
    
    /**
     * @Column(name="SERVIDOR", type="string", nullable=true)
     */
    protected $servidor;

    /**
     * @Column(name="PORTA", type="string", nullable=true)
     */
    protected $porta;

    /**
     * @Column(name="USUARIO", type="string", nullable=true)
     */
    protected $usuario;

    /**
     * @Column(name="SENHA", type="string", nullable=true)
     */
    protected $senha;

    /**
     * @Column(name="DBNAME", type="string", nullable=true)
     */
    protected $dbName;

    /**
     * @Column(name="PROVEDOR", type="string", nullable=true)
     */
    protected $provedor;

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
     * @return mixed
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param mixed $descricao
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
    }

    /**
     * @return mixed
     */
    public function getServidor()
    {
        return $this->servidor;
    }

    /**
     * @param mixed $servidor
     */
    public function setServidor($servidor)
    {
        $this->servidor = $servidor;
    }

    /**
     * @return mixed
     */
    public function getPorta()
    {
        return $this->porta;
    }

    /**
     * @param mixed $porta
     */
    public function setPorta($porta)
    {
        $this->porta = $porta;
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
    public function getSenha()
    {
        return $this->senha;
    }

    /**
     * @param mixed $senha
     */
    public function setSenha($senha)
    {
        $this->senha = $senha;
    }

    /**
     * @return mixed
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @param mixed $dbName
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
    }

    /**
     * @return mixed
     */
    public function getProvedor()
    {
        return $this->provedor;
    }

    /**
     * @param mixed $provedor
     */
    public function setProvedor($provedor)
    {
        $this->provedor = $provedor;
    }

}