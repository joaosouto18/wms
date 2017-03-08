<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class Carrefour extends Form
{

    public function init()
    {
          $this->setAttribs(array(
                    'method' => 'get',
                ))
              ->addElement('text', 'conteudo', array(
                  'size' => 10,
                  'label' => 'Conteúdo',
              ))
              ->addElement('text', 'peso', array(
                  'size' => 10,
                  'label' => 'Peso Líquido',
              ))
              ->addElement('text', 'validade', array(
                  'size' => 10,
                  'label' => 'Data de Validade',
              ))
              ->addElement('text', 'quantidade', array(
                  'size' => 10,
                  'label' => 'quantidade',
              ))
              ->addElement('text', 'lote', array(
                  'size' => 10,
                  'label' => 'lote',
              ))
              ->addElement('text', 'dataProducao', array(
                  'size' => 10,
                  'label' => 'data de Prod.',
              ))
              ->addElement('text', 'pesoBruto', array(
                  'size' => 10,
                  'label' => 'Peso Bruto',
              ))
              ->addElement('text', 'produto', array(
                  'size' => 10,
                  'label' => 'Nome do Produto',
              ))
              ->addElement('text', 'processador', array(
                  'size' => 10,
                  'label' => 'processador',
              ))
              ->addElement('text', 'sscc', array(
                  'size' => 10,
                  'label' => 'sscc',
              ))

              ->addElement('submit', 'submit', array(
                  'label' => 'Gerar Etiqueta',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper'),
              ))
            ->addDisplayGroup(array('conteudo', 'peso', 'validade', 'quantidade' , 'lote', 'dataProducao', 'pesoBruto', 'produto', 'processador', 'sscc', 'submit'), 'identificacao', array('legend' => 'Etiqueta Carrefour')
        );
    }

}