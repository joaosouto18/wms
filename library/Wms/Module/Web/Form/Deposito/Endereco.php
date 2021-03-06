<?php

namespace Wms\Module\Web\Form\Deposito;

use Wms\Domain\Entity\Deposito\EnderecoRepository;
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
        $idDeposito = (new \Zend_Session_Namespace('deposito'))->idDepositoLogado;
        $repoCaracteristica = $em->getRepository('wms:Deposito\Endereco\Caracteristica');
        $repoEstrutura = $em->getRepository('wms:Armazenagem\Estrutura\Tipo');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $repoArea = $em->getRepository('wms:Deposito\AreaArmazenagem');
        $area = $repoArea->getIdValue(array('idDeposito' => $idDeposito));

        //formulário
        $formIdentificacao = new SubForm;

        //depósito
        $formIdentificacao->addElement('hidden', 'id')
                ->addElement('hidden', 'idDeposito', array(
                    'value' => $idDeposito,
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
        $formIdentificacao
            ->addElement('multiCheckbox', 'bloqueada', array(
                'multiOptions' => ['E' => 'Entrada', 'S' => 'Saída'],
                'checkedValue' => true,
                'label' => 'Bloquear Mov.'
            ))
            ->addElement('hidden', 'status', array(
                'value' => 'D',
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
                'bloqueada',
                'ativo'
                    ), 'identificacao', array('legend' => 'Identificação'));

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao', 'endereco/cadastrar-form.phtml');
    }

    /**
     * Sets the values from entity
     * @param $endereco EnderecoEntity
     */
    public function setDefaultsFromEntity(EnderecoEntity $endereco)
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
            'ativo' => $endereco->getAtivo(),
            'idCaracteristica' => $endereco->getIdCaracteristica(),
            'idEstruturaArmazenagem' => $endereco->getIdEstruturaArmazenagem(),
            'idTipoEndereco' => $endereco->getIdTipoEndereco(),
            'status' => $endereco->getStatus(),
            'idAreaArmazenagem' => $endereco->getIdAreaArmazenagem()
        );
        if ($endereco->isBloqueadaSaida()) {
            $values['bloqueada'][] = 'S';
        }
        if ($endereco->isBloqueadaEntrada()) {
            $values['bloqueada'][] = 'E';
        }

        $this->setDefaults($values);
    }

    /**
     * @param $mass_ids
     * @param $repo EnderecoRepository
     */
    public function setMassDefaultsFromEntity($mass_ids, $repo)
    {
        $ids = implode('-',$mass_ids);
        /** @var EnderecoEntity $endInicio */
        $endInicio = $repo->find($mass_ids[0]);
        if (count($mass_ids) > 1) {
            /** @var EnderecoEntity $endFinal */
            $endFinal = $repo->find($mass_ids[count($mass_ids) - 1]);
        } else {
            /** @var EnderecoEntity $endFinal */
            $endFinal = $endInicio;
        }

        $values = array(
            'id' => $ids,
            'idDeposito' => $endInicio->getIdDeposito(),
            'inicialRua' => $endInicio->getRua(),
            'finalRua' => $endFinal->getRua(),
            'inicialPredio' => $endInicio->getPredio(),
            'finalPredio' => $endFinal->getPredio(),
            'inicialNivel' => $endInicio->getNivel(),
            'finalNivel' => $endFinal->getNivel(),
            'inicialApartamento' => $endInicio->getApartamento(),
            'finalApartamento' => $endFinal->getApartamento(),
            'ativo' => $endInicio->getAtivo(),
            'idCaracteristica' => $endInicio->getIdCaracteristica(),
            'idEstruturaArmazenagem' => $endInicio->getIdEstruturaArmazenagem(),
            'idTipoEndereco' => $endInicio->getIdTipoEndereco(),
            'status' => $endInicio->getStatus(),
            'idAreaArmazenagem' => $endInicio->getIdAreaArmazenagem()
        );
        if ($endInicio->isBloqueadaSaida()) {
            $values['bloqueada'][] = 'S';
        }
        if ($endInicio->isBloqueadaEntrada()) {
            $values['bloqueada'][] = 'E';
        }

        $this->setDefaults($values);
    }

}

