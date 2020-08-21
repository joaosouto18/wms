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
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_FORNECEDOR", referencedColumnName="COD_PESSOA")
     */
    protected $pessoa;

    /**
     * @return int
     */
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


    public function getCodExterno()
    {
        return $this->codExterno;
    }

    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
        return $this;
    }

    /**
     * @return Pessoa\Fisica|Pessoa\Juridica
     */
    public function getPessoa()
    {
        return $this->pessoa;
    }

    /**
     * @param Pessoa\Fisica|Pessoa\Juridica $pessoa
     */
    public function setPessoa($pessoa)
    {
        $this->pessoa = $pessoa;
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