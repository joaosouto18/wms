<?php

namespace Wms\Module\Produtividade\Form;

use Zend_Form_Element;
use Zend_Form;
use Wms\Module\Web\Form;

class FormProdutividadeDetalhada extends Form {

    public function init() {
        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo = $this->getEm()->getRepository('wms:Usuario');
        $usuario = $UsuarioRepo->selectUsuario('AUXILIAR EXPEDICAO');

        $this->setAction(
                $this->getView()->url(array(
                    'module' => 'produtividade',
                    'controller' => 'relatorio_indicadores',
                    'action' => 'relatorio-detalhado'
                ))
        );
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro-expedicao-mercadoria-form',
        ));
        $this->addElement('select', 'usuario', array(
            'mostrarSelecione' => false,
            'class' => 'medio',
            'multiOptions' => array(
                'firstOpt' => 'Todos',
                'options' => $usuario
            ),
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('text', 'expedicao', array(
            'label' => 'Expedição',
            'size' => 10,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('text', 'mapaSeparacao', array(
            'label' => 'Mapa Separação',
            'size' => 10,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('text', 'identidade', array(
            'label' => 'Identificador',
            'size' => 10,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('select', 'tipoQuebra', array(
            'mostrarSelecione' => false,
            'class' => 'medio',
            'multiOptions' => array(
                'firstOpt' => 'Todos',
                'options' => array(
                    '1' => 'Mapa de Separação Consolidado'
                )),
            'decorators' => array('ViewHelper')
        ));
        $this->addElement('select', 'atividade', array(
            'label' => 'Atividade:',
            'value' => 'operacao',
            'multiOptions' => array(
                'CONF. RECEBIMENTO' => 'CONF. RECEBIMENTO',
                'ENDERECAMENTO' => 'ENDERECAMENTO',
                'DESCARREGAMENTO' => 'DESCARREGAMENTO',
                'SEPARACAO' => 'SEPARACAO',
                'CARREGAMENTO' => 'CARREGAMENTO',
                'CONF. SEPARACAO' => 'CONF. SEPARACAO',
            ),
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('date', 'dataInicio', array(
            'label' => 'Data Inicial',
            'size' => 10,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('text', 'horaInicio', array(
            'label' => 'Hora Inícial',
            'size' => 5,
            'maxlength' => 5,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('date', 'dataFim', array(
            'label' => 'Data Final',
            'size' => 10,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('text', 'horaFim', array(
            'label' => 'Hora Final',
            'size' => 5,
            'maxlength' => 5,
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('submit', 'submit', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('button', 'btnRelatorio', array(
                    'label' => 'Gerar Relatorio',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup($this->getElements(), 'apontamento', array('legend' => 'Relatório de produtividade Detalhada')
        );

        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'relatorio/indicadores/filtro.phtml'))));
    }

}
