<?php
namespace Wms\Domain\Entity\Pessoa;


/**
 * PessoaPapel
 *
 * @Table(name="PESSOA_PAPEL")
 * @Entity
 */
class Papel
{
    /**
     * @var integer $id
     *
     * @Column(name="COD_PESSOA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer $sequencia
     *
     * @Column(name="NUM_SEQUENCIA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $sequencia;

    /**
     * @var integer $idPapel
     *
     * @Column(name="COD_PAPEL", type="integer", nullable=true)
     */
    private $idPapel;
    
    public function getId()     
    {
	return $this->id;
    }

    public function getSequencia()
    {
	return $this->sequencia;
    }

    public function setSequencia($sequencia)
    {
	$this->sequencia = $sequencia;
        return $this;
    }

    public function getIdPapel()
    {
	return $this->idPapel;
    }

    public function setIdPapel($idPapel)
    {
	$this->idPapel = $idPapel;
        return $this;
    }
}