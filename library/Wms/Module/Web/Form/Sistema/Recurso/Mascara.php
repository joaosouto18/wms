<?php

namespace Wms\Module\Web\Form\Sistema\Recurso;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Sistema\Recurso\Mascara as MascaraEntity;

class Mascara extends Form
{

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-recurso-mascara-form', 'class' => 'saveForm'));

        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $repo = $em->getRepository('wms:Sistema\Recurso');

        $formIdentificacao = new SubForm;

        $formIdentificacao->addElement('select', 'recurso', array(
                    'label' => 'Recurso',
                    'multiOptions' => $repo->getIdValue(),
                    'required' => true,
                    'class' => 'focus',
                ))
                ->addElement('date', 'datInicioVigencia', array(
                    'label' => 'Data Vigência Inicial',
                    'required' => true
                ))
                ->addElement('text', 'datFinalVigencia', array(
                    'label' => 'Data Vigência Final',
                    'class' => 'data',
                    'value' => '31/12/2049',
                    'readonly' => 'readonly',
                ))
                ->addElement('text', 'dscMascaraAuditoria', array(
                    'label' => 'Máscara',
                    'required' => true
                ))
                ->addDisplayGroup(array(
                    'recurso',
                    'dscMascaraAuditoria',
                    'datInicioVigencia',
                    'datFinalVigencia',
                        ), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(MascaraEntity $mascara)
    {
        $values = array(
            'id' => $mascara->getId(),
            'recurso' => $mascara->getRecurso()->getId(),
            'datInicioVigencia' => $mascara->getDatInicioVigencia(),
            'datFinalVigencia' => $mascara->getDatFinalVigencia(),
            'dscMascaraAuditoria' => $mascara->getDscMascaraAuditoria()
        );

        $this->setDefaults($values);
    }

}
