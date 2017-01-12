<?php

namespace Wms\Module\Armazenagem\Form\Endereco;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filtro extends Form
{
	
	public function setPicking($picking) 
	{        
		$values = array(
            'picking' => $picking,
        );

        $this->setDefaults($values);
	}

    public function setUnitizador($norma)
    {
        $values = array(
            'norma' => $norma,
        );

        $this->setDefaults($values);
    }

    public function init()
    {
                
        $em = $this->getEm();

        $repoCaracteristica = $em->getRepository('wms:Deposito\Endereco\Caracteristica');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $unitiizador = $em->getRepository('wms:Armazenagem\Unitizador');

        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-filtro-form', 'class' => 'saveForm'))
                ->setMethod('get');

        $formIdentificacao = new SubForm;

        //endereço
        $formIdentificacao->addElement('text', 'inicialRua', array(
                    'size' => 3,
                    'alt' => 'enderecoRua',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalRua', array(
                    'size' => 3,
                    'alt' => 'enderecoRua',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'inicialNivel', array(
                    'size' => 3,
                    'alt' => 'enderecoNivel',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalNivel', array(
                    'size' => 3,
                    'alt' => 'enderecoNivel',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('select', 'idCaracteristica', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoCaracteristica->getIdValue()),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'idTipoEndereco', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'unitizador', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $unitiizador->getIdValue()),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'lado', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => EnderecoEntity::$listaTipoLado,
                    'decorators' => array('ViewHelper'),
                    'class' => 'pequeno',
                ))
                ->addElement('hidden', 'origin', array(
                    'size' => 3,
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('select', 'ocupado', array(
                     'mostrarSelecione' => false,
                     'multiOptions' => array('D'=> 'Somente Disponiveis','O'=> 'Somente Ocupados','T'=> 'Todos' ),
                     'decorators' => array('ViewHelper'),
                     'class' => 'pequeno',
                ))
				->addElement('hidden','picking', array (
					'label' => ''
				))
                ->addElement('hidden','norma', array (
                    'label' => ''
                ))
               ->addElement('button', 'btnBuscar', array(
                    'label' => 'Buscar',
                    'attribs' => array('id' => 'btn-buscar-endereco')
                ));

        $formIdentificacao->addDisplayGroup(array(
            'inicialRua',
            'finalRua',
            'inicialNivel',
            'finalNivel',
            'idCaracteristica',
            'idTipoEndereco',
            'unitizador',
            'lado',
            'origin',
			'disponivel',
			'picking',
            'ativo',
            'btnBuscar'
                ), 'endereco', array('legend' => 'Busca'));

        $this->addSubFormTab('Abrir Filtro', $formIdentificacao, 'identificacao', 'forms/filtro-enderecamento.phtml');
    }


    public function setOrigin($origin) {
        $values = array(
            'origin' => $origin,
        );
        $subTabVal = array();
        $subTabVal['identificacao'] = $values;
        $this->setDefaults($subTabVal);

    }
}
