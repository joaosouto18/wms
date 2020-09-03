<?php

namespace Wms\Module\Web\Form\Expedicao;

use Wms\Domain\Entity\Expedicao\TipoPedido as TipoPedidoEn;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class TipoPedido extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'expedicao-tipo-pedido-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'class' => 'caixa-alta focus',
            'size' => 60,
            'maxlength' => 60
        ))->addElement('text', 'codExterno', array(
            'label' => 'Código ERP',
            'class' => 'caixa-alta',
            'size' => 60,
            'maxlength' => 60
        ));

        $formIdentificacao->addDisplayGroup(array('descricao', 'codExterno'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param TipoPedidoEn $tipo
     */
    public function setDefaultsFromEntity(TipoPedidoEn $tipo)
    {
        $values = array(
            'identificacao' => array(
                'descricao' => $tipo->getDescricao(),
                'codExterno' => $tipo->getCodExterno(),
            )
        );

        $this->setDefaults($values);
    }

}