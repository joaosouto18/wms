<?php

namespace Wms\Module\Armazenagem\Form\Endereco;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco as EnderecoUtil;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Index extends Form
{

    public function init()
    {
        $em = $this->getEm();

        $repoCaracteristica = $em->getRepository('wms:Deposito\Endereco\Caracteristica');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $unitiizador = $em->getRepository('wms:Armazenagem\Unitizador');

        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-filtro-form', 'class' => 'saveForm'))
                ->setMethod('get');

        $arrQtdDigitos = EnderecoUtil::getQtdDigitos();

        $formIdentificacao = new SubForm;

        //endereço
        $formIdentificacao->addElement('text', 'inicialRua', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['rua'],
                    'alt' => 'depositoEndereco',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalRua', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['rua'],
                    'alt' => 'depositoEndereco',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'inicialNivel', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['nivel'],
                    'alt' => 'depositoEndereco',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalNivel', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['nivel'],
                    'alt' => 'depositoEndereco',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'quantidade', array(
                     'size' =>3,
                     'alt' => 'depositoEndereco',
                     'decorators' => array('ViewHelper'),
                     'title' => 'Obrigatório.',
                ))
                ->addElement('date', 'dataInicial', array(
                     'size' => 20,
                     'alt' =>'dataInicial',
                     'decorators' => array('ViewHelper'),
                     'title' => 'Data Início'
                ))
                ->addElement('date', 'dataFinal', array(
                     'size' => 20,
                     'alt' =>'dataInicial',
                     'decorators' => array('ViewHelper'),
                     'title' => 'Data Finalização'
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
                ->addElement('button', 'btnBuscar', array(
                    'label' => 'Buscar',
                    'attribs' => array('id' => 'btn-buscar-endereco')
                ));


        $formIdentificacao->addDisplayGroup(array(
            'inicialRua',
            'finalRua',
            'inicialNivel',
            'finalNivel',
            'quantidade',
            'dataInicial',
            'dataFinal',
            'idCaracteristica',
            'idTipoEndereco',
            'unitizador',
            'lado',
            'btnBuscar'
                ), 'endereco', array('legend' => 'Busca'));

        $this->addSubFormTab('Abrir Filtro', $formIdentificacao, 'identificacao', 'forms/filtro-enderecamento.phtml');
    }

}
