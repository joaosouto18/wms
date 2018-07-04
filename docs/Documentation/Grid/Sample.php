<?php

$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
$grid->setId('grid-veiculo');
$grid->setAttrib('class', 'grid-recebimento');
$grid->setAttrib('caption', 'Histórico');

///////////////////////////////////////////////////////////////////
//////////////////////////     COLUMM    //////////////////////////
///////////////////////////////////////////////////////////////////
$grid->addColumn(array(
            'label' => 'Nome',
            'index' => 'nome',
        ))
        ->addColumn(array(
            'label' => 'Qtd. Produtos',
            'index' => 'qtdProduto',
            'align' => 'R',
            'hasTotal' => true,
        ))
        ->addColumn(array(
            'label' => 'Data Emissao',
            'index' => 'dataEmissao',
            'render' => 'Data',
        ))
        ->addColumn(array(
            'label' => 'Largura (m)',
            'index' => 'largura',
            'render' => 'decimal',
        ))
        ->addColumn(array(
            'label' => 'CNPJ',
            'index' => 'cnpj',
            'render' => 'documento',
        ))
        ->addColumn(array(
            'label' => 'Status',
            'index' => 'status',
            'render' => array(
                'type' => 'ArrayMap',
                'options' => array(
                    'array' => NotaFiscal::$listaStatus
                )
            ),
        ))
        ->addColumn(array(
            'label' => 'Data Vigência',
            'index' => 'datVigencia',
            'render' => 'Data',
            'filter' => array(
                'render' => array(
                    'type' => 'date',
                    'range' => true,
                ),
            ),
        ))
        ->addColumn(array(
            'label' => 'Largura (m)',
            'index' => 'largura',
            'render' => 'decimal',
            'filter' => array(
                'render' => array(
                    'type' => 'float',
                    'range' => true,
                ),
            ),
        ))
        ->addColumn(array(
            'label' => 'Título da Ajuda',
            'index' => 'dscAjuda',
            'filter' => array(
                'render' => array(
                    'type' => 'text',
                    'condition' => array('match' => array('fulltext'))
                ),
            ),
        ))
        ->addColumn(array(
            'label' => 'Código do Depósito',
            'index' => 'id',
            'filter' => array(
                'render' => array(
                    'type' => 'number',
                    'range' => true,
                ),
            ),
        ))
        ->addColumn(array(
            'label' => 'Ativo',
            'index' => 'isAtivo',
            'render' => 'SimOrNao',
            'filter' => array(
                'render' => array(
                    'type' => 'select',
                    'attributes' => array(
                        'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO')
                    )
                ),
            ),
        ))
        ->addColumn(array(
            'label' => 'Nome',
            'hasOrdering' => false,
            'index' => 'nome',
            'width' => '200',
            'render' => array(
                'type' => 'ArrayMap',
                'options' => array(
                    'array' => NotaFiscal::$listaStatus
                )
            ),
            'filter' => array(
                'render' => array(
                    'type' => 'text',
                    'condition' => array('match' => array('fulltext')),
                    'range' => true,
                ),
            ),
            'dirOrder' => 'DESC',
            'align' => 'R',
            'hasTotal' => true,
            'isReportColumn' => false,
        ))

///////////////////////////////////////////////////////////////////
//////////////////////////     ACTION    //////////////////////////
///////////////////////////////////////////////////////////////////
        ->addAction(array(
            'label' => 'Editar',
            'actionName' => 'edit',
            'pkIndex' => 'id',
        ))
        ->addAction(array(
            'label' => 'Excluir',
            'actionName' => 'delete',
            'pkIndex' => 'id',
            'cssClass' => 'del',
        ))
        ->addAction(array(
            'label' => 'Visualizar Veículo',
            'actionName' => 'view-veiculo-ajax',
            'pkIndex' => 'id',
            'cssClass' => 'view-veiculo',
        ))
        ->addAction(array(
            'label' => 'Visualizar ajuda',
            'pkIndex' => 'codIdentificacaoAjuda',
            'controllerName' => 'ajuda',
            'actionName' => 'view',
            'cssClass' => 'view'
        ))
        ->addAction(array(
            'label' => 'Visualizar Ordem de Serviço',
            'title' => 'Ordens de Serviço do Recebimento',
            'actionName' => 'view-ordem-servico-ajax',
            'cssClass' => 'view-ordem-servico dialogAjax',
            'pkIndex' => 'id',
            'condition' => function ($row) {
                return $row['idStatus'] != Recebimento::STATUS_CRIADO;
            }
        ))
        ->addAction(array(
            'label' => '',
            'actionName' => '',
            'controllerName' => '',
            'params' => array(),
            'userDefinedUrl' => '',
            'condition' => '',
            'pkIndex' => '',
            'cssClass' => '',
            'title' => '',
        ))

////////////////////////////////////////////////////////////////////
//////////////////////////     ATTRIBS    //////////////////////////
////////////////////////////////////////////////////////////////////
        ->addAttribs(array('target' => '_blank'))

////////////////////////////////////////////////////////////////////
////////////////////////     SHOW EXPORT   /////////////////////////
////////////////////////////////////////////////////////////////////
        ->setShowExport(false)
        ->setHasOrdering(true);
$this->view->grid = $grid->build();

