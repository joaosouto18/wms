<?php
namespace Wms\Domain\Entity\Deposito;

/**
 * AreaArmazenagem
 *
 * @Table(name="AREA_ARMAZENAGEM")
 * @Entity(repositoryClass="Wms\Domain\Entity\Deposito\AreaArmazenagemRepository")
 */
class AreaArmazenagem
{
    /**
     * @var integer $id
     *
     * @Column(name="COD_AREA_ARMAZENAGEM", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_AREA_ARMAZENAGEM_01", allocationSize=1, initialValue=1)
     */
    private $id;
    /**
     * @var string $descricao
     *
     * @Column(name="DSC_AREA_ARMAZENAGEM", type="string", length=60, nullable=false)
     */
    private $descricao;
    /**
     * @var smallint $idDeposito
     *
     * @Column(name="COD_DEPOSITO", type="smallint", nullable=false)
     */
    private $idDeposito;
    /**
     * @var Wms\Domain\Entity\Deposito $deposito
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito")
     * @JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO") 
     */
    private $deposito;

    public function getId()
    {
	return $this->id;
    }

    public function getDescricao()
    {
	return $this->descricao;
    }

    public function setDescricao($descricao)
    {
	$this->descricao = mb_strtoupper($descricao, 'UTF-8');
        return $this;
    }

    public function getIdDeposito()
    {
	return $this->idDeposito;
    }

    public function setIdDeposito($idDeposito)
    {
	$this->idDeposito = $idDeposito;
        return $this;
    }

    public function getDeposito()
    {
	return $this->deposito;
    }

    public function setDeposito($deposito)
    {
	$this->deposito = $deposito;
        return $this;
    }

}
