<?php

namespace Wms\Module\Web\Form\Movimentacao\Veiculo;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Tipo extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'movimentacao-veiculo-tipo-form', 'class' => 'saveForm'));
        
	$formIdentificacao = new \Core\Form\SubForm();
	$formIdentificacao->addElement('text', 'descricao', array(
	    'label' => 'Descrição',
	    'class' => 'caixa-alta focus',
            'size' => 60,
	    'maxlength' => 60,
	));
	
	$formIdentificacao->addDisplayGroup(array('descricao'), 'identificacao');
	
	$this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
	
    }
    
    /**
     * Sets the values from entity
     * @param \Wms\Entity\TipoVeiculo $tipo 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Movimentacao\Veiculo\Tipo $tipo)
    {
	$values = array(
	    'identificacao' => array(
		'descricao' => $tipo->getDescricao(),
	    )
	);
	
	$this->setDefaults($values);
    }

}

?>
