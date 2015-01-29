<?php

class Produto 
{

    /**
     * Retorna um Produto específico no WMS pelo seu ID
     *
     * @param string $idProduto ID do Produto
     * @return array
     */
    public function buscar($idProduto);
    
    /**
     * Insere um produto no WMS
     * 
     * @param string $idProduto ID do produto
     * @param string $descricao Descrição
     * @param string $grade Grade
     * @param string $idFabricante ID do fabricante
     * @param string $tipo 1 => Unitário, 2 => Composto, 3 => Kit
     * @param string $idClasse ID da classe do produto
     * @return boolean Se o produto foi inserido com sucesso ou não
     */
    public function inserir($idProduto, $descricao, $grade, $idFabricante, $tipo, $idClasse);

    /**
     * Alterar um produto no WMS
     * 
     * @param string $idProduto ID do produto
     * @param string $descricao Descrição
     * @param string $grade Grade
     * @param string $idFabricante ID do fabricante
     * @param string $tipo 1 => Unitário, 2 => Composto, 3 => Kit
     * @param string $idClasse ID da classe do produto
     * @return boolean Se o produto foi inserido com sucesso ou não
     */
    public function alterar($idProduto, $descricao, $grade, $idFabricante, $tipo, $idClasse);

    /**
     * Salva um Produto no WMS. Se o Produto não existe, insere, senão, altera 
     * 
     * @param string $idProduto ID do produto
     * @param string $descricao Descrição
     * @param string $grade Grade
     * @param string $idFabricante ID do fabricante
     * @param string $tipo 1 => Unitário, 2 => Composto, 3 => Kit
     * @param string $idClasse ID da classe do produto
     * @return boolean Se o produto foi inserido com sucesso ou não
     */
    public function salvar($idProduto, $descricao, $grade, $idFabricante, $tipo, $idClasse);

    /**
     * Exclui um Produto do WMS
     * 
     * @param string $id
     * @return boolean
     */
    public function excluir($idProduto);

    /**
     * Lista todos os Produtos cadastrados no sistema
     * 
     * @return array
     */
    public function listar();

}

?>