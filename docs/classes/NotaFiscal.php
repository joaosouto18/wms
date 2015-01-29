<?php
class NotaFiscal
{

    /**
     * Retorna uma matriz contendo os dados da nota fiscal
     * 
     * @param string $numeroNotaFiscal
     * @param string $serie
     * @param string $idFornecedor
     * @return array
     */
    
    public function buscar($numeroNotaFiscal, $serie, $idFornecedor);

    /**
     * Insere um nota fiscal no WMS
     * 
     * @param array $dados da nota fiscal
     * @return boolean Se o nota foi salva com sucesso
     */
    public function salvar(array $dados);

    /**
     * Exclui um NotaFiscal do WMS
     * 
     * @param string $numeroNotaFiscal
     * @param string $serie
     * @param string $idFornecedor
     * @return boolean 
     */
    public function excluir($numeroNotaFiscal, $serie, $idFornecedor);

    /**
     * Lista todos os NotaFiscals cadastrados no sistema
     * 
     * @return array
     */
    public function listar();
}

?>