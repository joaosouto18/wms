<?php

namespace Wms\Module\Web\Form\Util;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Sigla extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'util-sigla-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $repo = $em->getRepository('wms:Util\Sigla\Tipo');

        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('select', 'idTipo', array(
                    'label' => 'Tipo de Sigla',
                    'multiOptions' => $repo->getIdValue(),
                    'required' => true,
                    'class' => 'focus',
                ))
                ->addElement('text', 'sigla', array(
                    'label' => 'Sigla',
                    'class' => 'caixa-alta',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addElement('text', 'referencia', array(
                    'label' => 'Referência',
                    'size' => 10,
                    'maxlength' => 10,
                    'class' => 'caixa-alta pequeno'
                ))
                ->addDisplayGroup(array(
                    'idTipo',
                    'sigla',
                    'referencia'
                        ), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Util\Sigla $sigla)
    {
        $values = array(
            'idTipo' => $sigla->getTipo()->getId(),
            'sigla' => $sigla->getSigla(),
            'referencia' => $sigla->getReferencia()
        );

        $this->setDefaults($values);
    }

}