<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class DadosProdutos extends Form
{

    public function init()
    {
        $repoLinhaSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');

        $this->setAction($this->getView()->url(array('module' =>'expedicao', 'controller' => 'relatorio_dados-produtos', 'action' => 'index')))
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                    'id' => 'filtro-dados-expedicao',
                ))
                ->addElement('multiselect', 'grandeza', array(
                    'label' => 'Linha Separação',
                    'style' => 'height:auto; width:100%',
                    'multiOptions' => $repoLinhaSeparacao->getIdValue()
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Exportar Dados csv',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addDisplayGroup(array('grandeza', 'submit'), 'identificacao', array('legend' => 'Busca')
        );
    }



}