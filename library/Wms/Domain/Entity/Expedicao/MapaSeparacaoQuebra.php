<?php

namespace Wms\Domain\Entity\Expedicao;

/**
 *
 * @Table(name="MAPA_SEPARACAO_QUEBRA")
 * @Entity(repositoryClass="Wms\Domain\Entity\Expedicao\MapaSeparacaoQuebraRepository")
 */
class MapaSeparacaoQuebra
{

    const QUEBRA_RUA = "R";
    const QUEBRA_LINHA_SEPARACAO = "L";
    const QUEBRA_PRACA = "P";
    const QUEBRA_CLIENTE = "C";
    const QUEBRA_CARRINHO = "T";
    const QUEBRA_REENTREGA = "RE";
    const QUEBRA_PULMAO_DOCA = "PD";
    const QUEBRA_ROTA = 'RT';
    const QUEBRA_CROSS_DOCKING = 'CD';

    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(name="COD_MAPA_SEPARACAO_QUEBRA", type="integer", nullable=false)
     * @SequenceGenerator(sequenceName="SQ_MAPA_SEPARACAO_QUEB_01", initialValue=1, allocationSize=1)
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao\MapaSeparacao")
     * @JoinColumn(name="COD_MAPA_SEPARACAO", referencedColumnName="COD_MAPA_SEPARACAO")
     */
    protected $mapaSeparacao;

    /**
     * @Column(name="IND_TIPO_QUEBRA", type="string", nullable=false)
     */
    protected $tipoQuebra;

    /**
     * @Column(name="COD_QUEBRA", type="string", nullable=false)
     */
    protected $codQuebra;

    /**
     * @param mixed $codQuebra
     */
    public function setCodQuebra($codQuebra)
    {
        $this->codQuebra = $codQuebra;
    }

    /**
     * @return mixed
     */
    public function getCodQuebra()
    {
        return $this->codQuebra;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $mapaSeparacao
     */
    public function setMapaSeparacao($mapaSeparacao)
    {
        $this->mapaSeparacao = $mapaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getMapaSeparacao()
    {
        return $this->mapaSeparacao;
    }

    /**
     * @param mixed $tipoQuebra
     */
    public function setTipoQuebra($tipoQuebra)
    {
        $this->tipoQuebra = trim($tipoQuebra);
    }

    /**
     * @return mixed
     */
    public function getTipoQuebra()
    {
        return $this->tipoQuebra;
    }

}