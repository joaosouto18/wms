<?php

namespace Wms\Domain\Entity\Produto;

use Wms\Domain\Entity\Produto\Volume,
    Wms\Domain\Entity\Armazenagem\Unitizador as UnitizadorEntity,
    Core\Util\Converter;

/**
 * Description of NormaPaletizacao
 * @Entity(repositoryClass="Wms\Domain\Entity\Produto\NormaPaletizacaoRepository")
 * @Table(name="NORMA_PALETIZACAO")
 * @author daniel
 */
class NormaPaletizacao
{

    /**
     * @Id
     * @Column(name="COD_NORMA_PALETIZACAO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NORMA_PALETIZACAO_01", allocationSize=1, initialValue=1)
     * @var integer
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Armazenagem\Unitizador")
     * @JoinColumn(name="COD_UNITIZADOR", referencedColumnName="COD_UNITIZADOR")
     */
    protected $unitizador;

    /**
     * @Column(name="NUM_LASTRO", type="decimal", nullable=false)
     * @var decimal lastro (qtd. total de volumes por camada) da norma
     */
    protected $numLastro;

    /**
     * @Column(name="NUM_CAMADAS", type="decimal", nullable=false)
     * @var decimal número de camadas (níveis) da norma
     */
    protected $numCamadas;

    /**
     * @Column(name="NUM_PESO", type="decimal", nullable=false)
     * @var decimal valor de norma x peso da embalagem de recebimento
     */
    protected $numPeso;

    /**
     * @Column(name="NUM_NORMA", type="decimal", nullable=false)
     * @var decimal valor de lastro x camada
     */
    protected $numNorma;

    /**
     * @Column(name="IND_PADRAO", type="string", nullable=false)
     * @var string se a norma é padrão ou não
     */
    protected $isPadrao;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Retorna o unitizador usado pela norma
     * @return Unitizador
     */
    public function getUnitizador()
    {
        return $this->unitizador;
    }

    /**
     * Informa o unitizador usado pela norma
     * 
     * @param UnitizadorEntity $unitizadorEntity
     * @return \Wms\Domain\Entity\Produto\NormaPaletizacao 
     */
    public function setUnitizador(UnitizadorEntity $unitizadorEntity)
    {
        $this->unitizador = $unitizadorEntity;
        return $this;
    }

    /**
     * Retorna o numLastro da norma
     * 
     * @return integer
     */
    public function getNumLastro()
    {
        return $this->numLastro;
    }

    /**
     * Informa o numLastro da norma
     * 
     * @param integer $numLastro 
     */
    public function setNumLastro($numLastro)
    {
        $this->numLastro = $numLastro;
        return $this;
    }

    /**
     * Retorna o número de camadas usados pela norma
     * @return integer 
     */
    public function getNumCamadas()
    {
        return $this->numCamadas;
    }

    /**
     * Informa o número de camadas (níveis) que a norma possui
     * @param integer $numCamadas 
     */
    public function setNumCamadas($numCamadas)
    {
        $this->numCamadas = $numCamadas;
        return $this;
    }

    /**
     * Retorna o valor da norma de unitização
     * @return integer
     */
    public function getNumNorma()
    {
        return $this->numNorma;
    }

    public function setNumNorma($numNorma)
    {
        $this->numNorma = $numNorma;
        return $this;
    }

    /**
     * Calcula o valor da norma (lastro X numCamadas)
     * @return integer 
     */
    public function calculaNorma()
    {
        $this->numNorma = $this->numLastro * $this->numCamadas;
        return $this->numNorma;
    }

    public function setNumPeso($numPeso)
    {
        $this->numPeso = Converter::brToEn($numPeso, 3);
        return $this;
    }

    /**
     *
     * @param string $numPeso
     * @return \Wms\Domain\Entity\Produto\NormaPaletizacao 
     */
    public function calculaNumPeso($numPeso)
    {
        if (empty($this->numNorma))
            $this->calculaNorma();

        $this->numPeso = $this->numNorma * (Converter::brToEn($numPeso, 3));
        return $this;
    }

    /**
     * Retorna o peso total da norma
     * @return integer
     */
    public function getNumPeso()
    {
        return Converter::enToBr($this->numPeso, 3);
    }

    /**
     * Retorna se a norma é padrão
     * @return string
     */
    public function getIsPadrao()
    {
        return $this->isPadrao;
    }

    /**
     * Informa se esta norma usada como padrão pelo volume
     * @param string $isPadrao 
     */
    public function setIsPadrao($isPadrao)
    {
        if (!in_array($isPadrao, array('S', 'N')))
            throw new \InvalidArgumentException('Valor inválido');

        $this->isPadrao = $isPadrao;
        return $this;
    }

}