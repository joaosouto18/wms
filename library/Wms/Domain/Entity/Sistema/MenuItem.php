<?php

namespace Wms\Domain\Entity\Sistema;

/**
 * Parametro
 *
 * @Table(name="MENU_ITEM")
 * @Entity(repositoryClass="Wms\Domain\Entity\Sistema\MenuItemRepository")
 */
class MenuItem
{

    /**
     * @Id
     * @Column(name="COD_MENU_ITEM", type="integer", nullable=false)
     * @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="SQ_MENU_ITEM_01", allocationSize=1, initialValue=1)
     */
    protected $id;
    /**
     * @OneToOne(targetEntity="\Wms\Domain\Entity\Sistema\Recurso\Vinculo")
     * @JoinColumn(name="COD_RECURSO_ACAO", referencedColumnName="COD_RECURSO_ACAO")
     */
    protected $permissao;
    /**
     * @OneToOne(targetEntity="\Wms\Domain\Entity\Sistema\MenuItem")
     * @JoinColumn(name="COD_PAI", referencedColumnName="COD_MENU_ITEM")
     */
    protected $pai;

    /**
     * @Column(name="DSC_MENU_ITEM", type="string", length=100, nullable=false)
     */
    protected $dscMenuItem;

    /**
     * @Column(name="NUM_PESO", type="smallint", nullable=false)
     */
    protected $peso;

    /**
     * @Column(name="DSC_URL", type="string", length=255, nullable=false)
     */
    protected $url;

    /**
     * @Column(name="DSC_TARGET", type="string", length=10, nullable=false)
     */
    protected $target;

    public function getId()
    {
        return $this->id;
    }

    public function getPermissao()
    {
        return $this->permissao;
    }

    public function setPermissao($permissao)
    {
        $this->permissao = $permissao;
        return $this;
    }

    public function getPai()
    {
        return $this->pai;
    }

    public function setPai($pai)
    {
        $this->pai = $pai;
        return $this;
    }

        
    public function getDscMenuItem()
    {
        return $this->dscMenuItem;
    }

    public function setDscMenuItem($dscMenuItem)
    {
        $this->dscMenuItem = $dscMenuItem;
        return $this;
    }

    public function getPeso()
    {
        return $this->peso;
    }

    public function setPeso($peso)
    {
        $this->peso = $peso;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

}

