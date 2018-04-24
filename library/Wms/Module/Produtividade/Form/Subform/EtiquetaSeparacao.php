<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 20/07/2016
 * Time: 15:18
 */

namespace Wms\Module\Produtividade\Form\Subform;

use Core\Form\SubForm;

class EtiquetaSeparacao extends SubForm
{
    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
        ))
            ->addElement('text', 'pessoa', array(
                'size' => 15,
                'label' => 'CPF Funcionário',
                'style' => 'width:190px;',
                'class' => 'inptText',
            ))
            ->addElement('text', 'etiquetaInicial', array(
                'size' => 17,
                'label' => 'Etiqueta Inicial',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text', 'etiquetaFinal', array(
                'size' => 17,
                'label' => 'Etiqueta Final',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text','showIntervalo', array(
                'label' => 'Intervalo',
                'class' => 'inptText',
                'id' => 'txtIntervalo',
                'size' => 4,
                'readonly' => true,
                'disabled' => true,
            ))->addElement('text','qtdConferentes', array(
                'label' => 'Qtd. Funcionários',
                'class' => 'inptText',
                'alt' => 'number',
                'id' => 'qtdConferentes',
                'size' => 4,
            ))
            ->addElement('date', 'dataInicial', array(
                'label' => 'Data Inicio',
                'id' => 'dataInicial',
                'size' => 20,
                'class' => 'inptData',
            ))
            ->addElement('date', 'dataFinal', array(
                'label' => 'Data Fim',
                'class' => 'inptData',
                'id' => 'dataFinal',
                'size' => 20,
            ))
            ->addElement('button', 'buscar', array(
                'label' => 'Buscar',
                'class' => 'btn btnSearch',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('cpf', 'cpfBusca', array(
                'size' => 15,
                'label' => 'CPF Funcionário',
                'style' => 'width:190px;',
                'id' => 'cpfBusca',
                'class' => 'inptText',
            ))
            ->addElement('text', 'etiquetaBusca', array(
                'size' => 17,
                'label' => 'Etiqueta',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text', 'expedicao', array(
                'size' => 11,
                'label' => 'Expedição',
                'class' => 'inptText',
            ))
            ->addDisplayGroup(array('qtdConferentes','etiquetaInicial','etiquetaFinal','showIntervalo','pessoa'), 'identificacao', array('legend' => 'Vincular Etiqueta Separação'))
            ->addDisplayGroup(array('dataInicial','dataFinal','cpfBusca','etiquetaBusca','expedicao','buscar'), 'consulta', array('legend' => 'Consulta'));

        $this->getElement('etiquetaInicial')->setAttrib('onkeydown','gotoFinal(event)');
        $this->getElement('etiquetaFinal')->setAttrib('onkeydown','gotoPessoa(event)');
        $this->getElement('pessoa')->setAttrib('onkeydown','gotoBuscar(event)');
        //$this->getElement('pessoa')->setAttrib('onkeypress','mascaraMutuario(this,cpfCnpj)');
    }
}