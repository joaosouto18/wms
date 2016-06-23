<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class EquipeSeparacaoMapa extends Form
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
            ->addElement('text', 'codMapaSeparacao', array(
                'size' => 10,
                'label' => utf8_encode('Mapa Separacao'),
            ))
            ->addElement('submit', 'salvarMapa', array(
                'label' => 'Vincular',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('pessoa','codMapaSeparacao','salvarMapa'), 'identificacao', array('legend' => utf8_encode('Vincular Mapa Separação'))
            );
    }

}