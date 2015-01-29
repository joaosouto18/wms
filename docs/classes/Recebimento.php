<?php

class Recebimento 
{
    /**
     * Retorna um Recebimento específico no WMS pelo seu ID
     *
     * @param string $idRecebimento ID do Recebimento
     * @return array
     */
    public function buscar($idRecebimento){}

    /**
     * Salva um Recebimento no WMS. Se o Recebimento não existe, insere, senão, altera 
     * Caso seja um novo recebimento, poderá enviar os dados das notas fiscais simultaneamente.
     * Não será possível editar notas fiscais por este método.
     * 
     * @param array $dados ID do produto
     * @return boolean| Se o recebimento foi salvo com sucesso ou não
     */
    public function salvar(array $dados){}

    /**
     * Exclui um Recebimento do WMS
     * 
     * @param string $id
     * @return boolean
     */
    public function excluir($idRecebimento){}

    /**
     * Lista todos os Recebimentos cadastrados no sistema
     * 
     * @return array
     */
    public function listar(){}
}
