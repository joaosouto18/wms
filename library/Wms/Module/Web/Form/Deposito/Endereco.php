<?php

namespace Wms\Module\Web\Form\Deposito;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Endereco extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $sessao = new \Zend_Session_Namespace('deposito');
        $idDeposito = $sessao->idDepositoLogado;
        $repoCaracteristica = $em->getRepository('wms:Deposito\Endereco\Caracteristica');
        $repoEstrutura = $em->getRepository('wms:Armazenagem\Estrutura\Tipo');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $repoUnitizador = $em->getRepository('wms:Armazenagem\Unitizador');
        $repoExcedente = $em->getRepository('wms:Armazenagem\LinhaSeparacao');
        $repoArea = $em->getRepository('wms:Deposito\AreaArmazenagem');
        $area = $repoArea->getIdValue(array('idDeposito' => $sessao->idDepositoLogado));
        $id = $this->getRequest()->getParam('id');

        //formulário
        $formIdentificacao = new SubForm;

        //depósito
        $formIdentificacao->addElement('hidden', 'id')
                ->addElement('hidden', 'idDeposito', array(
                    'value' => $sessao->idDepositoLogado,
                ))
                ->addElement('text', 'inicialRua', array(
                    'style' => 'width: 22px',
                    'alt' => 'enderecoRua',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'finalRua', array(
                    'style' => 'width: 22px; margin-left:20px; margin-bottom: 10px;',
                    'alt' => 'enderecoRua',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'inicialPredio', array(
                    'style' => 'width: 22px',
                    'alt' => 'enderecoPredio',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'finalPredio', array(
                    'style' => 'width: 22px; margin-left:20px; margin-bottom: 10px;',
                    'alt' => 'enderecoPredio',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'inicialNivel', array(
                    'style' => 'width: 22px',
                    'alt' => 'enderecoNivel',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'finalNivel', array(
                    'style' => 'width: 22px; margin-left:20px; margin-bottom: 10px;',
                    'alt' => 'enderecoNivel',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'inicialApartamento', array(
                    'style' => 'width: 22px',
                    'alt' => 'enderecoApartamento',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('text', 'finalApartamento', array(
                    'style' => 'width: 22px; margin-left:20px; margin-bottom: 10px;',
                    'alt' => 'enderecoApartamento',
                    'decorators' => array('ViewHelper'),
                    'required' => true
                ))
                ->addElement('select', 'lado', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => EnderecoEntity::$listaTipoLado,
                    'decorators' => array('ViewHelper'),
                    'style' => 'margin-bottom: 10px;',
                ));


        //dados do endereço
        if ($id == null) {
            $formIdentificacao->addElement('select', 'situacao', array(
                'multiOptions' => array('B' => 'Bloqueado', 'D' => 'Desbloqueado'),
                'label' => 'Situação',
                'required' => true,
            ));
        } else {
            $formIdentificacao->addElement('select', 'situacao', array(
                'multiOptions' => array('B' => 'Bloqueado', 'D' => 'Desbloqueado'),
                'label' => 'Situação',
                'required' => true,
            ));
        }
        $formIdentificacao->addElement('hidden', 'status', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => array('D' => 'Disponível', 'O' => 'Ocupado'),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'ativo', array(
                    'multiOptions' => array('S' => 'Ativo', 'N' => 'Inativo'),
                    'label' => 'Disponibilidade',
                    'required' => true,
                ))
                 ->addElement('select', 'idCaracteristica', array(
                    'multiOptions' => $repoCaracteristica->getIdValue(),
                    'label' => 'Característica',
                    'required' => true
                ))
                ->addElement('select', 'idEstruturaArmazenagem', array(
                    'multiOptions' => $repoEstrutura->getIdValue(),
                    'label' => 'Estrutura de Armazenagem',
                    'required' => true
                ))
                ->addElement('select', 'idTipoEndereco', array(
                    'multiOptions' => $repoTipo->getIdValue(),
                    'label' => 'Tipo do Endereço',
                    'required' => true
                ))
                ->addElement('select', 'idAreaArmazenagem', array(
                    'multiOptions' => $area,
                    'label' => 'Área de Armazenagem',
                    'required' => true
                ))
                ->addDisplayGroup(array(
                    'inicialRua',
                    'finalRua',
                    'inicialPredio',
                    'finalPredio',
                    'inicialNivel',
                    'finalNivel',
                    'inicialApartamento',
                    'finalApartamento',
                    'lado'
                        ), 'endereco', array('legend' => 'Endereço'))
                ->addDisplayGroup(array(
                    'id',
                    'idDeposito',
                    'idAreaArmazenagem',
                    'idCaracteristica',
                    'idTipoEndereco',
                    'idEstruturaArmazenagem',
                    'status',
                    'situacao',
                    'ativo'
                        ), 'identificacao', array('legend' => 'Identificação'));

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao', 'endereco/cadastrar-form.phtml');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito\Endereco
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\Endereco $endereco)
    {

        $values = array(
            'id' => $endereco->getId(),
            'idDeposito' => $endereco->getIdDeposito(),
            'inicialRua' => $endereco->getRua(),
            'finalRua' => $endereco->getRua(),
            'inicialPredio' => $endereco->getPredio(),
            'finalPredio' => $endereco->getPredio(),
            'inicialNivel' => $endereco->getNivel(),
            'finalNivel' => $endereco->getNivel(),
            'inicialApartamento' => $endereco->getApartamento(),
            'finalApartamento' => $endereco->getApartamento(),
            'situacao' => $endereco->getSituacao(),
            'ativo' => $endereco->getAtivo(),
            'idCaracteristica' => $endereco->getIdCaracteristica(),
            'idEstruturaArmazenagem' => $endereco->getIdEstruturaArmazenagem(),
            'idTipoEndereco' => $endereco->getIdTipoEndereco(),
            'status' => $endereco->getStatus(),
            'idAreaArmazenagem' => $endereco->getIdAreaArmazenagem()
        );

        $this->setDefaults($values);
    }

}

