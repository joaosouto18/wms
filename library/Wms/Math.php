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

    public static function restoDivisao($x, $y)
    {
        $quantidade = self::maiorPrecisao($x, $y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return $x % $y;
    }

    /**
     * @param float|int $x Dividendo
     * @param float|int $y Divisor
     * @return float|int
     */
    public static function divisao($x, $y)
    {
        $quantidade = self::maiorPrecisao($x, $y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return $x / $y;
    }

    public static function totalAdicao($x,$y)
    {
        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x + $y) / $quantidade;

    }

    public static function totalSubtracao($x,$y) {

        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x - $y) / $quantidade;

    }

    public static function produtoMultiplicacao($x,$y)
    {
        $quantidade = self::maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x * $y) / ($quantidade * $quantidade);

    }
}