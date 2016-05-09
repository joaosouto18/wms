<?php

namespace Wms\Domain\Entity\Produto;

use Wms\Domain\Entity\Produto,
    Wms\Domain\Entity\Produto\NormaPaletizacao,
    Core\Util\Converter;

/**
 * Description of Dado Logistico
 * @Table(name="PRODUTO_DADO_LOGISTICO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\DadoLogisticoRepository")
 * @author Renato Medina
 */
class DadoLogistico
{    
    /**
     * @Id
     * @Column(name="COD_PRODUTO_DADO_LOGISTICO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_PRODUTO_DADO_LOGISTICO_01", allocationSize=1, initialValue=1)
     * @var integer
     */
    protected $id;
    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Produto\Embalagem")
     * @JoinColumn(name="COD_PRODUTO_EMBALAGEM", referencedColumnName="COD_PRODUTO_EMBALAGEM")
     * @var Wms\Domain\Entity\Produto\Embalagem $embalagem
     */
    protected $embalagem;
    /**
     * Norma de paletizacao do dado logistico
     * 
     * @OneToOne(targetEntity="Wms\Domain\Entity\Produto\NormaPaletizacao", cascade={"persist"})
     * @JoinColumn(name="COD_NORMA_PALETIZACAO", referencedColumnName="COD_NORMA_PALETIZACAO")
     * @var Wms\Domain\Entity\Produto\NormaPaletizacao $normaPaletizacao
     */
    protected $normaPaletizacao;
    /**
     * @Column(type="decimal", name="NUM_ALTURA")
     * @var decimal altura do volume
     */
    protected $altura;

    /**
     * @Column(type="decimal", name="NUM_LARGURA")
     * @var decimal largura do volume
     */
    protected $largura;

    /**
     * @Column(type="decimal", name="NUM_PROFUNDIDADE")
     * @var decimal profundidade do volume
     */
    protected $profundidade;

    /**
     * @Column(type="decimal", name="NUM_CUBAGEM")
     * @var decimal cubagem do volume
     */
    protected $cubagem;

    /**
     * @Column(type="decimal", name="NUM_PESO")
     * @var decimal peso do volume
     */
    protected $peso;

    /**
     * Retorna o código do volume
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function getEmbalagem()
    {
        return $this->embalagem;
    }

    public function setEmbalagem($embalagem)
    {
        $this->embalagem = $embalagem;
        return $this;
    }
    /**
     * Retorna a norma de paletizacao
     * @return integer
     */
    public function getNormaPaletizacao()
    {
        return $this->normaPaletizacao;
    }

    /**
     * Registra a norma de paletizacao
     * @param integer $normaPaletizacaoEntity
     */
    public function setNormaPaletizacao($normaPaletizacaoEntity)
    {
        $this->normaPaletizacao = $normaPaletizacaoEntity;
        return $this;
    }
    /**
     * Retorna a altura do produto
     * @return decimal
     */
    public function getAltura()
    {
        return Converter::enToBr($this->altura, 3);
    }

    /**
     * Informa a altura do volume
     * @param decimal $altura 
     */
    public function setAltura($altura)
    {
        $this->altura = Converter::brToEn($altura, 3);
        return $this;
    }

    /**
     * Retorna a largura do volume
     * @return decimal
     */
    public function getLargura()
    {
        return Converter::enToBr($this->largura, 3);
    }

    /**
     * Informa a largura do volume
     * @param decimal $largura 
     */
    public function setLargura($largura)
    {
        $this->largura = Converter::brToEn($largura, 3);
        return $this;
    }

    /**
     * Retorna a profundidade do volume
     * @return decimal
     */
    public function getProfundidade()
    {
        return Converter::enToBr($this->profundidade, 3);
    }

    /**
     * Informa a profundidade do volume
     * @param decimal $profundidade 
     */
    public function setProfundidade($profundidade)
    {
        $this->profundidade = Converter::brToEn($profundidade, 3);
        return $this;
    }

    /**
     * Retorna a cubagem do volume
     * @return decimal
     */
    public function getCubagem()
    {
        return Converter::enToBr($this->cubagem, 4);
    }

    /**
     * Informa a cubagem do volume
     * @param decimal $cubagem 
     */
    public function setCubagem($cubagem)
    {
        $this->cubagem = Converter::brToEn($cubagem, 4);
        return $this;
    }

    /**
     * Retorna o peso do volume
     * @return decimal
     */
    public function getPeso()
    {
        return Converter::enToBr($this->peso, 3);
    }

    /**
     * Informa o peso do volume
     * @param decimal $peso 
     * @param bool $importacao
     */
    public function setPeso($peso, $importacao = null)
    {
        if(empty($importacao)) {
            $this->peso = Converter::brToEn($peso, 3);
        } else {
            $this->peso = $peso;
        }
        return $this;
    }
}
