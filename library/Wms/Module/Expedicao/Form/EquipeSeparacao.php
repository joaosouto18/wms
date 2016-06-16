<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class EquipeSeparacao extends Form
{

    public function init()
    {
        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->getEm()->getRepository('wms:Usuario');
        $usuario     = $UsuarioRepo->selectUsuario('AUXILIAR EXPEDICAO');

          $this->setAttribs(array(
                    'method' => 'get',
                ))
                  ->addElement('select', 'pessoa', array(
                      'mostrarSelecione' => false,
                      'class' => 'medio',
                      'multiOptions' => array('options' => $usuario),
                      'decorators' => array('ViewHelper'),
                  ))
                  ->addElement('text', 'etiquetaInicial', array(
                      'size' => 10,
                      'label' => 'Etiqueta Inicial',
                  ))
                  ->addElement('text', 'etiquetaFinal', array(
                      'size' => 10,
                      'label' => 'Etiqueta Final',
                  ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Vincular',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addDisplayGroup(array('pessoa','etiquetaInicial','etiquetaFinal', 'submit'), 'identificacao', array('legend' => 'Vincular')
        );
    }

}