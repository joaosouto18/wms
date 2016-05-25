<?php
namespace Wms\Domain\Entity\Pessoa\Papel;

use Wms\Domain\Entity\Ator;
use Wms\Domain\Entity\Pessoa;

/**
 * Fornecedor
 *
 * @Table(name="FORNECEDOR")
 * @Entity(repositoryClass="Wms\Domain\Entity\Pessoa\Papel\FornecedorRepository")
 */
class Fornecedor implements Ator
{
    /**
     * @var string $idexterno
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $idExterno;
    /**
     * @Id
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa\Juridica", cascade={"persist"})
     * @JoinColumn(name="COD_FORNECEDOR", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @return Pessoa\Juridica
     */
    public function getPessoa()
    {
	    return $this->pessoa;
    }

    public function setPessoa($pessoa)
    {
	    $this->pessoa = $pessoa;
        return $this;
    }

    public function getIdExterno()
    {
	    return $this->idExterno;
    }

    public function setIdExterno($idExterno)
    {
	    $this->idExterno = $idExterno;
        return $this;
    }

}