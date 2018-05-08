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
     * @Column(name="COD_LOTE", type="integer", nullable=false)
     */
    protected $codLote;

    /**
     * @Column(name="QUANTIDADE", type="decimal", nullable=false)
     */
    protected $quantidade;

    /**
     * @Column(name="IND_ORIGEM_LOTE", type="string", nullable=false)
     */
    protected $origem;

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
     * @param mixed $codLote
     */
    public function setCodLote($codLote)
    {
        $this->codLote = $codLote;
    }

    /**
     * @return mixed
     */
    public function getCodLote()
    {
        return $this->codLote;
    }
}