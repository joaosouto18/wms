<?php

///////////////////////////////////////////////////////////////////
////////////////////////     CONTROLLER    ////////////////////////
///////////////////////////////////////////////////////////////////
use Wms\Module\Web\Form\Exemplo as ExemploForm;

$form = new ExemploForm;

$values = $form->getParams();

$form->setSession($values)
        ->populate($values);

$this->view->form = $form;

///////////////////////////////////////////////////////////////////
///////////////////////////     FORM    ///////////////////////////
///////////////////////////////////////////////////////////////////
$formExemplo = new \Core\Form\SubForm();

///////////////////////////////////////////////////////////////////
/////////////////////////     ATRIBUTOS    ////////////////////////
///////////////////////////////////////////////////////////////////
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'nameClass',
            'target' => '_blank',
            'id' => 'id-form',
        ));

///////////////////////////////////////////////////////////////////
///////////////////////////     BUTTON    /////////////////////////
///////////////////////////////////////////////////////////////////
$formExemplo->addElement('button', 'btnEndereco', array(
            'label' => 'Adicionar',
            'attribs' => array(
                'id' => 'btn-salvar-endereco',
                'class' => 'btn',
                'style' => 'display:block; clear:both;',
            ),
            'decorators' => array('ViewHelper'),
        ))
        ->addElement('button', 'btnBuscar', array(
            'label' => 'Buscar',
            'attribs' => array('id' => 'btn-buscar-endereco')
        ))


///////////////////////////////////////////////////////////////////
////////////////////////////     CEP    ///////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('cep', 'cep', array(
            'label' => 'CEP',
            'required' => true,
        ))

///////////////////////////////////////////////////////////////////
///////////////     CHECKBOX / MULTICHECKBOX    ///////////////////
///////////////////////////////////////////////////////////////////
        /*
         * EXEMPLO DE CHECKBOX
         */
        //$acoes = $em->getRepository('wms:Sistema\Acao')->findAll();
        //foreach ($acoes as $acao) {
        //
        //$nomCheckbox = $acao->getId() . 'chk';
        //$formAcoes->addElement('checkbox', $nomCheckbox, array(
        //              'label' => $acao->getNome(),
        //              'checkedValue' => $acao->getId()
        //));
        //
        //$nomeElementos[] = $nomCheckbox;
        //}


        /*
         * EXEMPLO DE MULTI CHECKBOX
         */
        ->addElement('multiCheckbox', 'perfis', array(
            'label' => 'Perfil(s) de Acesso',
            'multiOptions' => $repoPerfil->getIdValue(),
            'required' => true
        ))

        //$regras = array();
        //foreach ($repo->findAll() as $regra) {
        //   $regras[$regra->getId()] = $regra->getDescricao();
        //}
        ->addElement('multiCheckbox', 'regras', array(
            'multiOptions' => $regras,
            'label' => 'Regras vinculadas a esta caracteristica'
        ))

///////////////////////////////////////////////////////////////////
///////////////////////////     CNPJ    ///////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('cnpj', 'cnpj', array(
            'label' => 'CNPJ',
            'required' => true
        ))

///////////////////////////////////////////////////////////////////
////////////////////////////     CPF    ///////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('cpf', 'cpf', array(
            'label' => 'CPF',
            'required' => true
        ))

///////////////////////////////////////////////////////////////////
///////////////////////////     DATE    ///////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('date', 'dataInicial', array(
            'decorators' => array('ViewHelper'),
            'class' => 'focus',
        ))

///////////////////////////////////////////////////////////////////
//////////////////////////     MONEY    ///////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('money', 'salario', array(
            'label' => 'Valor Salário (R$)',
            'required' => true,
        ))

///////////////////////////////////////////////////////////////////
/////////////////////////     NUMERIC    //////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('numeric', 'quantidade', array(
            'label' => 'Quantidade',
            'size' => 10
        ))

