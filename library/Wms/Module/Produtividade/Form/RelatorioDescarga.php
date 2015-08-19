<?php
namespace Wms\Module\Produtividade\Form;

use Wms\Module\Web\Form;

class RelatorioDescarga extends Form
{
    public function init()
    {
        $usuarioRepo = $this->getEm()->getRepository('wms:Usuario');
        $idPerfil = $usuarioRepo->getIdPerfil("DESCARREGADOR RECEBI");
        $usuariosDescarga = $usuarioRepo->getIdValueByPerfil($idPerfil);

        $this->setAction($this->getView()->url(array('module' =>'produtividade', 'controller' => 'relatorio_descarga', 'action' => 'index')))
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                ))
                ->addElement('select', 'operadores', array(
                    'label' => 'Usuários',
                    'style' => 'height:auto; width:100%',
                    'multiOptions' => $usuariosDescarga
                ))
                ->addElement('date', 'dataInicio', array(
                    'label' => 'Data Inicio',
                    'size' => 10,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataFim', array(
                    'label' => 'Data Fim',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addDisplayGroup(array('operadores', 'dataInicio', 'dataFim', 'submit'), 'identificacao', array('legend' => 'Busca')
        );
    }

}