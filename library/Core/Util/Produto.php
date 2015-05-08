<?php

namespace Core\Util;

/**
 * Por regras do cliente ERP todo codigo de produto tem 6 digitos e podem 
 * iniciar com zero. Caso seja 7 ou maior ele jamais iniciará com zero
 */
class Produto
{
    public static $qtdDigitosCodProduto = 6;
    
    /**
     *
     * @param string $numero
     * @param int $digitos
     * @return string 
     */
    public static function preencheZerosEsquerda($numero, $digitos)
    {
        $zeros = $digitos - strlen($numero);
        
        if (0 >= $zeros)
            return $numero;
        
        $x = str_repeat("0", $zeros);
        $numero = $x . $numero;
        
        return $numero;
    }
    
    /**
     * Limpa deixando apenas numericos e preenche com zeros a esquerda,
     * conforme formato de produtos do cliente
     * 
     * @param string $valor
     * @return string 
     */
    public static function formatar($valor)
    {
        $valor = trim($valor);
        if (strlen(preg_replace('/\D+/', '', $valor)) == strlen($valor)) {
            return self::preencheZerosEsquerda($valor, self::$qtdDigitosCodProduto);
        } else {
            return trim($valor);
        }
    }

}
