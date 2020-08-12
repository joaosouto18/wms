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
class Fornecedor extends Emissor implements Ator, EmissorInterface
{

    /**
     * @var string
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    public function getCodExterno()
    {
        return $this->codExterno;
    }

    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
        return $this;
    }

}