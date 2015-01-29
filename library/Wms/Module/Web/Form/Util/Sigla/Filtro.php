<?php

namespace Wms\Module\Web\Form\Util\Sigla;

use Wms\Module\Web\Form;

/**
 * Description of Filtro
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filtro extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'util-sigla-filtro-form', 'class' => 'saveForm', 'method' => 'get'));

        $em = $this->getEm();
        $repo = $em->getRepository('wms:Util\Sigla\Tipo');

        $this->addElement('select', 'tipo', array(
                    'mostrarSelecione' => false,
                    'label' => 'Tipo Sigla',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repo->getIdValue()),
                    'class' => 'focus',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('tipo', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

}
