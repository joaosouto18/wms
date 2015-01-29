<?php
namespace Wms\Domain\Entity\Pessoa;

use Wms\Domain\Entity\Pessoa;

/**
 * @Table(name="PESSOA_FISICA")
 * @Entity
 */
class Fisica extends Pessoa
{
    /**
     * @var string $sexo
     * @Column(name="COD_SEXO", type="string", length=1, nullable=true)
     */
    protected $sexo;
    /**
     * @var integer $idSituacaoConjugal
     * @Column(name="COD_SITUACAO_CONJUGAL", type="integer", nullable=true)
     */
    protected $idSituacaoConjugal;
    /**
     * @var integer $idTipoAtividade
     *
     * @Column(name="COD_TIPO_ATIVIDADE", type="integer", nullable=true)
     */
    protected $idTipoAtividade;
    /**
     * @var integer $idTipoOrganizacao
     *
     * @Column(name="COD_TIPO_ORGANIZACAO", type="integer", nullable=true)
     */
    protected $idTipoOrganizacao;
    /**
     * @var datetime $dataAdmissaoEmprego
     *
     * @Column(name="DAT_ADMISSAO_EMPREGO", type="datetime", nullable=true)
     */
    protected $dataAdmissaoEmprego;
    /**
     * @var datetime $dataExpedicaoRg
     *
     * @Column(name="DAT_EXPEDICAO_RG", type="datetime", nullable=true)
     */
    protected $dataExpedicaoRg;
    /**
     * @var datetime $dataNascimento
     *
     * @Column(name="DAT_NASCIMENTO", type="datetime", nullable=true)
     */
    protected $dataNascimento;
    /**
     * @var string $apelido
     *
     * @Column(name="DSC_APELIDO", type="string", length=30, nullable=true)
     */
    protected $apelido;
    /**
     * @var string $cargo
     *
     * @Column(name="DSC_CARGO_EMPREGO", type="string", length=60, nullable=true)
     */
    protected $cargo;
    /**
     * @var string $nacionalidade
     * @Column(name="DSC_NACIONALIDADE", type="string", length=60, nullable=true)
     */
    protected $nacionalidade;
    /**
     * @var string $naturalidade
     * @Column(name="DSC_NATURALIDADE", type="string", length=60, nullable=true)
     */
    protected $naturalidade;
    /**
     * @var string $isFalecido
     * @Column(name="IND_FALECIDO", type="string", length=1, nullable=true)
     */
    protected $isFalecido;
    /**
     * @var string $nomeEmpregador
     * @Column(name="NOM_EMPREGADOR", type="string", length=60, nullable=true)
     */
    protected $nomeEmpregador;
    /**
     * @var string $orgaoExpedidorRg
     * @Column(name="NOM_EXPEDIDOR_RG", type="string", length=20, nullable=true)
     */
    protected $orgaoExpedidorRg;
    /**
     * @var string $nomeMae
     * @Column(name="NOM_MAE", type="string", length=60, nullable=true)
     */
    protected $nomeMae;
    /**
     * @var string $nomePai
     * @Column(name="NOM_PAI", type="string", length=60, nullable=true)
     */
    protected $nomePai;
    /**
     * @var string $cpf
     *
     * @Column(name="NUM_CPF", type="string", length=11, nullable=true)
     */
    protected $cpf;
    /**
     * @var string $matriculaEmprego
     * @Column(name="NUM_MATRICULA_EMPREGO", type="string", length=20, nullable=true)
     */
    protected $matriculaEmprego;
    /**
     * @var string $rg
     * @Column(name="NUM_RG", type="string", length=15, nullable=true)
     */
    protected $rg;
    /**
     * @var string $ufOrgaoExpedidorRg
     * @Column(name="SGL_UF_EXPEDIDOR_RG", type="string", length=2, nullable=true)
     */
    protected $ufOrgaoExpedidorRg;
    /**
     * @var decimal $salario
     * @Column(name="VLR_SALARIO", type="decimal", nullable=true)
     */
    protected $salario;
    /**
     * @var integer
     * @Column(name="COD_GRAU_ESCOLARIDADE", type="integer")
     */
    protected $idGrauEscolaridade;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_GRAU_ESCOLARIDADE", referencedColumnName="COD_SIGLA")
     */
    protected $grauEscolaridade;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Util\Sigla")
     * @JoinColumn(name="COD_SITUACAO_CONJUGAL", referencedColumnName="COD_SIGLA")
     */
    protected $situacaoConjugal;

    public function getIdGrauEscolaridade()
    {
	return $this->idGrauEscolaridade;
    }

    public function setIdGrauEscolaridade($idGrauEscolaridade)
    {
	$this->idGrauEscolaridade = $idGrauEscolaridade;
    }

    /**
     * Retorna o sexo da pessoa (M => MASCULINO, F => FEMININO)
     * @return string
     */
    public function getSexo()
    {
	return $this->sexo;
    }

