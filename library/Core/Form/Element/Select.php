<?php

class Core_Form_Element_Select extends Core_Form_Element_Multi
{
    /**
     * Use formSelect view helper by default
     * @var string
     */
    public $helper = 'formSelect';
    
    /**
     * @var bool informa se a opção "Selecione..." deve ser visivel
     */
    protected $mostrarSelecione = true;
    
    /**
     * Informa se a opção "Selecione..." deve ser visivel
     * @param bool $v 
     */
    public function setMostrarSelecione($v)
    {
        $this->mostrarSelecione = $v;
    }
    
    public function setMultiOptions(array $options)
    {
	$this->clearMultiOptions();
        
        if(isset($options['firstOpt'])) {
            $this->options[NULL] = $options['firstOpt'];
            $options = $options['options'];
        }
        else if ($this->mostrarSelecione) {
            $this->options[NULL] = 'Selecione...';
        }        
        
	return $this->addMultiOptions($options);
    }
}