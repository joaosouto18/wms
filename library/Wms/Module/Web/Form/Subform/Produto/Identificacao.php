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
                    'size' => 13,
                    'maxlength' => 64,
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
                    'maxlength' => 50,
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
                    'size' => 10,
                    'readonly' => 'readonly',
                    'alt' => 'centesimal',
                ))
                ->addElement('text', 'cubagem', array(
                    'label' => 'Cubagem Total (m³)',
                    'size' => 13,
                    'readonly' => 'readonly',
                    'alt' => 'centesimal',
                ))                             
                ->addDisplayGroup(
                        array('idLinhaSeparacao', 'idTipoComercializacao', 'numVolumes', 'referencia', 'codigoBarrasBase', 'CBInterno', 'imprimirCB', 'peso', 'cubagem'), 'logistico', array('legend' => 'Dados Logisticos')
                );

                $this->addElement('select', 'pVariavel', array(
                    'label' => 'Possui Peso Variável?',
                    'required' => true,
                    'multiOptions' => array(
                        'S' => 'SIM',
                        'N' => 'NÃO'
                    )))
                    ->addElement('text', 'percTolerancia', array(
                        'label' => 'Porcentagem de Tolerância %',
                        'size' => 18,
                        'maxlength' => 18,
                    ))
                    ->addElement('text', 'toleranciaNominal', array(
                        'label' => 'Peso Nominal (Kg)',
                        'size' => 18,
                        'readonly' => 'readonly',
                    ))
                    ->addDisplayGroup(
                        array('pVariavel', 'percTolerancia','toleranciaNominal'), 'pesoVariavel', array('legend' => 'Peso Variável')
                    );

                $this->addElement('select', 'indFracionavel', array(
                    'label' => 'Unidade fracionável?',
                    'required' => true,
                    'multiOptions' => array(
                        'S' => 'SIM',
                        'N' => 'NÃO'
                    )
                ))->addElement('select', 'unidFracao', array(
                    'label' => 'Unidade de medida',
                    'multiOptions' => ProdutoEntity::$listaUnidadeMedida
                ))->addDisplayGroup(
                    array('indFracionavel', 'unidFracao'), 'unidComercio', array('legend' => 'Unidade de medida fracionável')
                )
                ;

                $this
                    ->addElement('select', 'validade', array(
                        'label' => 'Possui validade?',
                        'required' => true,
                        'multiOptions' => array(
                            'S' => 'SIM',
                            'N' => 'NÃO'
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
        );

        if (empty($produto->getValidade())) {
            $values['validade'] = 'N';
            $values['diasVidaUtil'] = null;
        } else {
            $values['validade'] = $produto->getValidade();
            $values['diasVidaUtil'] = $produto->getDiasVidaUtil();
        }

        if (empty($produto->getPossuiPesoVariavel())) {
            $values['pVariavel'] = 'N';
            $values['percTolerancia'] = null;
            $values['toleranciaNominal'] = null;
        } else {
            $values['pVariavel'] = $produto->getPossuiPesoVariavel();
            $values['percTolerancia'] = $produto->getPercTolerancia();
            $values['toleranciaNominal'] = $produto->getToleranciaNominal();
        }

        if (empty($produto->getIndFracionavel())) {
            $values['indFracionavel'] = 'N';
            $values['unidFracao'] = null;
        } else {
            $values['indFracionavel'] = $produto->getIndFracionavel();
            $values['unidFracao'] = $produto->getUnidadeFracao();
        }

        $this->setDefaults($values);
    }

}