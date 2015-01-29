<?php
namespace Wms\Domain\Entity\Pessoa;


/**
 * PessoaRelacionamento
 *
 * @Table(name="PESSOA_RELACIONAMENTO")
 * @Entity
 */
class Relacionamento
{
    /**
     * @var integer $id
     *
     * @Column(name="COD_PESSOA_PRINCIPAL", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer $idPessoaSecundaria
     *
     * @Column(name="COD_PESSOA_SECUNDARIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $idPessoaSecundaria;

    /**
     * @var integer $idTipoRelacionamento
     *
     * @Column(name="COD_TIPO_RELACIONAMENTO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $idTipoRelacionamento;

    /**
     * @var string $indRelacionamentoAtivo
     *
     * @Column(name="IND_RELACIONAMENTO_ATIVO", type="string", length=1, nullable=true)
     */
    private $indRelacionamentoAtivo;
    
    public function getId()     
    {
	return $this->id;
    }

    public function getIdPessoaSecundaria()
    {
	return $this->idPessoaSecundaria;
    }

    public function setIdPessoaSecundaria($idPessoaSecundaria)
    {
	$this->idPessoaSecundaria = $idPessoaSecundaria;
        return $this;
    }

    public function getIdTipoRelacionamento()
    {
	return $this->idTipoRelacionamento;
    }

    public function setIdTipoRelacionamento($idTipoRelacionamento)
    {
	$this->idTipoRelacionamento = $idTipoRelacionamento;
        return $this;
    }

    public function getIndRelacionamentoAtivo()
    {
	return $this->indRelacionamentoAtivo;
    }

    public function setIndRelacionamentoAtivo($indRelacionamentoAtivo)
    {
	$this->indRelacionamentoAtivo = $indRelacionamentoAtivo;
        return $this;
    }
}