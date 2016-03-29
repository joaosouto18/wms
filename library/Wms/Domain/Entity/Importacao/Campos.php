<?php

namespace Wms\Domain\Entity\Importacao;

/**
 *
 * @Table(name="IMPORTACAO_CAMPOS")
 * @Entity(repositoryClass="Wms\Domain\Entity\Importacao\CamposRepository")
 */
class Campos
{
    /**
     * @Id
     * @Column(name="COD_IMPORTACAO_CAMPOS", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_IMPORTACAO_CAMPOS_01", initialValue=1, allocationSize=100)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Importacao\Arquivo")
     * @JoinColumn(name="COD_IMPORTACAO_ARQUIVO", referencedColumnName="COD_IMPORTACAO_ARQUIVO")
     */
    protected $arquivo;

    /**
     * @Column(name="NOME_CAMPO", type="string", nullable=true)
     */
    protected $nomeCampo;

    /**
     * @Column(name="POSICAO_TXT", type="string", nullable=true)
     */
    protected $posicaoTxt;

    /**
     * @Column(name="TAMANHO_INICIO", type="string", nullable=true)
     */
    protected $tamanhoInicio;

    /**
     * @Column(name="TAMANHO_FIM", type="string", nullable=true)
     */
    protected $tamanhoFim;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getArquivo()
    {
        return $this->arquivo;
    }

    /**
     * @param mixed $arquivo
     */
    public function setArquivo($arquivo)
    {
        $this->arquivo = $arquivo;
    }

    /**
     * @return mixed
     */
    public function getNomeCampo()
    {
        return $this->nomeCampo;
    }

    /**
     * @param mixed $nomeCampo
     */
    public function setNomeCampo($nomeCampo)
    {
        $this->nomeCampo = $nomeCampo;
    }

    /**
     * @return mixed
     */
    public function getPosicaoTxt()
    {
        return $this->posicaoTxt;
    }

    /**
     * @param mixed $posicaoTxt
     */
    public function setPosicaoTxt($posicaoTxt)
    {
        $this->posicaoTxt = $posicaoTxt;
    }

    /**
     * @return mixed
     */
    public function getTamanhoInicio()
    {
        return $this->tamanhoInicio;
    }

    /**
     * @param mixed $tamanhoInicio
     */
    public function setTamanhoInicio($tamanhoInicio)
    {
        $this->tamanhoInicio = $tamanhoInicio;
    }

    /**
     * @return mixed
     */
    public function getTamanhoFim()
    {
        return $this->tamanhoFim;
    }

    /**
     * @param mixed $tamanhoFim
     */
    public function setTamanhoFim($tamanhoFim)
    {
        $this->tamanhoFim = $tamanhoFim;
    }

    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'tamanhoInicio' => $this->getTamanhoInicio(),
            'tamanhoFim' => $this->getTamanhoFim(),
            'posicaoTxt' => $this->posicaoTxt(),
            'nomeCampo' => $this->getNomeCampo(),
            'arquivo' => $this->getArquivo()
        );
    }

}