<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 08/05/2018
 * Time: 09:15
 */

namespace Wms\Domain\Entity\NotaFiscal;

/**
 * Nota fiscal
 *
 * @Table(name="NOTA_FISCAL_ITEM_LOTE")
 * @Entity(repositoryClass="Wms\Domain\Entity\NotaFiscal\NotaFiscalItemLoteRepository")
 */
class NotaFiscalItemLote{

    /**
     * @Id
     * @Column(name="COD_NOTA_FISCAL_ITEM_LOTE", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_NOTA_FISCAL_ITEM_LOTE_01", initialValue=1, allocationSize=1)
     **/
    protected $id;

    /**
     * @Column(name="COD_NOTA_FISCAL_ITEM", type="integer", nullable=false)
     */
    protected $codNotaFiscalItem;


    /**
     * @var string
     * @Column(name="DSC_LOTE", type="string", nullable=false)
     */
    protected $lote;

    /**
     * @Column(name="QUANTIDADE", type="decimal", nullable=false)
     */
    protected $quantidade;

    public function setQuantidade($quantidade)
    {
        $this->quantidade = $quantidade;
    }

    public function getQuantidade()
    {
        return $this->quantidade;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $codNotaFiscalItem
     */
    public function setCodNotaFiscalItem($codNotaFiscalItem)
    {
        $this->codNotaFiscalItem = $codNotaFiscalItem;
    }

    /**
     * @return mixed
     */
    public function getCodNotaFiscalItem()
    {
        return $this->codNotaFiscalItem;
    }

    /**
     * @return string
     */
    public function getLote()
    {
        return $this->lote;
    }

    /**
     * @param string $lote
     */
    public function setLote($lote)
    {
        $this->lote = $lote;
    }
}