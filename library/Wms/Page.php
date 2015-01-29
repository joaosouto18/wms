<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Wms;

/**
 * Description of View
 *
 * @author Renato Medina <medinadato@gmail.com>
 * @todo Documentar
 */
class Page
{

    protected $id;

    public function __construct(array $options = array())
    {
	\Zend\Stdlib\Configurator::configure($this, $options);
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

}

?>
