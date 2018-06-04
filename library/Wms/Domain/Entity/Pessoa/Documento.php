<?php
namespace Wms\Domain\Entity\Pessoa;

/**
 * PessoaDocumento
 *
 * @Table(name="PESSOA_DOCUMENTO")
 * @Entity
 */
class Documento
{
    /**
     * @var integer $id
     * @Column(name="COD_PESSOA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $id;
    /**
     * @var integer $idTipo
     * @Column(name="COD_TIPO_DOCUMENTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $idTipo;
    /**
     * @var datetime $datExpedicao
     * @Column(name="DAT_EXPEDICAO", type="datetime", nullable=true)
     */
    protected $dataExpedicao;
    /**
     * @var string $nomOrgaoExpedidor
     * @Column(name="NOM_ORGAO_EXPEDIDOR", type="string", length=40, nullable=true)
     */
    protected $orgaoExpedidor;
    /**
     * @var string $numDocumento
     * @Column(name="NUM_DOCUMENTO", type="string", length=20, nullable=true)
     */
    protected $numero;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Documento\Tipo")
     * @JoinColumn(name="COD_TIPO_DOCUMENTO", referencedColumnName="COD_SIGLA")
     */
    protected $tipo;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa", cascade={"all"})
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")     
     */
    protected $pessoa;
    
    public function getIdPessoa()     
    {
	return $this->idPessoa;
    }

    public function getIdTipo()
    {
	return $this->idTipo;
    }

    public function getDataExpedicao()
    {
	return $this->dataExpedicao;
    }

    public function setDataExpedicao($dataExpedicao)
    {
	$this->dataExpedicao = $dataExpedicao;
        return $this;
    }

    public function getOrgaoExpedidor()
    {
	return $this->orgaoExpedidor;
    }

    public function setOrgaoExpedidor($orgaoExpedidor)
    {
	$this->orgaoExpedidor = $orgaoExpedidor;
        return $this;
    }

    public function getNumero()
    {
	return $this->numero;
    }

    public function setNumero($numero)
    {
	$this->numero = $numero;
        return $this;
    }

    public function getTipoDocumento()
    {
	return $this->tipoDocumento;
    }

    public function setTipo($tipo)
    {
	$this->tipo = $tipo;
        return $this;
    }

    public function getPessoa()
    {
	return $this->pessoa;
    }

    public function setPessoa($pessoa)
    {
	$this->pessoa = $pessoa;
        return $this;
    }
}