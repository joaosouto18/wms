<?php
namespace Wms\Domain\Entity\Pessoa;


/**
 * PessoaEmail
 *
 * @Table(name="PESSOA_EMAIL")
 * @Entity
 */
class Email
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
     * @var integer $idTipoEmail
     *
     * @Column(name="COD_TIPO_EMAIL", type="integer", nullable=true)
     */
    private $idTipoEmail;

    /**
     * @var string $email
     *
     * @Column(name="DSC_EMAIL", type="string", length=100, nullable=true)
     */
    private $email;

    /**
     * @var string $indEmailPrincipal
     *
     * @Column(name="IND_EMAIL_PRINCIPAL", type="string", length=1, nullable=true)
     */
    private $indEmailPrincipal;
    
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

    public function getIdTipoEmail()
    {
	return $this->idTipoEmail;
    }

    public function setIdTipoEmail($idTipoEmail)
    {
	$this->idTipoEmail = $idTipoEmail;
        return $this;
    }

    public function getEmail()
    {
	return $this->email;
    }

    public function setEmail($email)
    {
	$this->email = $email;
        return $this;
    }

    public function getIndEmailPrincipal()
    {
	return $this->indEmailPrincipal;
    }

    public function setIndEmailPrincipal($indEmailPrincipal)
    {
	$this->indEmailPrincipal = $indEmailPrincipal;
        return $this;
    }
}