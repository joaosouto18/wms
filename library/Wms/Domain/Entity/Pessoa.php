<?php

namespace Wms\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Wms\Domain\Entity\Pessoa\Telefone,
    Wms\Domain\Entity\Pessoa\Endereco,
    Wms\Domain\Entity\Ator;

/**
 * @Entity
 * @Table(name="PESSOA")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="COD_TIPO_PESSOA", type="string")
 * @DiscriminatorMap({"F" = "Wms\Domain\Entity\Pessoa\Fisica", "J" = "Wms\Domain\Entity\Pessoa\Juridica"})
 */
class Pessoa implements Ator
{

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_PESSOA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_PESSOA_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @var datetime $dataInclusao
     * @Column(name="DTH_INCLUSAO", type="datetime", nullable=false)
     */
    protected $dataInclusao;

    /**
     * @var datetime $dataUltimaAlteracao
     *
     * @Column(name="DTH_ULTIMA_ALTERACAO", type="datetime", nullable=false)
     */
    protected $dataUltimaAlteracao;

    /**
     * @var string $nome
     *
     * @Column(name="NOM_PESSOA", type="string", length=60, nullable=false)
     */
    protected $nome;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Wms\Domain\Entity\Pessoa\Endereco", mappedBy="pessoa", cascade={"persist", "remove", "merge"})
     */
    protected $enderecos;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Wms\Domain\Entity\Pessoa\Telefone", mappedBy="pessoa", cascade={"persist", "remove", "merge"})
     */
    protected $telefones;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Wms\Domain\Entity\Pessoa\Documento", mappedBy="pessoa")
     */
    protected $documentos;

    public function __construct()
    {
        $this->enderecos = new ArrayCollection;
        $this->telefones = new ArrayCollection;
        $this->documentos = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDataInclusao()
    {
        return $this->dataInclusao;
    }

    public function setDataInclusao($dataInclusao)
    {
        $this->dataInclusao = $dataInclusao;
        return $this;
    }

    public function getDataUltimaAlteracao()
    {
        return $this->dataUltimaAlteracao;
    }

    public function setDataUltimaAlteracao($dataUltimaAlteracao)
    {
        $this->dataUltimaAlteracao = $dataUltimaAlteracao;
        return $this;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = mb_strtoupper($nome, 'UTF-8');
        return $this;
    }

    public function getIdTipoPessoa()
    {
        return $this->idTipoPessoa;
    }

    public function setIdTipoPessoa($idTipoPessoa)
    {
        $this->idTipoPessoa = $idTipoPessoa;
        return $this;
    }

    public function getTelefones()
    {
        return $this->telefones;
    }

    public function setTelefones(array $telefones)
    {
        foreach ($telefones as $telefone) {
            $this->addTelefone($telefone);
        }
        return $this;
    }

    /**
     * Adiciona um telefone à lista
     * @param Telefone $telefone 
     */
    public function addTelefone(Telefone $telefone)
    {
        $this->getTelefones()->add($telefone);
        return $this;
    }

    public function getDocumentos()
    {
        return $this->documentos;
    }

    public function setDocumentos($documentos)
    {
        $this->documentos = $documentos;
        return $this;
    }

    /**
     * Retorna lista de endedeços da pessoa
     * @return type 
     */
    public function getEnderecos()
    {
        return $this->enderecos;
    }

    /**
     * Seta uma lista de endereços
     * @param array $enderecos 
     */
    public function setEnderecos(array $enderecos)
    {
        foreach ($enderecos as $endereco) {
            $this->addEndereco($endereco);
        }
        return $this;
    }

    /**
     * Adiciona um endereço à lista
     * @param Endereco $endereco 
     */
    public function addEndereco(Endereco $endereco)
    {
        $this->getEnderecos()->add($endereco);
        return $this;
    }

}