<?php

namespace Wms\Module\Web\Form\Subform;

/**
 * Description of FiltroRecebimentoMercadoria
 *
 * @author Michel Castro
 */
class FiltroProdutosSemPicking extends \Wms\Module\Web\Form
{

    public function init()
    {
        $em = $this->getEm();
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro-produtos-sem-picking-form',
        ));
        
        $this->addElement('text', 'rua', array(
                    'size' => 10,
                    'label' => 'Rua',
                    'class' => 'focus',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup($this->getElements(), 'identificacao', array('legend' => 'Busca')
        );

    }

    /**
     *
     * @param array $params
     * @return boolean 
     */
    public function isValid($params)
    {
        extract($params);

        if (!parent::isValid($params))
            return false;

        if ($this->checkAllEmpty())
            return false;


        return true;
    }

}