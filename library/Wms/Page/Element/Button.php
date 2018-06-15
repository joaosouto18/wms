<?php

namespace Wms\Page\Element;

/**
 * Description of Button
 *
 * @author Renato Medina <medinadato@gmail.com>
 * @todo Documentar classe
 */
class Button
{

    protected $label;
    protected $hrefConfig;
    protected $id;
    protected $cssClass;
    protected $type;
    protected $tag = 'button';
    protected $urlParams = array();

    public function __construct(array $options = array())
    {
	\Zend\Stdlib\Configurator::configure($this, $options);
    }

    public function getLabel()
    {
	return $this->label;
    }

    public function setLabel($label)
    {
	$this->label = $label;
	return $this;
    }

    public function getHrefConfig()
    {
	return $this->hrefConfig;
    }

    public function setHrefConfig($hrefConfig)
    {
	$this->hrefConfig = $hrefConfig;
	return $this;
    }

    public function getId()
    {
	return $this->id;
    }

    public function setId($id)
    {
	$this->id = $id;
	return $this;
    }

    public function getCssClass()
    {
	return $this->cssClass;
    }

    public function setCssClass($class)
    {
	$this->cssClass = $class;
	return $this;
    }

    public function getType()
    {
	return $this->type;
    }

    public function setType($type)
    {
	$this->type = $type;
	return $this;
    }

    public function getUrlParams()
    {
	return $this->urlParams;
    }

    public function setUrlParams($urlParams)
    {
	$this->urlParams = $urlParams;
    }

    public function getTag()
    {
	return $this->tag;
    }

    public function setTag($tag)
    {
	$this->tag = $tag;
    }

}
