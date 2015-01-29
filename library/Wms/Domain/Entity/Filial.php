<?php

namespace Wms\Domain\Entity;

use Wms\Domain\Entity\Ator as AtorInterface;

/**
 *
 * @Table(name="FILIAL")
 * @Entity(repositoryClass="Wms\Domain\Entity\FilialRepository")
 */
class Filial implements AtorInterface
{

    /**
     * @var integer $id
     * @Column(name="COD_FILIAL", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PESSOA_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @var integer Código da filial vindo da integração
     * @Column(name="COD_FILIAL_INTEGRACAO", type="string", nullable=false)
     */
    protected $idExterno;

    /**
     * @var string Código da filial na integraçao '104' na Simonetti
     * @Column(name="COD_EXTERNO", type="string", nullable=false)
     */
    protected $codExterno;

    /**
     * @var string Parametro indicando se vai ser obrigatório o recebimento do transbordo na expedição.
     * @Column(name="IND_RECEB_TRANSB_OBG", type="string", nullable=false)
     */
    protected $indRecTransbObg;

    /**
     * @var string Parametro indicando se a etiqueta do produto deve ser bipada na expedição de transbordo
     * @Column(name="IND_LEIT_ETQ_PROD_TRANSB_OBG", type="string", nullable=false)
     */
    protected $indLeitEtqProdTransbObg;

    /**
     * @OneToMany(targetEntity="Wms\Domain\Entity\Deposito", mappedBy="Wms\Domain\Entity\Filial")
     */
    protected $depositos;

    /**
     * @OneToOne(targetEntity="Wms\Domain\Entity\Pessoa\Juridica")
     * @JoinColumn(name="COD_FILIAL", referencedColumnName="COD_PESSOA")
     */
    protected $juridica;

    /**
     * @Column(name="IND_ATIVO", type="string", length=1, nullable=true)
     * @var string se está ativo
     */
    protected $isAtivo;

    public function __construct()
    {
	$this->depositos = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
	return $this->id;
    }

    public function setId($id)
    {
	$this->id = $id;
        return $this;
    }

    public function setCodExterno($codExterno)
    {
        $this->codExterno = $codExterno;
    }

    public function getCodExterno()
    {
        return $this->codExterno;
    }

    public function getDepositos()
    {
	return $this->depositos;
    }

    public function setDepositos($depositos)
    {
	$this->depositos = $depositos;
        return $this;
    }

    public function getPessoa()
    {
	return $this->juridica;
    }

    public function setPessoa($pessoa)
    {
	$this->juridica = $pessoa;
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
    
    public function getIsAtivo()
    {
        return ($this->isAtivo == 'S');
    }

    public function setIsAtivo($isAtivo)
    {
        $this->isAtivo = ($isAtivo) ? 'S' : 'N';
        return $this;
    }

    public function setIndLeitEtqProdTransbObg($indLeitEtqProdTransbObg)
    {
        $this->indLeitEtqProdTransbObg = $indLeitEtqProdTransbObg;
    }

    public function getIndLeitEtqProdTransbObg()
    {
        return $this->indLeitEtqProdTransbObg;
    }

    public function setIndRecTransbObg($indRecTransbObg)
    {
        $this->indRecTransbObg = $indRecTransbObg;
    }

    public function getIndRecTransbObg()
    {
        return $this->indRecTransbObg;
    }


}