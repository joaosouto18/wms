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

        $quantidadeZero='';
        for ($count = 1; $count <= $maiorPrecisao; $count++) {
            $quantidadeZero .= '0';
        }
        $quantidade = 1 .$quantidadeZero;

        return $quantidade;
    }

    public static function compare($x,$y,$oper = ">") {
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
        $quantidade = self::maiorPrecisao($x, $y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x % $y) / $quantidade;
    }

    public static function adicionar($x, $y)
    {
        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x + $y) / $quantidade;

    }

    public static function subtrair($x, $y)
    {

        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x - $y) / $quantidade;

    }

    public static function multiplicar($x, $y)
    {
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
        $quantidade = self::maiorPrecisao($divisor, $dividendo);

        $x = $divisor * $quantidade;
        $y = $dividendo * $quantidade;

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
}