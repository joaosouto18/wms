<?php

namespace Wms\Domain\Entity\Deposito\Endereco;
/**
 * Description of Rua
 * @Entity
 * @Table(name="SENTIDO_RUA")
 * @author Daniel Lima <yourwebmaker@gmail.com>
 */
class SentidoRua
{
    /**
     * @Column(name="COD_SENTIDO_RUA", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_SENTIDO_RUA_01", allocationSize=1, initialValue=1)
     * @var integer código do sentido da rua
     */
    protected $id;
    /**
     * @Column(name="NUM_RUA", type="integer", nullable=false)
     * @var integer número da rua
     */
    protected $rua;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Deposito")
     * @JoinColumn(name="COD_DEPOSITO", referencedColumnName="COD_DEPOSITO") 
     * @var Wms\Domain\Entity\Deposito $deposito depósito no qual essa configuração pertence
     */
    protected $deposito;
    /**
     * @Column(name="DSC_SENTIDO_RUA", type="string", length=255, nullable=false)
     * @var string sentido da rua
     */
    protected $sentido;
    /**
     * Retorna o código da rua
     * @return integer
     */
    public function getId()
    {
	return $this->id;
    }
    /**
     * Retorna o sentido da rua
     * @return string 
     */
    public function getSentido()
    {
	return $this->sentido;
    }
    /**
     * Informa o sentido da rua
     * @param string $sentido 
     */
    public function setSentido($sentido)
    {
	if (!in_array($sentido, array('C', 'D'))) {
	    throw new \InvalidArgumentException('Sentido inválido');
	}
	$this->sentido = $sentido;
        return $this;
    }
    /**
     * Retorna o número da rua no qual este sentido se aplica
     * @return integer
     */
    public function getRua()
    {
	return $this->rua;
    }
    /**
     * Informa o número da rua no qual este sentido se aplica
     * @param integer $rua 
     */
    public function setRua($rua)
    {
	$this->rua = $rua;
        return $this;
    }
    /**
     * Informa qual depósito essa configuração pertence
     * @return Deposito
     */
    public function getDeposito()
    {
	return $this->deposito;
    }
    /**
     * Informa qual depósito essa configuração pertence
     * @param Deposito $deposito 
     */
    public function setDeposito($deposito)
    {
	$this->deposito = $deposito;
        return $this;
    }
}