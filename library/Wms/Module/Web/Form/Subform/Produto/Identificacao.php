<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Wms\Domain\Entity\Produto as ProdutoEntity,
    Core\Form\SubForm;

/**
 * Description of Identificao
 *
 * @author medina
 */
class Identificacao extends SubForm
{

    public function init()
    {
        //repositories
        $fabricantes = $this->getEm()->getRepository('wms:Fabricante')->getIdValue();
        $classes = $this->getEm()->getRepository('wms:Produto\Classe')->getIdValue();
        $linhasSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue();
        $unitizadores = $this->getEm()->getRepository('wms:Armazenagem\Unitizador')->getIdDescricaoAssoc();
        $tiposComercializacao = $this->getEm()->getRepository('wms:Produto\TipoComercializacao')->getIdDescricao();
        $modeloRecebimento = $this->getEm()->getRepository('wms:Recebimento\ModeloRecebimento')->findAll();

        $this->addElement('hidden', 'unitizadores', array(
                    'value' => json_encode($unitizadores),
                ))
                ->addElement('text', 'id', array(
                    'label' => 'Código',
                    'size' => 10,
                    'readonly' => 'readonly',
                    'class' => 'focus',
                    'required' => true,
                ))
                ->addElement('text', 'grade', array(
                    'label' => 'Grade',
                    'size' => 10,
                    'maxlength' => 10,
                    'readonly' => 'readonly',
                    'required' => true,
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 45,
                    'maxlength' => 1000,
                    'required' => true,
                    'readonly' => 'readonly',
                ))
                ->addElement('select', 'idClasse', array(
                    'label' => 'Classe',
                    'multiOptions' => $classes,
                    'disabled' => true,
                ))
                ->addElement('select', 'idFabricante', array(
                    'label' => 'Fabricante',
                    'multiOptions' => $fabricantes,
                    'disabled' => true,
                ))   
                ->addDisplayGroup(
                        array('id', 'grade', 'descricao', 'idClasse', 'idFabricante', 'unitizadores'), 'cadastral', array('legend' => 'Dados Cadastrais')
                )
                ->addElement('select', 'idLinhaSeparacao', array(
                    'label' => 'Linha de Separação',
                    'multiOptions' => $linhasSeparacao,
                    'required' => true,
                ))
                ->addElement('select', 'idTipoComercializacao', array(
                    'label' => 'Tipo Comercialização',
                    'multiOptions' => $tiposComercializacao,
                    'required' => true,
                ))
                ->addElement('text', 'referencia', array(
                    'label' => 'Referência',
                    'size' => 10,
                    'maxlength' => 10,
                ))
                ->addElement('numeric', 'numVolumes', array(
                    'label' => 'Nº Volumes',
                    'size' => 8,
                ))
                ->addElement('text', 'codigoBarrasBase', array(
                    'label' => 'Código de Barra Base',
                    'size' => 40,
                    'maxlength' => 100,
                ))
                 ->addElement('text', 'peso', array(
                    'label' => 'Peso Total (kg)',
                    'size' => 15,
                    'readonly' => 'readonly',
                    'alt' => 'centesimal',
                ))
                ->addElement('text', 'cubagem', array(
                    'label' => 'Cubagem Total (m³)',
                    'size' => 15,
                    'readonly' => 'readonly',
                    'alt' => 'centesimal',
                ))                             
                ->addDisplayGroup(
                        array('idLinhaSeparacao', 'idTipoComercializacao', 'numVolumes', 'referencia', 'codigoBarrasBase', 'CBInterno', 'imprimirCB', 'peso', 'cubagem'), 'logistico', array('legend' => 'Dados Logisticos')
                );

                if ($modeloRecebimento[0]->getControleValidade() == 'S') {
                    $this
                        ->addElement('select', 'validade', array(
                        'label' => 'Possui validade (S/N)',
                        'multiOptions' => array(
                            'S' => 'S',
                            'N' => 'N'
                        )))
                        ->addElement('text', 'diasVidaUtil', array(
                            'label' => 'Dias para Vencimento',
                            'size' => 10,
                            'maxlength' => 4
                        ))
                        ->addDisplayGroup(
                            array('validade', 'diasVidaUtil'), 'validadeProdutos', array('legend' => 'Validade')
                        );
                }

    }

    public function setDefaultsFromEntity(ProdutoEntity $produto)
    {
        $idLinhaSeparacao = ($produto->getLinhaSeparacao()) ? $produto->getLinhaSeparacao()->getId() : 0;

        $values = array(
            'id' => $produto->getId(),
            'idClasse' => $produto->getClasse()->getId(),
            'idFabricante' => $produto->getFabricante()->getId(),
            'descricao' => $produto->getDescricao(),
            'idLinhaSeparacao' => $idLinhaSeparacao,
            'numVolumes' => $produto->getNumVolumes(),
            'referencia' => $produto->getReferencia(),
            'codigoBarrasBase' => $produto->getCodigoBarrasBase(),
            'grade' => $produto->getGrade(),
            'idTipoComercializacao' => $produto->getTipoComercializacao()->getId(),
            'validade' => $produto->getValidade(),
            'diasVidaUtil' => $produto->getDiasVidaUtil(),
        );

        $this->setDefaults($values);
    }

}