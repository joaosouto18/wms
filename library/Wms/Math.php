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

    public function quocienteDivisao($x, $y)
    {
        $quantidade = $this->maiorPrecisao($x, $y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return $x / $y;

    }

    public function restoDivisao($x, $y)
    {
        $quantidade = $this->maiorPrecisao($x, $y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return $x % $y;
    }

    public function compare($x,$y,$oper = ">") {
        $quantidade = $this->maiorPrecisao($x,$y);
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

    public function totalAdicao($x,$y)
    {
        $quantidade = $this->maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x + $y) / $quantidade;

    }

    public function totalSubtracao($x,$y) {

        $quantidade = $this->maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x - $y) / $quantidade;

    }

    public function produtoMultiplicacao($x,$y)
    {
        $quantidade = $this->maiorPrecisao($x,$y);

        $x = $x * $quantidade;
        $y = $y * $quantidade;

        return ($x * $y) / ($quantidade * $quantidade);

    }
}