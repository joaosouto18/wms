<?php

namespace Wms;

Class Math
{
    private function maiorPrecisao($x, $y)
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

    public function restoDivisao($x, $y)
    {
        $quantidade = $this->maiorPrecisao($x, $y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return $x % $y;
    }
}