<?php
namespace Wms\Domain\Entity\Pessoa\Endereco;


/**
 * @Entity
 * @Table(name="SIGLA")
 */
class Tipo
{
    const RECADO = 21;
    const COMERCIAL = 20;
    const RESIDENCIAL = 19;
    const ANTERIOR = 23;
    const ENTREGA = 22;
    const COBRANCA = 344;
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