    /**
     * Atribui o sexo da pessoa (M => MASCULINO, F => FEMININO)
     * @param string $sexo 
     */
    public function setSexo($sexo)
    {
	if (!in_array($sexo, array('M', 'F'))) {
	    throw new \InvalidArgumentException($sexo);
	}
	$this->sexo = $sexo;
        return $this;
    }

    public function getIdSituacaoConjugal()
    {
	return $this->idSituacaoConjugal;
    }

    public function setIdSituacaoConjugal($idSituacaoConjugal)
    {
	$this->idSituacaoConjugal = $idSituacaoConjugal;
        return $this;
    }

    public function getIdTipoAtividade()
    {
	return $this->idTipoAtividade;
    }

    public function setIdTipoAtividade($idTipoAtividade)
    {
	$this->idTipoAtividade = $idTipoAtividade;
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

    public function getDataAdmissaoEmprego()
    {
	if($this->dataAdmissaoEmprego == null)
	    return $this->dataAdmissaoEmprego;
	else
	    return $this->dataAdmissaoEmprego->format('d/m/Y');
    }

    public function setDataAdmissaoEmprego(\DateTime $dataAdmissaoEmprego)
    {
	$this->dataAdmissaoEmprego = $dataAdmissaoEmprego;
        return $this;
    }

    public function getDataExpedicaoRg()
    {
	if($this->dataExpedicaoRg == null)
	    return $this->dataExpedicaoRg;
	else
	    return $this->dataExpedicaoRg->format('d/m/Y');
    }

    public function setDataExpedicaoRg(\DateTime $dataExpedicaoRg)
    {
	$this->dataExpedicaoRg = $dataExpedicaoRg;
        return $this;
    }

    public function getDataNascimento()
    {
	if($this->dataNascimento == null)
	    return $this->dataNascimento;
	else
	    return $this->dataNascimento->format('d/m/Y');
    }

    public function setDataNascimento(\DateTime $dataNascimento)
    {
	$this->dataNascimento = $dataNascimento;
        return $this;
    }

    public function getApelido()
    {
	return $this->apelido;
    }

    public function setApelido($apelido)
    {
	$this->apelido = $apelido;
        return $this;
    }

    public function getCargo()
    {
	return $this->cargo;
    }

    public function setCargo($cargo)
    {
	$this->cargo = $cargo;
        return $this;
    }

    public function getNacionalidade()
    {
	return $this->nacionalidade;
    }

    public function setNacionalidade($nacionalidade)
    {
	$this->nacionalidade = $nacionalidade;
        return $this;
    }

    public function getNaturalidade()
    {
	return $this->naturalidade;
    }

    public function setNaturalidade($naturalidade)
    {
	$this->naturalidade = $naturalidade;
        return $this;
    }

    public function getIsFalecido()
    {
	return $this->isFalecido;
    }

    public function setIsFalecido($isFalecido)
    {
	$this->isFalecido = $isFalecido;
        return $this;
    }

    public function getNomeEmpregador()
    {
	return $this->nomeEmpregador;
        return $this;
    }

    public function setNomeEmpregador($nomeEmpregador)
    {
	$this->nomeEmpregador = $nomeEmpregador;
        return $this;
    }

    public function getOrgaoExpedidorRg()
    {
	return $this->orgaoExpedidorRg;
    }

    public function setOrgaoExpedidorRg($orgaoExpedidorRg)
    {
	$this->orgaoExpedidorRg = $orgaoExpedidorRg;
        return $this;
    }

    public function getNomeMae()
    {
	return $this->nomeMae;
    }

    public function setNomeMae($nomeMae)
    {
	$this->nomeMae = $nomeMae;
        return $this;
    }

    public function getNomePai()
    {
	return $this->nomePai;
    }

    public function setNomePai($nomePai)
    {
	$this->nomePai = $nomePai;
        return $this;
    }

    public function getCpf()
    {
	return \Core\Util\String::mask($this->cpf, '###.###.###-##');
    }

    public function setCpf($cpf)
    {
	$cpf = str_replace(array('.', '-'), '', $cpf);
	$this->cpf = $cpf;
        return $this;
    }

    public function getMatriculaEmprego()
    {
	return $this->matriculaEmprego;
    }

    public function setMatriculaEmprego($matriculaEmprego)
    {
	$this->matriculaEmprego = $matriculaEmprego;
        return $this;
    }

    public function getRg()
    {
	return $this->rg;
    }

    public function setRg($rg)
    {
	$this->rg = $rg;
        return $this;
    }

    public function getUfOrgaoExpedidorRg()
    {
	return $this->ufOrgaoExpedidorRg;
    }

    public function setUfOrgaoExpedidorRg($ufOrgaoExpedidorRg)
    {
	$this->ufOrgaoExpedidorRg = $ufOrgaoExpedidorRg;
    }

    public function getSalario()
    {
	return $this->salario;
        return $this;
    }

    public function setSalario($salario)
    {	
	$this->salario = str_replace(array(',', '.'), '', $salario);
        return $this;
    }
    
    public function getGrauEscolaridade()     
    {
	return $this->grauEscolaridade;
    }
    
    public function getSituacaoConjugal()     
    {
	return $this->situacaoConjugal;
    }
}