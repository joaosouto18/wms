<?php

namespace Wms\Module\Web\Form\Sistema\Recurso\Auditoria;

use Wms\Module\Web\Form;

class Filtro extends Form
{

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-recurso-auditoria-filtro-form', 'class' => 'saveForm'));

        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $repoFilial = $em->getRepository('wms:Filial');
        $repoRecurso = $em->getRepository('wms:Sistema\Recurso');

        $this->addElement('date', 'dataInicial', array(
                    'label' => 'Data Inicial',
                    'class' => 'focus',
                ))
                ->addElement('date', 'dataFinal', array(
                    'label' => 'Data Final',
                ))
                ->addElement('select', 'filial', array(
                    'label' => 'Filial',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoFilial->getIdValue()),
                ))
                ->addElement('select', 'recurso', array(
                    'label' => 'Recurso',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoRecurso->getIdValue()),
                ))
                ->addElement('text', 'usuario', array(
                    'label' => 'Usuário',
                ))
                ->addElement('hidden', 'nome', array(
                    'label' => 'Nome',
                    'class' => 'grande',
                    'disable' => true,
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array(
                    'dataInicial',
                    'dataFinal',
                    'filial',
                    'recurso',
                    'usuario',
                    'nome',
                    'submit'
                        ), 'identificacao', array('legend' => 'Filtros de Busca'
                ));
    }

    /**
     *
     * @param array $data
     * @return boolean 
     */
    public function isValid($data)
    {
        extract($data);

        if (!parent::isValid($data))
            return false;

        if ($this->checkAllEmpty())
            return false;

        if ((!$dataInicial && $dataFinal) || ($dataInicial && !$dataFinal)) {
            $this->addError('Favor preencher corretamente o intervalo de datas.');
            return false;
        }

        return true;
    }

}

