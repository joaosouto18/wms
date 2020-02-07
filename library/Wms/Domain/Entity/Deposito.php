<?php

namespace Wms\Domain\Entity;

/**
 * Deposito
 *
 * @Table(name="DEPOSITO")
 * @Entity(repositoryClass="Wms\Domain\Entity\DepositoRepository")
 */
class Deposito
{

    /**
     * @var integer $id
     *
     * @Column(name="COD_DEPOSITO", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_DEPOSITO_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var int $idFilial
     *
     * @Column(name="COD_FILIAL", type="smallint", nullable=false)
     */
    protected $idFilial;

    /**
     * @var string $descricao
     *
     * @Column(name="DSC_DEPOSITO", type="string", length=60, nullable=false)
     */
    protected $descricao;

    /**
     * @var Filial $filial
     * 
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Filial")
     * @JoinColumn(name="COD_FILIAL", referencedColumnName="COD_FILIAL") 
     */
    protected $filial;

    /**
     * Usuários que tem acesso a este depósito
     * 
     * @ManyToMany(targetEntity="Wms\Domain\Entity\Usuario", mappedBy="depositos", cascade={"persist"})
     * @var Usuario[]
     */
    protected $usuarios;

    /**
     * @Column(name="IND_ATIVO", type="string", length=1, nullable=true)
     * @var string se o deposito está ativo
     */
    protected $isAtivo;

    /**
     * @Column(name="IND_USA_ENDERECAMENTO", type="string", length=1, nullable=true)
     * @var string se o deposito está tem processo de endereçamento
     */
    protected $usaEnderecamento;

    public function addUsuario(\Wms\Domain\Entity\Usuario $usuario)
    {
        $this->usuarios[] = $usuario;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getIdFilial()
    {
        return $this->idFilial;
    }

    public function setIdFilial($idFilial)
    {
        $this->idFilial = $idFilial;
        return $this;
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

    public function getFilial()
    {
        return $this->filial;
    }

    public function setFilial($filial)
    {
        $this->filial = $filial;
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

    /**
     * @return bool | string
     * @param $strVal bool
     */
    public function getUsaEnderecamento($strVal = false)
    {
        return (empty($strVal)) ? ($this->usaEnderecamento == 'S') : $this->usaEnderecamento;
    }

    /**
     * @param string|bool $usaEnderecamento
     * @return Deposito
     */
    public function setUsaEnderecamento($usaEnderecamento)
    {
        if (is_bool($usaEnderecamento)) {
            $this->usaEnderecamento = ($usaEnderecamento) ? 'S' : 'N';
        } elseif (is_string($usaEnderecamento)) {
            $this->usaEnderecamento = $usaEnderecamento;
        } else {
            $this->usaEnderecamento = 'N';
        }
        return $this;
    }

}