<?php
namespace Wms\Domain\Entity\Pessoa;

/**
 * PessoaTelefone
 *
 * @Table(name="PESSOA_TELEFONE")
 * @Entity
 */
class Telefone
{
    /**
     * @var integer $id
     * @Column(name="COD_PESSOA_TELEFONE", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PESSOA_TELEFONE_01", initialValue=1, allocationSize=1)
     */
    protected $id;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa")
     * @JoinColumn(name="COD_PESSOA", referencedColumnName="COD_PESSOA")     
     */
    protected $pessoa;
    /**
     * @var integer $idTipo
     * @Column(name="COD_TIPO_TELEFONE", type="integer", nullable=true)
     */
    protected $idTipo;
    /**
     * @var integer $ddd
     * @Column(name="COD_DDD", type="integer", nullable=true)
     */
    protected $ddd;
    /**
     * @var integer $ramal
     * @Column(name="NUM_RAMAL", type="integer", nullable=true)
     */
    protected $ramal;
    /**
     * @var integer $numero
     * @Column(name="NUM_TELEFONE", type="integer", nullable=true)
     */
    protected $numero;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Pessoa\Telefone\Tipo")
     * @JoinColumn(name="COD_TIPO_TELEFONE", referencedColumnName="COD_SIGLA")
     */
    protected $tipo;
    
    public function getId()
    {
	return $this->id;
    }

    public function getIdTipo()
    {
	return $this->idTipo;
    }

    public function getDdd()
    {
	return $this->ddd;
    }

    public function setDdd($ddd)
    {
	$this->ddd = $ddd;
        return $this;
    }

    public function getRamal()
    {
	return $this->ramal;
    }

    public function setRamal($ramal)
    {
	$this->ramal = $ramal;
        return $this;
    }

    public function getNumero()
    {
	return $this->numero;
    }

    public function setNumero($numero)
    {
	$this->numero = str_replace('-', '', $numero);
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

    public function getTipo()     
    {
	return $this->tipo;
    }

    public function setTipo($tipo)
    {
	$this->tipo = $tipo;
        return $this;
    }
}