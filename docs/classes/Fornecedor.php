<?php

class Fornecedor
{

    /**
     * Retorna um fornecedor específico no WMS pelo seu ID
     *
     * @param string $idFornecedor ID do fornecedor
     * @return array
     */
    public function buscar($idFornecedor);

    /**
     * Adiciona um fornecedor no WMS
     * 
     * @param string $idFornecedor ID 
     * @param string $cnpj CNPJ
     * @param string $nome Nome ou Nome Fantasia
     * @param string $insc Inscrição Estadual
     * @return boolean Se o fornecedor foi inserido com sucesso ou não
     */
    public function inserir($idFornecedor, $cnpj, $nome, $insc);

    /**
     * Altera um fornecedor no WMS
     * 
     * @param string $idFornecedor ID 
     * @param string $cnpj CNPJ
     * @param string $nome Nome ou Nome Fantasia
     * @param string $insc Inscrição Estadual
     * @return boolean Se o fornecedor foi inserido com sucesso ou não
     */
    public function alterar($idFornecedor, $cnpj, $nome, $insc);

    /**
     * Salva um fornecedor no WMS. Se o fornecedor não existe, insere, senão, altera 
     * 
     * @param string $idFornecedor ID 
     * @param string $cnpj CNPJ
     * @param string $nome Nome ou Nome Fantasia
     * @param string $insc Inscrição Estadual
     * @return boolean se o fornecedor foi salvo ou não
     */
    public function salvar($idFornecedor, $cnpj, $nome, $insc);

    /**
     * Exclui um fornecedor do WMS
     * @param string $id
     * @return boolean
     */
    public function excluir($idFornecedor);

    /**
     * Lista todos os fornecedores cadastrados no sistema
     * 
     * @return array
     */
    public function listar();

}

?>