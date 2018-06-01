<?php

namespace Wms\Module\Web\Form\NotaFiscal;

use Wms\Module\Web\Form,
    Zend_Form_Element_File,
    Core\Form\SubForm;

/**
 * Description of Importarxml
 *
 * Formulário de inserção do XML para cadastro
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class Importarxml extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'notafiscal-importarxml-form', 'class' => 'saveForm', 'action' => '/notafiscal/importarxml/index','enctype'=> 'multipart/form-data','method'=>'post'));

        $formIdentificacao = new \Core\Form\SubForm();

        /* $image = new Zend_Form_Element_File('image');
        $image->setLabel('Some picture');
        $image->addValidator('Count', false, 1);
        $image->addValidator('Size', false, 204800);
        $image->addValidator('Extension', false, 'jpg,png,gif');
        $image->setRequired(true);*/

        //$this->addElement($element);

       $image = $this->createElement('file','arquivo_xml');
        $image->setLabel('Escolha o Arquivo XML:')
            ->setRequired(true)

          ->addValidator('Count',false,1)
        ->addValidator('Size',false,'40MB')->addValidator('Extension',false,'xml,XML,xmlns,XMLNS');

        $botao = $this->createElement('submit','importar');
        $botao->setLabel('Importar')
            ->setAttribs(array('class'=>'btn header'));


        $botao->removeDecorator('label');
        $botao->removeDecorator('importar-label');



        $formIdentificacao->addElement($image)
            ->addElement($botao);

       // $formIdentificacao->addElement($image);

        $formIdentificacao->addDisplayGroup(array('arquivo_xml','importar'), 'notafiscal');

        $this->addSubFormTab('Importar XML', $formIdentificacao, 'notafiscal');

    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\NotaFiscal
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\NotaFiscal $notaFiscal)
    {
        /*
        $values = array(
            'id' => $areaArmazenagem->getId(),
            'idDeposito' => $areaArmazenagem->getIdDeposito(),
            'descricao' => $areaArmazenagem->getDescricao()
        );

        $this->setDefaults($values);*/
    }

}
