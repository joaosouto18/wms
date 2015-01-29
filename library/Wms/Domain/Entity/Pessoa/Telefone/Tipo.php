<?php
namespace Wms\Domain\Entity\Pessoa\Telefone;

/**
 * @Entity
 * @Table(name="SIGLA")
 */
class Tipo
{
    const COBRANÇA = 434;
    const PRINCIPAL = 188;
    const FAX = 189;
    const ENTREGA = 433;
    const RECADO = 6;
    const CELULAR = 186;
    const RESIDENCIAL = 185;
    const COMERCIAL = 187;
    
    /**
     * @var integer $id
     * @Column(name="COD_SIGLA", type="integer", nullable=false)
     * @Id
     */
    protected $id;
    /**
     * @var string $nome
     * @Column(name="DSC_SIGLA", type="string", length=60, nullable=true)
     */
    protected $nome;
    
    public function getId()     
    {
	return $this->id;
    }

    public function getNome()
    {
	return $this->nome;
    }

    public function setNome($nome)
    {
	$this->nome = $nome;
        return $this;
    }
}