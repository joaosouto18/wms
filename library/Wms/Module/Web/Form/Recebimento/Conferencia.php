<?php

namespace Wms\Module\Web\Form\Conferencia;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Usuario
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Conferencia extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'recebimento-conferencia-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $produto = $em->getRepository('wms:Recebimento\Conferencia');

        $this->addElement('select', 'idProduto', array(
            'label' => 'Produto',
            'multiOptions' => $produtosOptions
        ));

        $this->addElement('text', 'quantidade', array(
            'label' => 'Quantidade',
            'alt' => 'integer',
            'class' => 'pequeno'
        ));

        $this->addDisplayGroup(array('idProduto', 'quantidade'), 'identificacao', array('legend' => 'Identificação'));
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Recebimento\Conferencia
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Recebimento\Conferencia $conferencia)
    {
        $values = array(
            'id' => $conferencia->getId(),
            'dataConferencia' => $conferencia->getDataConferencia(),
            'quantidade' => $conferencia->getQuantidade(),
            'idProduto' => $conferencia->getIdProduto(),
            '$idOrdemServico' => $conferencia->getIdOrdemServico()
        );

        $this->setDefaults($values);
    }

}