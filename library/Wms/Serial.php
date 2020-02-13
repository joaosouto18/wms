<?php

namespace Wms;

/*
 * Tratativas registradas:
 *
 * 01/08/19 Tarcísio César: Caso Math::resto(32.55, 1.05) [Valor esperado: 0; Valor obtido: 1.04]
 *      passar multiplicações ($x * $quantidade) e ($y * $quantidade) pela função strval() nas funções de operações ficando
 *      $x = strval($x * $quantidade);
 *      $y = strval($y * $quantidade);
 */

Class Serial
{
    private $_key;
    private $_valid;
    private $_day;
    private $_month;
    private $_year;
    private $_releaseDays;
    private $_representantes;
    private $_creationDate;

    function __construct($key)
    {
        $this->_key = $key;
        $this->_valid = false;

        if (strlen(trim($key)) <> 16) {
            $this->_valid = false;
        }

        if ($this->_valid) {
            for ($i = 0; $i < strlen($key); $i++) {
                if (($key[$i] <> "P") AND
                    ($key[$i] <> "E") AND
                    ($key[$i] <> "R") AND
                    ($key[$i] <> "N") AND
                    ($key[$i] <> "A") AND
                    ($key[$i] <> "M") AND
                    ($key[$i] <> "B") AND
                    ($key[$i] <> "U") AND
                    ($key[$i] <> "C") AND
                    ($key[$i] <> "O")) {
                    $this->_valid = false;
                }
            }
        }

        $this->decryptsKey();

    }

    function decryptsKey() {
        /*
           PERNAMBUCO
           1234567890
           yyyyMMddXXXXYYYY
           yyyy - Ano da Geração
           MM - Mes da Geração
           dd - Dia da Geração
           XXXX - Dias que poderá usar, 0000 é igual a não expira
           YYYY - Campos sem utilização
           2011020100000000
           EOPPOEOPOOOOOOOO
        */

        $strTemp = "";
        if ($this->_valid) {
            for ($i = 0; $i < strlen($this->_key); $i++) {
                switch (strtoupper($this->_key[$i])) {
                    case "P": $strTemp = $strTemp . "1";
                    case "E": $strTemp = $strTemp . "2";
                    case "R": $strTemp = $strTemp . "3";
                    case "N": $strTemp = $strTemp . "4";
                    case "A": $strTemp = $strTemp . "5";
                    case "M": $strTemp = $strTemp . "6";
                    case "B": $strTemp = $strTemp . "7";
                    case "U": $strTemp = $strTemp . "8";
                    case "C": $strTemp = $strTemp . "9";
                    case "O": $strTemp = $strTemp . "0";
                }
            }
        }

        $this->_day = substr($strTemp,6,2);
        $this->_month = substr($strTemp, 4,2);
        $this->_year = substr($strTemp,0 ,4);
        $this->_releaseDays = substr($strTemp, 8,4);
    }

}