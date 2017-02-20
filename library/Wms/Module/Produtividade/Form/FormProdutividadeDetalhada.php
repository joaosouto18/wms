<?php
namespace Wms\Module\Produtividade\Form;

use Zend_Form_Element;
use Zend_Form;
use Wms\Module\Web\Form;


class FormProdutividadeDetalhada extends Form
{
    public function init()
    {
        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->getEm()->getRepository('wms:Usuario');
        $usuario     = $UsuarioRepo->selectUsuario('AUXILIAR EXPEDICAO');

        $this->setAction(
            $this->getView()->url(array(
                'module' =>'produtividade',
                'controller' => 'relatorio_indicadores',
                'action' => 'relatorio-detalhado'
                ))
        );
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro'
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
            'size' => 10
        ));
        $this->addElement('text', 'mapaSeparacao', array(
            'label' => 'Mapa Separação',
            'size' => 10
        ));
        $this->addElement('date', 'dataInicio', array(
                'label' => 'Data inicial',
                'size' => 10
        ));
        $this->addElement('text', 'horaInicio', array(
            'label' => 'Hora Início',
            'size' => 5,
            'maxlength' => 5

        ));
        $this->addElement('date', 'dataFim', array(
                'label' => 'Data final',
                'size' => 10,
        ));
        $this->addElement('text', 'horaFim', array(
            'label' => 'Hora Final',
            'size' => 5,
            'maxlength' => 5
        ));
        $this->addElement('submit', 'submit', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ))
//        $this->addElement('submit', 'gerarPdf', array(
//            'label' => 'Gerar relatório',
//            'class' => 'btn',
//            'decorators' => array('ViewHelper')
//        ))
        ->addDisplayGroup(array('usuario', 'expedicao', 'carga', 'mapaSeparacao', 'dataInicio', 'horaInicio', 'dataFim', 'horaFim', 'submit', 'gerarPdf'), 'apontamento', array('legend' => 'Relatório de produtividade Detalhada')
        );
    }
}