<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Wms\Domain\Entity\Produto,
    Core\Form\SubForm;

/**
 * Description of Logistico
 *
 * @author medina
 */
class Logistico extends SubForm
{

    public function init()
    {
        //repositories
        $repoFabricante = $this->getEm()->getRepository('wms:Fabricante');
        $repoClasse = $this->getEm()->getRepository('wms:Produto\Classe');

        $this->addElement('text', 'id', array(
                    'label' => 'Codigo',
                    'size' => 10,
                ))
                ->addElement('text', 'descricao', array(
                    'required' => true,
                    'label' => 'Descrição',
                    'size' => 45,
                    'maxlength' => 40,
                ))
                ->addElement('select', 'idClasse', array(
                    'label' => 'Classe',
                    'multiOptions' => $repoClasse->getIdValue()
                ))
                ->addElement('select', 'idFabricante', array(
                    'label' => 'Fabricante',
                    'multiOptions' => $repoFabricante->getIdValue()
                ))
                ->addElement('text', 'referencia', array(
                    'required' => true,
                    'label' => 'Referência',
                    'size' => 10,
                ))
                ->addElement('text', 'grade', array(
                    'required' => true,
                    'label' => 'Grade',
                    'size' => 10,
                    'maxlength' => 10
                ))
                ->addElement('numeric', 'volumes', array(
                    'required' => true,
                    'label' => 'Nº Volumes',
                    'size' => 10
                ))
                ->addElement('text', 'modoOperacao', array(
                    'required' => true,
                    'label' => 'Modo de Operação',
                    'size' => 10,
                    'maxlength' => 1
                ))
                ->addDisplayGroup(
                        array('id', 'descricao', 'grade', 'idClasse', 'idFabricante', 'modoOperacao', 'referencia', 'volumes'), 'veiculo', array('legend' => 'Dados Cadastrais')
        );
    }

    public function setDefaultsFromEntity(Produto $produto)
    {
        $values = array(
            'id' => $produto->getId(),
            'idClasse' => $produto->getClasse()->getId(),
            'idFabricante' => $produto->getFabricante()->getId(),
            'descricao' => $produto->getDescricao(),
            'grade' => $produto->getGrade(),
            'volumes' => $produto->getNumVolumes(),
            'modoOperacao' => $produto::$listaModoOperacao[$produto->getModoOperacao()],
        );

        $this->setDefaults($values);
    }

}