///////////////////////////////////////////////////////////////////
////////////////////////     PASSWORD    //////////////////////////
///////////////////////////////////////////////////////////////////       
        ->addElement('password', 'senha', array(
            'label' => 'Senha',
            'required' => true,
            'validators' => array(
                array('StringLength', false, array(5, 30)),
            ),
        ))
        ->addElement('password', 'password', array(
            'label' => 'Senha',
            'required' => true,
            'size' => 25,
            'maxlength' => 15,
        ))

///////////////////////////////////////////////////////////////////
//////////////////////////     PHONE    ///////////////////////////
///////////////////////////////////////////////////////////////////        
        ->addElement('phone', 'telefone1', array(
            'required' => true,
            'label' => 'Telefone(01)'
        ))

///////////////////////////////////////////////////////////////////
//////////////////////////     RADIO    ///////////////////////////
///////////////////////////////////////////////////////////////////        
        ->addElement('radio', 'isAtivo', array(
            'label' => 'Usuário Ativo?',
            'multiOptions' => array('S' => 'Sim', 'N' => 'Não'),
            'required' => true,
            'value' => 'S',
            'separator' => ''
        ))
        ->addElement('radio', 'sexo', array(
            'label' => 'Sexo',
            'multiOptions' => array('M' => 'MASCULINO', 'F' => 'FEMININO'),
            'separator' => '',
        ))
        ->addElement('radio', 'descricao', array(
            'label' => 'Sentido',
            'multiOptions' => array(array(1 => 'Crescente', 2 => 'Decrescente')),
            'class' => 'class',
            'required' => true
        ))
        ->addElement('radio', 'idTipoAtributo', array(
            'label' => 'Tipo de Dado',
            'multiOptions' => ParametroEntity::$listaTipoAtributo,
            'required' => true,
            'separator' => '',
        ))

///////////////////////////////////////////////////////////////////
//////////////////////////     SELECT    //////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('select', 'tipo', array(
            'label' => 'Tipo de Veículo',
            'multiOptions' => $repoTipo->getIdValue(),
        ))
        ->addElement('select', 'tipo', array(
            'label' => 'Tipo de Veículo',
            'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
        ))
        ->addElement('select', 'tipo', array(
            'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
            'decorators' => array('ViewHelper'),
        ))
        ->addElement('select', 'tipo', array(
            'label' => 'Tipo de Veículo',
            'mostrarSelecione' => false,
            'multiOptions' => $repoTipo->getIdValue(),
        ))
        ->addElement('select', 'isPadrao', array(
            'mostrarSelecione' => false,
            'label' => 'Embalagem Recebimento',
            'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
            'value' => 'S',
        ))
        ->addElement('select', 'id', array(
            'mostrarSelecione' => false,
            'label' => '',
            'multiOptions' => array(),
            'value' => '',
            'class' => '',
            'size' => 999,
            'style' => 'max-width: 300px',
            'decorators' => array('ViewHelper'),
            'required' => true,
        ))

///////////////////////////////////////////////////////////////////
///////////////////////////     TEXT    ///////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('text', 'nome', array(
            'label' => 'Nome',
            'size' => 60,
            'maxlength' => 50,
            'class' => 'focus',
            'required' => true,
        ))
        ->addElement('text', 'id', array(
            'label' => 'Placa',
            'size' => 20,
            'class' => 'focus',
            'alt' => 'placaVeiculo',
            'required' => true,
        ))
        ->addElement('text', 'id', array(
            'size' => 10,
            'value' => '1',
            'class' => 'focus',
            'decorators' => array('ViewHelper'),
        ))
        ->addElement('text', 'nome', array(
            'label' => '',
            'value' => '',
            'alt' => '',
            'class' => '',
            'style' => '',
            'size' => 999,
            'maxlength' => 999,
            'decorators' => array('ViewHelper'),
            'required' => true,
        ))


///////////////////////////////////////////////////////////////////
////////////////////////     TEXTAREA    //////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('textarea', 'observacao', array(
            'label' => 'Observacao',
            'rows' => '15',
            'cols' => '100',
            'maxlength' => 130,
            'required' => true,
        ))

///////////////////////////////////////////////////////////////////
//////////////////////////     SUBMIT    //////////////////////////
///////////////////////////////////////////////////////////////////
        ->addElement('submit', 'submit', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ));