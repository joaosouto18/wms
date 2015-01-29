<?php
namespace Wms\Domain\Entity\Pessoa;

use \Wms\Domain\Entity\Pessoa;
/**
 * PessoaJuridica
 * @Table(name="PESSOA_JURIDICA")
 * @Entity
 */
class Juridica extends Pessoa
{

    /**
     * @var integer $id
     * @Column(name="COD_PESSOA", type="integer", nullable=false)
     * @Id
     */
    protected $id;
    /**
     * @var integer $idRamoAtividade
     * @Column(name="COD_RAMO_ATIVIDADE", type="integer", nullable=true)
     */
    protected $idRamoAtividade;
    /**
     * @var integer $idTipoOrganizacao
     * @Column(name="COD_TIPO_ORGANIZACAO", type="integer", nullable=true)
     */
    protected $idTipoOrganizacao;
    /**
     * @var datetime $dataAbertura
     * @Column(name="DAT_ABERTURA", type="datetime", nullable=true)
     */
    protected $dataAbertura;
    /**
     * @var string $nomeFantasia
     * @Column(name="NOM_FANTASIA", type="string", length=30, nullable=true)
     */
    protected $nomeFantasia;
    /**
     * @var string $cnpj
     * @Column(name="NUM_CNPJ", type="string", length=14, nullable=true)
     */
    protected $cnpj;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Organizacao\Tipo")
     * @JoinColumn(name="COD_TIPO_ORGANIZACAO", referencedColumnName="COD_SIGLA")
     */
    protected $tipoOrganizacao;
    
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Atividade\Tipo")
     * @JoinColumn(name="COD_RAMO_ATIVIDADE", referencedColumnName="COD_SIGLA")
     */
    protected $tipoRamoAtividade;
    /**
     * @var integer $inscricaoEstadual
     * @Column(name="INSCRICAO_ESTADUAL", type="integer", nullable=true)
     */
    protected $inscricaoEstadual;
    /**
     * @var integer $inscricaoMunicipal
     * @Column(name="INSCRICAO_MUNICIPAL", type="integer", nullable=true)
     */
    protected $inscricaoMunicipal;

    public function getIdRamoAtividade()
    {
	return $this->idRamoAtividade;
    }

    public function setIdRamoAtividade($idRamoAtividade)
    {
	$this->idRamoAtividade = $idRamoAtividade;
        return $this;
    }

    public function getIdTipoOrganizacao()
    {
	return $this->idTipoOrganizacao;
    }

    public function setIdTipoOrganizacao($idTipoOrganizacao)
    {
	$this->idTipoOrganizacao = $idTipoOrganizacao;
        return $this;
    }

    /**
     * Retorna data abertura da empresa
     * @return string  
     */
    public function getDataAbertura()
    {
	return ($this->dataAbertura == null) ? null : $this->dataAbertura->format('d/m/Y');
    }

    /**
     * Atribui a data de abertura da empresa
     * @param \DateTime $dataAbertura 
     */
    public function setDataAbertura(\DateTime $dataAbertura)
    {
	$this->dataAbertura = $dataAbertura;
        return $this;
    }

    public function getNomeFantasia()
    {
	return $this->nomeFantasia;
    }

    public function setNomeFantasia($nomeFantasia)
    {
	$this->nomeFantasia = mb_strtoupper($nomeFantasia, 'UTF-8');
        return $this;
    }

    public function getCnpj()
    {
	return \Core\Util\String::mask($this->cnpj, '##.###.###/####-##');
    }

    public function setCnpj($cnpj)
    {
	$cnpj = str_replace(array('.', '-', '/'), '', $cnpj);
	$this->cnpj = $cnpj;
        return $this;
    }

    /*public function getPessoa()
    {
	return $this->pessoa;
    }

    public function setPessoa(Pessoa $pessoa)
    {
	$this->pessoa = $pessoa;
    }*/

    public function getTipoOrganizacao()
    {
	return $this->tipoOrganizacao;
    }

    public function setTipoOrganizacao($tipoOrganizacao)
    {
	$this->tipoOrganizacao = $tipoOrganizacao;
        return $this;
    }

    public function getTipoRamoAtividade()     
    {
	return $this->tipoRamoAtividade;
    }

    public function setTipoRamoAtividade($tipoRamoAtividade)
    {
	$this->tipoRamoAtividade = $tipoRamoAtividade;
        return $this;
    }
    
    public function getInscricaoEstadual()     
    {
	return $this->inscricaoEstadual;
    }

    public function setInscricaoEstadual($inscricaoEstadual)
    {
	$this->inscricaoEstadual = $inscricaoEstadual;
        return $this;
    }

    public function getInscricaoMunicipal()
    {
	return $this->inscricaoMunicipal;
    }

    public function setInscricaoMunicipal($inscricaoMunicipal)
    {
	$this->inscricaoMunicipal = $inscricaoMunicipal;
        return $this;
    }
}