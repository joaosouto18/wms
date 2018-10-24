<?php
namespace Core\Util;

class Date
{
    
    /**
     * Formata uma data
     * 
     * @param string $data Data no formato (dd/mm/YYYY)
     * @param string $sep Separador, padrao = '/'
     * @return int Data como numero (YYYYMMDD) 
     */
    public static function fromBRtoNumber($data, $sep = '/')
    {
        $ano = substr($data, 6, 4);
        $mes = substr($data, 3, 2);
        $dia = substr($data, 0, 2);
        $data = (int) $ano . $mes . $dia;

        return $data;
    }

}
