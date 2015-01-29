<?php

namespace Core\Util;

class Converter
{

    /**
     * Converte para pontução padrão BR, valor esperado e.g. 10456.98 gerando e.g. 10.456,98  
     * @param  decimal $value
     * @param  integer $decimal
     * @return double
     */
    public static function enToBr($value, $decimal)
    {
        return number_format((double) str_replace(',', '.', $value), $decimal, ',', '.');
    }

    /**
     * Converte para pontução padrão EN, valor esperado e.g. 10.456,98 gerando e.g. 10456.98  
     * @param  decimal $value
     * @param  integer $decimal
     * @return double
     */
    public static function brToEn($value, $decimal)
    {
        return number_format((double) str_replace(',', '.', str_replace('.', '', $value)), $decimal, '.', '');
    }

}
