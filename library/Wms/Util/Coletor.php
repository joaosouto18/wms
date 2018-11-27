<?php
namespace Wms\Util;


class Coletor
{
    /**
     *
     * @param string $codigoBarras
     * @param $exception bool
     * @throws \Exception
     * @return string
     */
    public static function adequaCodigoBarras($codigoBarras, $exception = false)
    {

        $codigoBarras = trim($codigoBarras);
        
        if(substr($codigoBarras, 0, 4) == '(01)') {
            return substr($codigoBarras, 4, 18);
        }

        $codigoBarras = str_replace('(','',$codigoBarras);
        $codigoBarras = str_replace(')','',$codigoBarras);
        if (!$codigoBarras || empty($codigoBarras)) {
            if ($exception == true) {
                throw new \Exception('Código de barras inválido ou não existente');
            } else {
                return 0;
            }
        }

        // Se o código começa com "]C1010", o sistema considera o código da posição 6 até 19.
        if(substr($codigoBarras, 0, 6) == ']C1010') {
            return substr($codigoBarras, 6, 14);
        }

        // Se o código começa com "]C" o sistema considera o código da posição 8 até 25.
        if(substr($codigoBarras, 0, 2) == ']C') {
            return substr($codigoBarras, 7, 15);
        }

        // Se o código começa com "8006", o sistema considera o código da posição 5 até 22.
        if(substr($codigoBarras, 0, 4) == '8006') {
            return substr($codigoBarras, 4, 18);
        }
        if(substr($codigoBarras, 0, 5) == '98006') {
            return substr($codigoBarras, 6, 18);
        }
        // Se o código começa com "0901", o sistema considera o código da posição 5 até 22.
        if(substr($codigoBarras, 0, 4) == '0901') {
            return substr($codigoBarras, 4, 18);
        }
        if(substr($codigoBarras, 0, 5) == '08006') {
            return substr($codigoBarras, 5, 18);
        }
        // Se o código começa com "9010", o sistema considera o código da posição 5 até 22.
        if(substr($codigoBarras, 0, 4) == '9010') {
            return substr($codigoBarras, 4, 18);
        }


        // Se o código começa com "8006", o sistema considera o código da posição 5 até 22.
        if(substr($codigoBarras, 0, 2) == '01') {
            return substr($codigoBarras, 3, 13);
        }

        if (substr($codigoBarras, 0, 3) == '856') {
            return substr($codigoBarras, 0, 14);
        }


        // retorna o codigo todo caso nenhuma situacao anterior adequar
        return $codigoBarras;
    }

    public static function retiraDigitoIdentificador($codigoBarras)
    {
        $codigoBarras = ltrim($codigoBarras, '0');
        return substr($codigoBarras, 0, strlen($codigoBarras)-1);
    }
} 