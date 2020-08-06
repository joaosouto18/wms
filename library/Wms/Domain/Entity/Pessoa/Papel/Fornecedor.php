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
class Fornecedor implements Ator, EmissorInterface
{
    
    /**
     * @var integer $id
     * @Column(name="COD_FORNECEDOR", type="integer", nullable=false)
     * @Id
     */
    protected $id;
    /**
     * @var string $idexterno
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $idExterno;
    /**
     * @var Pessoa
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_FORNECEDOR", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Pessoa
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

    public function getCodExterno()
    {
        return $this->idExterno;
    }

    public function setIdExterno($idExterno)
    {
        $this->idExterno = $idExterno;
        return $this;
    }

    /**
     * @param bool $maskOn
     * @return string
     * @throws \Exception
     */
    public function getCpfCnpj($maskOn = true)
    {
        if (is_a($this->pessoa, Pessoa\Fisica::class)) {
            return $this->pessoa->getCpf($maskOn);
        } else if (is_a($this->pessoa, Pessoa\Juridica::class)) {
            return $this->pessoa->getCnpj($maskOn);
        }
        throw new \Exception("Tipo Pessoa não identificado!");
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getNome()
    {
        if (is_a($this->pessoa, Pessoa\Fisica::class)) {
            return $this->pessoa->getNome();
        } else if (is_a($this->pessoa, Pessoa\Juridica::class)){
            return ($this->pessoa->getNomeFantasia() != null) ? $this->pessoa->getNomeFantasia() : $this->pessoa->getNome();
        }
        throw new \Exception("Tipo Pessoa não identificado!");
    }

}