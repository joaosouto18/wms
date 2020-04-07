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

    function __construct($key)
    {
        $this->_key = $key;
        $this->_valid = true;
        $this->_day = 00;
        $this->_month = 00;
        $this->_year = 00;
        $this->_releaseDays = 0;

        if (strlen(trim($key)) <> 16) {
            $this->_valid = false;
            return;
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
                    return;
                }
            }
        }

        $this->decryptsKey();

        if (($this->_year == "00")
            OR ($this->_month == "00")
            OR ($this->_day == "00")){
            $this->_valid = false;
            return;
        }

        $this->_valid = checkdate($this->_month, $this->_day, $this->_year);
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
                    case "P": $strTemp = $strTemp . "1"; break;
                    case "E": $strTemp = $strTemp . "2"; break;
                    case "R": $strTemp = $strTemp . "3"; break;
                    case "N": $strTemp = $strTemp . "4"; break;
                    case "A": $strTemp = $strTemp . "5"; break;
                    case "M": $strTemp = $strTemp . "6"; break;
                    case "B": $strTemp = $strTemp . "7"; break;
                    case "U": $strTemp = $strTemp . "8"; break;
                    case "C": $strTemp = $strTemp . "9"; break;
                    case "O": $strTemp = $strTemp . "0"; break;
                }
            }
        }

        $this->_day = substr($strTemp,6,2);
        $this->_month = substr($strTemp, 4,2);
        $this->_year = substr($strTemp,0 ,4);
        $this->_releaseDays = substr($strTemp, 8,4);
    }

    public function expire() {
        if ($this->_releaseDays == 0) {
            return false;
        }
        return true;
    }

    public function isValid() {
        return $this->_valid;
    }

    public function isExpired() {
        if (!$this->_valid) return true;

        if ($this->daysRemaing() <0) return true;
        return false;
    }

    public function creationDay() {
        if (!$this->_valid) return false;

        $date = $this->_day . "/" . $this->_month . "/" . $this->_year;

        return $date;
    }

    public function expirationDate() {
        if (!$this->_valid) return false;

        $date = $this->_day . "-" . $this->_month . "-" . $this->_year;

        return date('d/m/Y', strtotime("+ " . $this->_releaseDays . " days", strtotime($date)));
    }

    public function daysRemaing() {
        if (!$this->_valid) return false;

        // HOJE - DIA_DE_EXPIRAÇÃO
        $expiracaoAno = substr($this->expirationDate(),6,4);
        $expiracaoMes = substr($this->expirationDate(),3,2);
        $expiracaoDia = substr($this->expirationDate(),0,2);

        $diasRestantes = intval($expiracaoAno . $expiracaoMes . $expiracaoDia) - intval(date("Ymd"));

        return $diasRestantes;
    }

    public function daysToClose() {
        if (!$this->_valid) return false;

        return $this->_releaseDays;
    }
}