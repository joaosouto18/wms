<?php

class cliente {
    /** @var string */
    public $idCliente;
    /** @var string */
    public $nome;
    /** @var string */
    public $cnpj;
    /** @var string */
    public $insc;
}

class clientes {

    /** @var cliente[] */
    public $clientes = array();
}

class Wms_WebService_Cliente extends Wms_WebService
{

    /**
     * Retorna um cliente específico no WMS pelo seu ID
     *
     * @param string $idCliente ID do cliente
     * @return cliente
     */
    public function buscar($idCliente)
    {

        $for = new cliente();
        $for->idCliente = "9999";
        $for->nome =  "Teste";
        $for->cnpj =  "teste";
        $for->insc = "teste";
        return $for;
    }

    /**
     * Adiciona um cliente no WMS
     * 
     * @param string $idCliente ID
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean|Exception se o cliente foi inserido com sucesso ou não
     */
    private function inserir($idCliente, $cnpj, $insc, $nome)
    {
        return true;
    }

    /**
     * Altera um cliente no WMS
     * 
     * @param string $idCliente ID 
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean|Exception se o cliente foi inserido com sucesso ou não
     */
    private function alterar($idCliente, $cnpj, $insc, $nome)
    {
        return true;
    }

    /**
     * Salva um cliente no WMS. Se o cliente não existe, insere, senão, altera 
     * 
     * @param string $idCliente ID 
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean se o cliente foi salvo ou não
     */
    public function salvar($idCliente, $cnpj, $insc, $nome)
    {
        return true;
    }

    /**
     * Exclui um cliente do WMS
     * 
     * @param string $id
     * @return boolean|Exception
     */
    public function excluir($idCliente)
    {
        return true;
    }

    /**
     * Lista todos os clientees cadastrados no sistema
     * 
     * @return clientees
     */
    public function listar()
    {
        return array('clientees' => null);
    }

}

