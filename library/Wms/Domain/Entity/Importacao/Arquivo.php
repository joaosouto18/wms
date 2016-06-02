<?php

namespace Wms\Domain\Entity\Importacao;
use Wms\Domain\Configurator;

/**
 *
 * @Table(name="IMPORTACAO_ARQUIVO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Importacao\ArquivoRepository")
 */
class Arquivo
{

    const STS_ATIVO = "S";
    const STS_INATIVO = "N";

    /**
     * @Id
     * @Column(name="COD_IMPORTACAO_ARQUIVO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_IMPORTACAO_ARQUIVO_01", initialValue=1, allocationSize=100)
     */

    protected $id;
    
    /**
     * @Column(name="TABELA_DESTINO", type="string", nullable=true)
     */
    protected $tabelaDestino;
    
    /**
     * @Column(name="NOME_ARQUIVO", type="string", nullable=true)
     */
    protected $nomeArquivo;

    /**
     * @Column(name="CARACTER_QUEBRA", type="string", nullable=true)
     */
    protected $caracterQuebra;

    /**
     * @Column(name="CABECALHO", type="string", nullable=true)
     */
    protected $cabecalho;

    /**
     * @Column(name="SEQUENCIA", type="integer", nullable=true)
     */
    protected $sequencia;

    /**
     * @Column(name="IND_ATIVO", type="string", nullable=true)
     */
    protected $ativo;

    /**
     * @return mixed
     */
    public function getAtivo()
    {
        return $this->ativo;
    }

    /**
     * @param mixed $ativo
     */
    public function setAtivo($ativo)
    {
        $this->ativo = $ativo;
    }

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
    public function getTabelaDestino()
    {
        return $this->tabelaDestino;
    }

    /**
     * @param mixed $tabelaDestino
     */
    public function setTabelaDestino($tabelaDestino)
    {
        $this->tabelaDestino = $tabelaDestino;
    }

    /**
     * @return mixed
     */
    public function getNomeArquivo()
    {
        return $this->nomeArquivo;
    }

    /**
     * @param mixed $nomeArquivo
     */
    public function setNomeArquivo($nomeArquivo)
    {
        $this->nomeArquivo = $nomeArquivo;
    }

    /**
     * @return mixed
     */
    public function getCaracterQuebra()
    {
        return $this->caracterQuebra;
    }

    /**
     * @param mixed $caracterQuebra
     */
    public function setCaracterQuebra($caracterQuebra)
    {
        $this->caracterQuebra = $caracterQuebra;
    }

    /**
     * @return mixed
     */
    public function getCabecalho()
    {
        return $this->cabecalho;
    }

    /**
     * @param mixed $cabecalho
     */
    public function setCabecalho($cabecalho)
    {
        $this->cabecalho = $cabecalho;
    }

    /**
     * @return mixed
     */
    public function getSequencia()
    {
        return $this->sequencia;
    }

    /**
     * @param mixed $sequencia
     */
    public function setSequencia($sequencia)
    {
        $this->sequencia = $sequencia;
    }

    public function toArray()
    {
        return Configurator::configureToArray($this);
    }

}