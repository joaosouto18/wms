<?php

namespace Wms\Module\Web\Form\Subform;

use Wms\Module\Web\Form;

/**
 * Description of FiltroNotaFiscal
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroNotaFiscal extends Form
{

    public function init()
    {
        $em = $this->getEm();

        //form's attr
        $this->setAttribs(array('id' => 'filtro-nota-fiscal', 'class' => 'filtro'));

        $this->addElement('hidden', 'idFornecedor')
                ->addElement('text', 'fornecedor', array(
                    'label' => 'Fornecedor',
                    'class' => 'focus',
                    'size' => 40,
                ))
                ->addElement('date', 'dataEntradaInicial', array(
                    'label' => 'Data Entrada Inicial',
                ))
                ->addElement('date', 'dataEntradaFinal', array(
                    'label' => 'Data Entrada Final',
                ))
                ->addElement('text', 'placa', array(
                    'label' => 'Placa',
                    'alt' => 'placaVeiculo',
                    'size' => 15,
                ))
                ->addElement('text', 'serie', array(
                    'label' => 'Serie',
                    'size' => 7,
                ))
                ->addElement('text', 'numero', array(
                    'label' => 'Nota Fiscal',
                    'size' => 20,
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('fornecedor', 'dataEntradaInicial', 'dataEntradaFinal', 'placa', 'numero', 'serie', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

    /**
     *
     * @param array $data
     * @return boolean 
     */
    public function isValid($data)
    {
        extract($data);

        if (!parent::isValid($data))
            return false;

        if ($this->checkAllEmpty())
            return false;
        
        if ( (!$dataEntradaInicial && $dataEntradaFinal) || ($dataEntradaInicial && !$dataEntradaFinal) ) {
            $this->addError('Favor preencher corretamente o intervalo de datas');
            return false;
        }

        return true;
    }

}