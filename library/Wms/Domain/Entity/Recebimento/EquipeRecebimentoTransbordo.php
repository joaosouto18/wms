<?php

namespace Wms\Domain\Entity\Recebimento;

/**
 *
 * @Table(name="EQUIPE_RECEBIMENTO_TRANSBORDO")
 * @Entity(repositoryClass="Wms\Domain\Entity\Recebimento\EquipeRecebimentoTransbordoRepository")
 */
class EquipeRecebimentoTransbordo
{

    /**
     * @Id
     * @Column(name="COD_EQUIPE_TRANSBORDO", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_EQUIPE_TRANSB_01", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @Column(name="DTH_VINCULO", type="datetime", nullable=false)
     */
    protected $dataVinculo;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Expedicao")
     * @JoinColumn(name="COD_EXPEDICAO", referencedColumnName="COD_EXPEDICAO")
     */
    protected $expedicao;

    /**
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Usuario")
     * @JoinColumn(name="COD_USUARIO", referencedColumnName="COD_USUARIO")
     */
    protected $usuario;

    /**
     * @param mixed $dataVinculo
     */
    public function setDataVinculo($dataVinculo)
    {
        $this->dataVinculo = $dataVinculo;
    }

    /**
     * @return mixed
     */
    public function getDataVinculo()
    {
        return $this->dataVinculo;
    }

    /**
     * @param mixed $expedicao
     */
    public function setExpedicao($expedicao)
    {
        $this->expedicao = $expedicao;
    }

    /**
     * @return mixed
     */
    public function getExpedicao()
    {
        return $this->expedicao;
    }


    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * @return mixed
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

}