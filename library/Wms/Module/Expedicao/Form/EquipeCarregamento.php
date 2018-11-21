<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class EquipeCarregamento extends Form
{

    public function init()
    {
        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->getEm()->getRepository('wms:Usuario');
        $usuario     = $UsuarioRepo->selectUsuario('AUXILIAR EXPEDICAO');

          $this->setAttribs(array(
                    'method' => 'get',
                ))
                ->addElement('text', 'idExpedicao', array(
                    'size' => 10,
                    'label' => 'Expedição',
                ))
                  ->addElement('select', 'pessoa', array(
                      'mostrarSelecione' => false,
                      'class' => 'medio',
                      'multiOptions' => array('firstOpt' => 'Todos', 'options' => $usuario),
                      'decorators' => array('ViewHelper'),
                  ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addDisplayGroup(array('idExpedicao','pessoa', 'submit'), 'identificacao', array('legend' => 'Pesquisar')
        );
    }

}