<?php

namespace Core\Util;

class String {

    /**
     * Aplica uma máscara à uma string
     * 
     * @param string $val valor a ser 'mascardo'
     * @param string $mask máscara a ser aplicada
     * @return string 
     * 
     *  <code>
       * <?php
       * \Core\String::mask(1234567, '###.###-#') // 123.456-7
       * ?>
     * </code>
     */
    public static function mask($val, $mask) {
        $maskared = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k]))
                    $maskared .= $val[$k++];
            } else {
                if (isset($mask[$i]))
                    $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }

    /**
     *
     * @param string $char
     * @return string 
     */
    public static function convertToXmlTag($char) {
        $badchars = array(' ', 'ã', 'á', 'à', 'ä', 'â', 'é', 'è', 'ë', 'ê', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ö', 'ô', 'õ', 'ú', 'ù', 'ü', 'û', '/', '\\', '|', '~', '^', 'ç', '=', '&', "'", '"', ':');
        $goodchars = array('', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', '_', '_', '_', '', '', 'c', '-', '+', '', '', '-');

        return str_ireplace($badchars, $goodchars, $char);
    }

    /**
     *
     * @param string $value
     * @return string
     */
    public static function toNumber($value) {
        return preg_replace("/\D+/", "", $value);
    }

    /**
     * Adiciona mascara de CPF ou CNPJ conforme a string enviada e.g. 12345678901 gerando 123.456.789.01 ou e.g. 12345678901234 gerando 12.345.678/9012-34
     * @param  string $string
     * @return string
     */
    public static function maskCpfCnpj($string) {
        $output = preg_replace("[' '-./ t]", '', $string);
        $size = (strlen($output) - 2);
        if ($size != 9 && $size != 12) {
            return false;
        }
        $mask = ($size == 9) ? '###.###.###-##' : '##.###.###/####-##';
        $index = -1;
        for ($i = 0; $i < strlen($mask); $i++):
            if ($mask[$i] == '#')
                $mask[$i] = $output[++$index];
        endfor;
        return $mask;
    }

    /**
     * Retira a mascara de CPF ou CNPJ conforme a string enviada e.g. 123.456.789.01 gerando 12345678901 ou e.g. 12.345.678/9012-34 gerando 12345678901234 
     * @param  string $string
     * @return string
     */
    public static function retirarMaskCpfCnpj($string) {

        $string = str_replace(array('.', '-', '/'), '', $string);

        return $string;
    }

}
