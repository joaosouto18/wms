<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;

class VolumePatrimonioFiltro extends Form
{

    public function init($label = 'Buscar', $formLabel = "Busca", $showSalvar = false, $showImprimir = false)
    {

        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-filtro-form', 'class' => 'saveForm'))
            ->setMethod('get');


        $formIdentificacao = new SubForm;
        $formIdentificacao->addElement('text', 'inicialCodigo', array(
            'label' => 'Inicio',
            'size' => 10,
            'title' => 'Obrigatório.',
        ))
        ->addElement('text', 'finalCodigo', array(
            'label' => 'Fim',
            'size' => 10,
            'title' => 'Obrigatório.',
        ))
        ->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'size' => 40,

            'title' => 'Obrigatório.',
        ))
        ->addElement('submit', 'submit', array(
            'label' => $label,
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ));

        if ($showImprimir == true) {
            $formIdentificacao
                ->addElement('submit', 'imprimir', array(
                    'label' => 'imprimir',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ));
        }
        if ($showSalvar == true) {
            $formIdentificacao
            ->addElement('submit', 'salvar', array(
                'label' => 'salvar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ));
        }

        if (($showSalvar == false) && ($showImprimir == false)) {
            $arrayObject = array (
                'inicialCodigo',
                'finalCodigo',
                'descricao',
                'submit'
            );
        } else {
            if (($showSalvar == true) && ($showImprimir == true)) {
                $arrayObject = array (
                    'inicialCodigo',
                    'finalCodigo',
                    'descricao',
                    'submit',
                    'salvar',
                    'imprimir'
                );
            } else {
                if ($showSalvar ==true) {
                    $arrayObject = array (
                        'inicialCodigo',
                        'finalCodigo',
                        'descricao',
                        'submit',
                        'salvar'
                    );
                } else {
                    $arrayObject = array (
                        'inicialCodigo',
                        'finalCodigo',
                        'descricao',
                        'submit',
                        'imprimir'
                    );
                }
            }
        }

        $formIdentificacao->addDisplayGroup($arrayObject, 'endereco', array('legend' => 'Busca'));

        $this->addSubFormTab($formLabel, $formIdentificacao, 'identificacao');
    }

    public function setDefaultsFromValue($codigoInicial, $codigoFinal, $descricao)
    {
        $values = array(
            'inicialCodigo' => $codigoInicial,
            'finalCodigo' => $codigoFinal,
            'descricao' => $descricao
        );

        $this->setDefaults($values);
    }

}