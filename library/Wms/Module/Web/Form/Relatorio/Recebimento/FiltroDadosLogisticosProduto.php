<?php

namespace Wms\Module\Web\Form\Relatorio\Recebimento;

use Wms\Domain\Entity\Recebimento,
    Wms\Module\Web\Form;

/**
 * Description of FiltroDadosLogisticosProduto
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class FiltroDadosLogisticosProduto extends Form
{

    public function init()
    {
        $em = $this->getEm();
        //repositories
        $produtoClasses = $em->getRepository('wms:Produto\Classe')->getIdValue();
        $linhasSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue();
        $tiposComercializacao = $this->getEm()->getRepository('wms:Produto\TipoComercializacao')->getIdDescricao();

        //form's attr
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'target' => '_blank',
            'id' => 'filtro-dados-logisticos-produtos-form',
        ));

        $this->addElement('text', 'idRecebimento', array(
                    'size' => 10,
                    'label' => 'Recebimento',
                    'class' => 'focus',
                ))
                ->addElement('select', 'classe', array(
                    'label' => 'Classe de Produto',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $produtoClasses),
                ))
                ->addElement('select', 'idLinhaSeparacao', array(
                    'label' => 'Linha de Separação',
                    'multiOptions' => $linhasSeparacao,
                ))
                ->addElement('select', 'idTipoComercializacao', array(
                    'label' => 'Tipo Comercialização',
                    'multiOptions' => $tiposComercializacao,
                ))
                ->addElement('select', 'indDadosLogisticos', array(
                    'label' => 'Dados Logísticos',
                    'multiOptions' => array('S' => 'COM DADOS LOGISTÍCOS', 'N' => 'SEM DADOS LOGISTÍCOS'),
                ))
                ->addElement('select', 'codigoBarras', array(
                    'label' => 'Produtos com Código de Barras',
                    'multiOptions' => array('T' => 'TODOS', 'S' => 'SIM', 'N' => 'NÃO'),
                ))
                ->addElement('select', 'normaPaletizacao', array(
                    'label' => 'Produtos com Norma de Paletização',
                    'multiOptions' => array('T' => 'TODOS', 'S' => 'SIM', 'N' => 'NÃO'),
                ))
                ->addElement('select', 'enderecoPicking', array(
                    'label' => 'Produtos com Endereço de Picking',
                    'multiOptions' => array('T' => 'TODOS', 'S' => 'SIM', 'N' => 'NÃO'),
                ))
                ->addElement('select', 'estoquePulmao', array(
                    'label' => 'Produtos com estoque no Pulmão',
                    'multiOptions' => array('T' => 'TODOS', 'S' => 'SIM', 'N' => 'NÃO'),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idRecebimento', 'classe', 'idLinhaSeparacao', 'idTipoComercializacao', 'indDadosLogisticos',
                    'codigoBarras', 'normaPaletizacao','enderecoPicking','estoquePulmao','submit'), 'identificacao', array('legend' => 'Busca')
        );
    }

}