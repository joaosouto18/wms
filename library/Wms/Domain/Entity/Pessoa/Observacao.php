<?php
namespace Wms\Domain\Entity\Pessoa;


/**
 * PessoaObservacao
 *
 * @Table(name="PESSOA_OBSERVACAO")
 * @Entity
 */
class Observacao
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
     * @var datetime $dataObservacao
     *
     * @Column(name="DAT_OBSERVACAO", type="datetime", nullable=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $dataObservacao;

    /**
     * @var string $idUsuario
     *
     * @Column(name="COD_USUARIO", type="string", length=25, nullable=true)
     */
    private $idUsuario;

    /**
     * @var string $observacao
     *
     * @Column(name="DSC_OBSERVACAO", type="string", length=2048, nullable=true)
     */
    private $observacao;
    
    public function getId()     
    {
	return $this->id;
    }

    public function getDataObservacao()
    {
	return $this->dataObservacao;
    }

    public function setDataObservacao($dataObservacao)
    {
	$this->dataObservacao = $dataObservacao;
        return $this;
    }

    public function getIdUsuario()
    {
	return $this->idUsuario;
    }

    public function setIdUsuario($idUsuario)
    {
	$this->idUsuario = $idUsuario;
        return $this;
    }

    public function getObservacao()
    {
	return $this->observacao;
    }

    public function setObservacao($observacao)
    {
	$this->observacao = $observacao;
        return $this;
    }
}