<?php

namespace Wms\Module\Web\Form\Sistema;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Sistema\Parametro as ParametroEntity,
    Wms\Domain\Entity\Sistema\Parametro\Contexto as ContextoEntity,
    Wms\Domain\Entity\Sistema\Parametro\Valor as ValorEntity;

/**
 * Description of Configuracao
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Configuracao extends Form
{

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-configuracao-form', 'class' => 'saveForm configs'));

        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $repoContexto = $em->getRepository('wms:Sistema\Parametro\Contexto');
        $repoParametro = $em->getRepository('wms:Sistema\Parametro');

        $contextos = $repoContexto->getIdValue();

        foreach ($contextos as $key => $descricao) {

            $formIdentificacao = new SubForm;
            $parametros = $repoParametro->findBy(array('idContexto' => $key));
            $displayGroupArray = array();

            foreach ($parametros as $parametro) {

                $elementArray = array(
                    'size' => 20,
                    'maxlength' => 40,
                    'label' => $parametro->getTitulo(),
                    'value' => $parametro->getValor(),
                );

                switch ($parametro->getIdTipoAtributo()) {
                    case 'A':
                        $element = new \Core_Form_Element_Text($parametro->getConstante(), $elementArray);
                        break;
                    case 'D':
                        $element = new \Core_Form_Element_Date($parametro->getConstante(), $elementArray);
                        break;
                    case 'I':
                        $element = new \Core_Form_Element_Numeric($parametro->getConstante(), $elementArray);
                        break;
                    case 'R':
                        $element = new \Core_Form_Element_Numeric($parametro->getConstante(), $elementArray);
                        break;
                }

                $formIdentificacao->addElement($element);
                array_push($displayGroupArray, $parametro->getConstante());
            }

            $formIdentificacao->addDisplayGroup($displayGroupArray, $key);
            $this->addSubFormTab($descricao, $formIdentificacao, $key);
        }
    }

}

