<?php

namespace Wms\Module\Web\Form\Sistema;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Sistema\Parametro as ParametroEntity;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Parametro extends Form {

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-parametro-form', 'class' => 'saveForm'));

        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $repo = $em->getRepository('wms:Sistema\Parametro\Contexto');

        $formIdentificacao = new SubForm;

        $formIdentificacao->addElement('select', 'idContexto', array(
                    'label' => 'Contexto',
                    'multiOptions' => $repo->getIdValue(),
                    'required' => true,
                    'class' => 'focus',
                ))
                ->addElement('text', 'titulo', array(
                    'label' => 'Título',
                    'size' => 50,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addElement('text', 'constante', array(
                    'label' => 'Constante',
                    'class' => 'caixa-alta',
                    'size' => 35,
                    'maxlength' => 40,
                    'required' => true,
                ))
                ->addElement('radio', 'idTipoAtributo', array(
                    'label' => 'Tipo de Dado',
                    'multiOptions' => ParametroEntity::$listaTipoAtributo,
                    'required' => true,
                    'separator' => '',
                ))
                ->addDisplayGroup(array(
                    'idContexto',
                    'titulo',
                    'constante',
                    'idTipoAtributo'
                        ), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromEntity(ParametroEntity $parametro)
    {
        $values = array(
            'id' => $parametro->getId(),
            'idContexto' => $parametro->getIdContexto(),
            'titulo' => $parametro->getTitulo(),
            'constante' => $parametro->getConstante(),
            'idTipoAtributo' => $parametro->getIdTipoAtributo()
        );

        $this->setDefaults($values);
    }

}

