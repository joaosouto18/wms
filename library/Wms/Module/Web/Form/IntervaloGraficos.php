<?php
namespace Wms\Module\Web\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class IntervaloGraficos extends \Core\Form
{

    public function init()
    {
	$this->setAction($this->getView()->url(array('controller' => '', 'action' => '')));

	$this->addElement('date', 'dataInicial1', array(
	    'label' => 'Data Inicial',
	));

	$this->addElement('date', 'dataInicial2', array(
	    'label' => 'Data Final',
	));

	$this->addElement('submit', 'submit', array(
	    'label' => 'Consultar',
	    'class' => 'btn',
            'decorators' => array('ViewHelper'),
	));

	$this->addDisplayGroup(
		array('dataInicial1', 'dataInicial2', 'submit'), 'identification', array('legend' => 'Intervalo para consulta dos gráficos')
	);
    }

}