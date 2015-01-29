<?php
class Fabricante
{

    /**
     * Retorna uma matriz contendo os dados de um Fabricante específico no WMS
     *
     * @param string $idFabricante ID do Fabricante a ser consultado
     * @return array
     */
    public function buscar($idFabricante);

    /**
     * Adiciona um Fabricante no WMS
     * 
     * @param string $idFabricante ID 
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean Se o Fabricante foi inserido com sucesso ou não
     */
    public function inserir($idFabricante, $nome);

    /**
     * Altera um Fabricante no WMS
     * 
     * @param string $idFabricante ID 
     * @param string $nome Nome do fabricante
     * @return boolean Se o Fabricante foi inserido com sucesso ou não
     */
    public function alterar($idFabricante, $nome);

    /**
     * Salva um Fabricante no WMS. Se o Fabricante não existe, insere, senão, altera 
     * 
     * @param string $idFabricante ID 
     * @param string $nome Nome do fabricante
     * @return boolean se o Fabricante foi salvo com sucesso ou não
     */
    public function salvar($idFabricante, $nome);

    /**
     * Exclui um Fabricante do WMS
     * 
     * @param string $id ID do fabricante a ser excluído
     * @return boolean
     */
    public function excluir($idFabricante);

    /**
     * Retorna uma matriz com todos os fabricantes cadastrados no WMS
     * 
     * @return array
     */
    public function listar();
}

?>