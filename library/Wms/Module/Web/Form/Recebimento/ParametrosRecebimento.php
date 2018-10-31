<?php
namespace Wms\Module\Web\Form\Recebimento;

use Wms\Domain\Entity\Recebimento;
use Wms\Module\Web\Form,
    Core\Form\SubForm;


class ParametrosRecebimento extends Form
{

    public function init()
    {
        $this->setAttribs(array('id' => 'form-parametros-recebimento', 'class' => 'saveForm'));

        $modeloRepo = $this->getEm()->getRepository('wms:Enderecamento\Modelo');

        $form = new SubForm;
        $form->addElement('select', 'modelo', array(
                'label' => 'Selecione um Modelo',
                'multiOptions' => $modeloRepo->getIdValue()
            ))->addElement('submit', 'salvar', array(
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
                'label' => 'Salvar',
            ))->addElement('text','descricao',array(
                'label' => "Modelo de Endereçamento Atual",
                'size' => 50
            ))
        ;

        $form->getElement('descricao')->setAttrib('disabled','disabled');
        $form->addDisplayGroup(array('modelo', 'descricao','salvar'), 'parametroRecebimento');
        $this->addSubFormTab("Modelo", $form, 'recebimento');
    }

    public function setDefaultsFromEntity(Recebimento $recebimentoEn)
    {
        $idModelo = null;
        $dscModelo = "Nenhum Modelo Selecionado";
        if ($recebimentoEn->getModeloEnderecamento() != null) {
            $idModelo = $recebimentoEn->getModeloEnderecamento()->getId();
            $dscModelo = $recebimentoEn->getModeloEnderecamento()->getDescricao();
        }

        $values = array(
            'modelo' =>$idModelo,
            'idRecebimento' => $recebimentoEn->getId(),
            'descricao' =>$dscModelo
        );
        $this->setDefaults($values);
    }

}