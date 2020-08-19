<?php


namespace Wms\Domain\Entity\Pessoa\Papel;


use Wms\Domain\Entity\Ator;
use Wms\Domain\Entity\Pessoa;

/**
 * @Entity
 * @Table(name="Emissor")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="IND_TIPO", type="string")
 * @DiscriminatorMap({"F" = "Wms\Domain\Entity\Pessoa\Papel\Fornecedor", "C" = "Wms\Domain\Entity\Pessoa\Papel\Cliente"})
 */
class Emissor implements Ator
{
    const EMISSOR_CLIENTE = 'C';
    const EMISSOR_FORNECEDOR = 'F';

    public static $arrResponsaveis = [
        self::EMISSOR_CLIENTE => "Cliente",
        self::EMISSOR_FORNECEDOR => "Fornecedor",
    ];

    /**
     * @var integer $id
     * @Column(name="COD_EMISSOR", type="integer", nullable=false)
     * @Id
     */
    protected $id;

    /**
     * @var Pessoa
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_EMISSOR", referencedColumnName="COD_PESSOA")
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