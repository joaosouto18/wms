<?php

namespace Wms;

Class Math
{
    private static function maiorPrecisao($x, $y)
    {
        $precisaoA = 0;
        $precisaoB = 0;

        $a = explode('.',$x);
        $b = explode('.',$y);
        if (count($a) > 1) {
            $precisaoA = strlen($a[1]);
        }
        if (count($b) > 1) {
            $precisaoB = strlen($b[1]);
        }
        if ($precisaoA >= $precisaoB) {
            $maiorPrecisao = $precisaoA;
        } else {
            $maiorPrecisao = $precisaoB;
        }

        return str_pad(1 ,$maiorPrecisao + 1,'0');
    }


    public static function compare($x,$y,$oper = ">") {

        $x = strval($x);
        $y = strval($y);

        $quantidade = self::maiorPrecisao($x,$y);
        
        $x = $x * $quantidade;
        $y = $y * $quantidade;

        if ($oper == ">") {
            return $x > $y;
        } elseif ($oper == ">=") {
            return $x >= $y;
        } elseif ($oper == "<=") {
            return $x <= $y;
        } elseif ($oper == "<") {
            return $x < $y;
        }
    }

    public static function resto($x, $y)
    {
        if ($x == 0) return 0;

        $x = strval($x);
        $y = strval($y);

        $quantidade = self::maiorPrecisao($x, $y);
        if ($quantidade == 0) return 0;

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        if ($x == 0 || $y == 0)
            return 0;

        return ($x % $y) / $quantidade;
    }

    public static function adicionar($x, $y)
    {
        $x = strval($x);
        $y = strval($y);
        
        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x + $y) / $quantidade;
    }

    public static function subtrair($x, $y)
    {
        $x = strval($x);
        $y = strval($y);
        
        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x - $y) / $quantidade;
    }

    public static function multiplicar($x, $y)
    {
        $x = strval($x);
        $y = strval($y);
        
        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x * $y) / ($quantidade * $quantidade);
    }

    /**
     * @param float|int $divisor
     * @param float|int $dividendo
     * @return float|int
     */
    public static function dividir($divisor, $dividendo)
    {
        $divisor = strval($divisor);
        $dividendo = strval($dividendo);

        $quantidade = self::maiorPrecisao($divisor, $dividendo);

        $x = $divisor * $quantidade;
        $y = $dividendo * $quantidade;

        if ($x == 0 || $y == 0)
            return 0;

        return $x / $y;
    }

    /**
     * @param $x
     * @return float|int
     */
    public static function decrementar($x)
    {
        return self::subtrair($x,$x);
    }

    /**
     * @param $x
     * @return float|int
     */
    public static function incrementar($x)
    {
        return self::adicionar($x,$x);
    }

    /**
     * @param $qtdBase
     * @param $qtdFator
     * @return array($qtdMultipla, $resto)
     */
    public static function getFatorMultiploResto ($qtdBase, $qtdFator) {

        $return = [];
        $return[1] = self::resto($qtdBase, $qtdFator);
        // Com isso identifico quanto de cada embalagem será possível e necessária para separar o item
        $return[0] = self::dividir(self::subtrair($qtdBase, $return[1]), $qtdFator);

        return $return;
    }
}