<?php

namespace Wms\Module\Web\Form\Deposito\Endereco;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;
use Wms\Util\Endereco;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filtro extends Form
{

    public function init()
    {
                
        $em = $this->getEm();
        $sessao = new \Zend_Session_Namespace('deposito');

        $repoCaracteristica = $em->getRepository('wms:Deposito\Endereco\Caracteristica');
        $repoEstrutura = $em->getRepository('wms:Armazenagem\Estrutura\Tipo');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $repoArea = $em->getRepository('wms:Deposito\AreaArmazenagem');
        $area = $repoArea->getIdValue(array('idDeposito' => $sessao->idDepositoLogado));

        $arrQtdDigitos = Endereco::getQtdDigitos();

        //form's attr
        $this->setAttribs(array('id' => 'deposito-endereco-filtro-form', 'class' => 'saveForm'))
                ->setMethod('get');

        $formIdentificacao = new SubForm;

        //endereço
        $formIdentificacao->addElement('text', 'inicialRua', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['rua'],
                    'alt' => 'enderecoRua',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalRua', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['rua'],
                    'alt' => 'enderecoRua',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'inicialPredio', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['predio'],
                    'alt' => 'enderecoPredio',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalPredio', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['predio'],
                    'alt' => 'enderecoPredio',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'inicialNivel', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['nivel'],
                    'alt' => 'enderecoNivel',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalNivel', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['nivel'],
                    'alt' => 'enderecoNivel',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'inicialApartamento', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['apartamento'],
                    'alt' => 'enderecoApartamento',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('text', 'finalApartamento', array(
                    'size' => 3,
                    'maxlength' => $arrQtdDigitos['apartamento'],
                    'alt' => 'enderecoApartamento',
                    'decorators' => array('ViewHelper'),
                    'title' => 'Obrigatório.',
                ))
                ->addElement('select', 'lado', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => EnderecoEntity::$listaTipoLado,
                    'decorators' => array('ViewHelper'),
                    'class' => 'pequeno',
                ))
                ->addElement('select', 'situacao', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => array('B' => 'Bloqueado', 'D' => 'Desbloqueado')),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'status', array(
                    'mostrarSelecione' => false,
                    'class' => 'pequeno',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => array('D' => 'Disponível', 'O' => 'Ocupado')),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'ativo', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('S' => 'Ativo', 'N' => 'Inativo'),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'idCaracteristica', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoCaracteristica->getIdValue()),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'idEstruturaArmazenagem', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoEstrutura->getIdValue()),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'idTipoEndereco', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'idAreaArmazenagem', array(
                    'mostrarSelecione' => false,
                    'class' => 'medio',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $area),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('button', 'btnBuscar', array(
                    'label' => 'Buscar',
                    'attribs' => array('id' => 'btn-buscar-endereco')
                ));


        $formIdentificacao->addDisplayGroup(array(
            'inicialRua',
            'finalRua',
            'inicialPredio',
            'finalPredio',
            'inicialNivel',
            'finalNivel',
            'inicialApartamento',
            'finalApartamento',
            'idCaracteristica',
            'idEstruturaArmazenagem',
            'idTipoEndereco',
            'lado',
            'idAreaArmazenagem',
            'situacao',
            'status',
            'ativo',
            'btnBuscar'
                ), 'endereco', array('legend' => 'Busca'));

        $this->addSubFormTab('Busca', $formIdentificacao, 'identificacao', 'forms/deposito-endereco-filtro.phtml');
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
        );

        $this->setDefaults($values);
    }

}
