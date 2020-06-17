<?php
namespace Wms\Module\Integracao\Form;
use Core\Form\SubForm;
use Wms\Domain\Entity\Integracao\ConexaoIntegracao;

/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 17/06/2020
 * Time: 16:00
 */

class CadastrarIntegracaoForm extends SubForm
{
    public function init()
    {

        $this->setAttribs(array('id' => 'cadastar-integracao-form', 'class' => 'form'));
        $conexoes = [];
        foreach ($this->getEm()->createQueryBuilder()
            ->select('c.id, c.descricao')
            ->from(ConexaoIntegracao::class, 'c')->getQuery()->getResult() as $con) {
            $conexoes[$con['id']] = $con['descricao'];
        }

        $this->addElement('text', 'dscAcaoIntegracao', array(
                'label' => 'Descrição',
            ))
            ->addElement('select', 'Conexão', array(
                'mostrarSelecione' => true,
                'multiOptions' => $conexoes,
                'label' => 'Conexão',
                'require' => true
            ))
            ->addElement('text', 'tipoAcao', [
                'label' => 'Cod. Tipo Ação',
                'size' => 3,
                'require' => true
            ])
            ->addElement('checkbox', 'indUtilizaLog', array(
                'label' => 'Usa LOG',
                'checkedValue' => 'S',
                'uncheckedValue' => 'N'
            ))
            ->addElement('text', 'tipoControle', array(
                'size' => 2,
                'label' => 'Tipo Controle',
            ))
            ->addElement('text', 'tabelaReferencia', array(
                'size' => 3,
                'label' => 'Tabela Referêcia',
            ))
            ->addElement('text', 'idAcaoRelacionada', array(
                'size' => 3,
                'label' => 'Ações Relacionadas',
            ))
            ->addElement('textarea', 'query', array(
                'label' => 'Script'
            ))
            ->addElement('submit', 'salvar', array(
                'class' => 'btn',
                'label' => 'Salvar',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('dscAcaoIntegracao', 'Conexão', 'tipoAcao', 'indUtilizaLog', 'tipoControle', 'tabelaReferencia', 'idAcaoRelacionada', 'query', 'salvar'), 'cadastro-integracao', array('legend' => 'Cadastro de Integração'));
    }
}