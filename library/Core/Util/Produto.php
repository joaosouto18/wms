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

    public function getSystemParameterValue($param) {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => $param));

        if ($parametro == NULL) {
            return "";
        } else {
            return $parametro->getValor();
        }
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
            if (self::getSystemParameterValue('ZERO_ESQ_COD_PRODUTO') == "N") {
                return $valor;
            } else {
                return self::preencheZerosEsquerda($valor, self::$qtdDigitosCodProduto);
            }
        } else {
            return trim($valor);
        }
    }

}
