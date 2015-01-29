<?php

class Wms_WebService_ProdutoClasse extends Wms_WebService
{

    /**
     * Retorna um Classe específico no WMS pelo seu ID
     *
     * @param string $idClasse ID do Classe
     * @return array
     */
    public function buscar($idClasse);

    /**
     * Adiciona um Classe no WMS
     * 
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean Se o Classe foi inserida com sucesso ou não
     */
    public function inserir($idClasse, $nome, $idClassePai = null);

    /**
     * Altera um Classe no WMS
     * 
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean Se o Classe foi inserida com sucesso ou não
     */
    public function alterar($idClasse, $nome, $idClassePai = null);

    /**
     * Salva um Classe no WMS. Se o Classe não existe, insere, senão, altera 
     * 
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean Classe foi salvo com sucesso
     */
    public function salvar($idClasse, $nome, $idClassePai = null);

    /**
     * Exclui um Classe do WMS
     * 
     * @param string $id
     * @return boolean
     */
    public function excluir($idClasse);

    /**
     * Lista todos os Classees cadastrados no sistema
     * 
     * @return array
     */
    public function listar();

}

?>