<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 09/03/2018
 * Time: 14:57
 */

namespace Wms\Module\Armazenagem\Form\EstoqueProprietario;

use Wms\Module\Web\Form;
use Wms\Util\Endereco;

class FiltroRelatorio extends Form
{

    public function init($utilizaGrade = 'S'){
        $proprietario = $this->getEm()->getRepository('wms:Filial')->getIdValue(true);
        $this
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro-m',
                'id' => 'filtro-movimentacao',
            ))
            ->addElement('text', 'idProduto', array(
                'size' => 12,
                'label' => 'Cod. produto',
                'class' => 'focus',
            ));
        if ($utilizaGrade == "S") {
            $this->addElement('text', 'grade', array(
                'size' => 12,
                'label' => 'Grade',
            ));
        } else {
            $this->addElement('hidden', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ));
        }
        $this->addElement('select', 'codPessoa', array(
            'label' => 'Proprietário',
            'mostrarSelecione' => true,
            'multiOptions' => $proprietario,
        ))
            ->addElement('submit', 'imprimir', array(
                'label' => 'Imprimir',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('idProduto', 'grade', 'codPessoa', 'imprimir'), 'identificacao', array('legend' => 'Filtro')
            );

    }

}
