<?php

// Ws Fabricante 
$client = new SoapClient('http://wms.exemplo/soap/fabricante/wsdl');
$client->listar(); //retorna matriz dos fabricantes
$client->buscar(123); //busca fabricante específico
$client->salvar('ID', 'NOME'); //insere/altera fabricante
$client->excluir(123); //exclui fabricante


//Ws Fornecedor
// http://dev.wms/soap/index/wsdl?service=fornecedor
$client = new SoapClient('http://wms.exemplo/soap/fornecedor/wsdl');
$client->salvar('ID', 'CNPJ', 'NOME', 'INSC.ESTADUAL' ); //insere/altera fornecedor
$client->listar(); //retorna matriz de fornecedores
$client->buscar('ID'); //retorna ID
$client->excluir(123); //exclui fornecedor

//Ws Classe de produto
$client = new SoapClient('http://wms.exemplo/soap/classeProduto/wsdl');
$client->salvar('ID', 'NOME'); //insere/altera classe de produto
$client->salvar('ID', 'NOME', 'ID-PAI'); //insere/altera classe de produto com ID pai
$client->listar(); //retorna matriz com classes de produtos
$client->buscar('ID'); //retorna classe específica
$client->excluir('ID'); //exclui classe

//Ws produto
$client = new SoapClient('http://wms.exemplo/soap/produto/wsdl');
//obs: TIPO: 1 => Unitário, 2 => Composto, 3 => Kit
$client->salvar('ID', 'DESCRICAO', 'GRADE', 'ID-FABRICANTE', 'TIPO', 'ID-CLASSE', 'NUM-VOLUMES');
$client->listar(); //retorna matriz de produtos
$client->buscar('ID'); //retorna produto específico
$client->excluir('ID'); //exclui produto

// Ws de Transportador
$client = new SoapClient('http://wms.exemplo/soap/transportador/wsdl');
$client->listar(); // lista de transportadores
$client->inserir('ID', 'CNPJ', 'RAZAO_SOCIAL', 'NOME_FANTASIA', 'PLACA'); // insere um novo transportador

//WS de NotaFiscal
$client = new SoapClient('http://wms.exemplo/soap/notaFiscal/wsdl');
//somente insere nota. não é possível alterar uma nota. para isso, exclua e depois insira novamente
$client->inserir(array(
    'numeroNotaFiscal' => 'numero2',
    'placa' => 'ADR9998',
    'serie' => 'serie987876',
    'dataEmissao' => '12/12/2011',
    'idFornecedor' => '456',
    'itens' => array(
	array(
	    'idProduto' => 111,
	    'quantidade' => 200,
	    'grade' => 'XXX'
	),
	array(
	    'idProduto' => 333,
	    'quantidade' => 300,
	    'grade' => 'YYY'
	),
    )
));
$client->listar(); //retorna matriz de notas fiscais
$client->buscar('idFornecedor', 'numeroNF', 'serie'); //retorna nota fiscal específica
        
//Ws de Recebimento
$client = new SoapClient('http://wms.exemplo/soap/recebimento/wsdl');
//insere /altera recebimento.
//caso esteja inserindo novo recebimento, poderá enviar as notas simultaneamete.
//não será possível inserir/alterar notas quando alterar recebimento. para isso, use webservice de nota fiscal
$client->salvar(array(
    'idRecebimento' => 'xpto',
    'notasFiscais' => array( //só será usado quando for um novo recebimento
	array(
	    'numeroNotaFiscal' => 'xpto1',
	    'serie' => 'xpto-serie',
	    'dataEmissao' => '15/10/1999',
	    'idFornecedor' => '123',
	    'itens' => array(
		array(
		    'idProduto' => 111,
		    'quantidade' => 200,
		    'grade' => 'XXX'
		),
		array(
		    'idProduto' => 333,
		    'quantidade' => 300,
		    'grade' => 'YYY'
		),
	    )
	)
    )
));
$client->listar(); //retorna matriz de recebimentos
$client->buscar('ID'); //retorna recebimento específico


// Ws de Filial
$client = new SoapClient('http://wms.exemplo/soap/filial/wsdl');
$client->listar(); // lista de filiais
$client->inserir('ID', 'CNPJ', 'RAZAO_SOCIAL', 'NOME_FANTASIA'); // insere uma nova filial

// Ws de Tipo de Pedido de Expedicao
$client = new SoapClient('http://wms.exemplo/soap/tipoPedidoExpedicao/wsdl');
$client->listar();
$client->inserir('NOME', 'DESCRICAO');

?>