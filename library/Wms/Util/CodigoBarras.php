<?php

namespace Wms\Util;

/**
 * Classe responsável por gerar o código e a imagem do código de barras
 * 
 * @author Adriano Uliana
 */
class CodigoBarras
{

    public function verificarDiretorioExistente($dir){
        if ( !is_dir($dir) ){
            mkdir($dir,777);
        }
    }

    /**
     * Gera imagem do codigo de barras no padrao UPCA
     * @param string $code
     * @return string 
     */
    public static function gerarImagemUPCA($code)
    {
        $code = self::preencheZerosEsquerda($code, 11);
        $nomeArquivo = $code . ".png";

        $Lencode = array('0001101', '0011001', '0010011', '0111101', '0100011',
            '0110001', '0101111', '0111011', '0110111', '0001011');
        $Rencode = array('1110010', '1100110', '1101100', '1000010', '1011100',
            '1001110', '1010000', '1000100', '1001000', '1110100');

        $ends = '101';
        $center = '01010';

        $ncode = '0' . $code;
        $even = 0;
        $odd = 0;
        $x = 0;
        for ($x = 0; $x < 12; $x++) {
            if ($x % 2) {
                $odd += $ncode[$x];
            } else {
                $even += $ncode[$x];
            }
        }
        $code .= (10 - (($odd * 3 + $even) % 10)) % 10;

        /* Create the bar encoding using a binary string */
        $bars = $ends;
        $bars .= $Lencode[$code[0]];
        for ($x = 1; $x < 6; $x++) {
            $bars.=$Lencode[$code[$x]];
        }
        $bars .= $center;
        for ($x = 6; $x < 12; $x++) {
            $bars.=$Rencode[$code[$x]];
        }
        $bars .= $ends;

        $width = 3;
        $height = 40;

        /* Gera a base para o preenchimento do código.
          $img       -> imagem do código
          $white     -> cor branca
          $black     -> cor preta
         */
        $img = ImageCreate($width * 95 + 30, $height + 30);
        $white = ImageColorAllocate($img, 0, 0, 0);
        $black = ImageColorAllocate($img, 255, 255, 255);

        /* Cria o retángulo principal onde a imagem será aplicada. */
        ImageFilledRectangle($img, 0, 0, $width * 95 + 30, $height + 30, $black);

        /* Abertura do código de barras. */
        for ($x = 0; $x < strlen($bars); $x++) {
            if (($x < 10) || ($x >= 45 && $x < 50) || ($x >= 85)) {
                $sh = 10;
            } else {
                $sh = 0;
            }
            if ($bars[$x] == '1') {
                $color = $white;
            } else {
                $color = $black;
            }
            ImageFilledRectangle($img, ($x * $width) + 15, 5, ($x + 1) * $width + 14, $height + 5 + $sh, $color);
        }

        /* Adiciona label visivel no código de barras */
        ImageString($img, 4, 5, $height - 5, $code[0], $white);
        for ($x = 0; $x < 5; $x++) {
            ImageString($img, 5, $width * (13 + $x * 6) + 15, $height + 5, $code[$x + 1], $white);
            ImageString($img, 5, $width * (53 + $x * 6) + 15, $height + 5, $code[$x + 6], $white);
        }
        ImageString($img, 4, $width * 95 + 17, $height - 5, $code[11], $white);

        self::verificarDiretorioExistente(APPLICATION_PATH . '/../data/CodigoBarras/');

        ImagePNG($img, APPLICATION_PATH . '/../data/CodigoBarras/' . $nomeArquivo);

        return APPLICATION_PATH . '/../data/CodigoBarras/' . $nomeArquivo;
    }

    /**
     *
     * @param string $numero
     * @param int $digitos
     * @return string 
     */
    public static function preencheZerosEsquerda($numero, $digitos)
    {
        $zeros = $digitos - strlen($numero);

        if (0 >= $zeros)
            return $numero;

        $x = str_repeat("0", $zeros);
        $numero = $x . $numero;

        return $numero;
    }

    /**
     * Gera o codigo no padrao EAN 13
     * @param string $code
     * @return string 
     */
    public static function gerarCodigoEAN13($code)
    {
        $code = self::preencheZerosEsquerda($code, 12);
        $sum = 0;

        for ($i = 0, $t = strlen($code); $i < $t; ++$i) {
            $sum += $code{ $i } * ( $i & 1 ? 3 : 1 );
        }

        return $code . ( ( 1 - ( ( $sum / 10 ) - (int) ( $sum / 10 ) ) ) * 10 );
    }

    /**
     * Gera imagem do codigo de barras no padrao EAN 13
     * @param string $code
     * @param integer $width
     * @param integer $height
     * @param integer $font
     * @return string 
     */
    public static function gerarImagemEAN13($code, $width = 3, $height = 40, $font = 4)
    {
        $code = self::gerarCodigoEAN13($code);
        $nomeArquivo = $code . ".png";

        $numberSet = array(
            '0' => array(
                'A' => array(0, 0, 0, 1, 1, 0, 1),
                'B' => array(0, 1, 0, 0, 1, 1, 1),
                'C' => array(1, 1, 1, 0, 0, 1, 0)
            ),
            '1' => array(
                'A' => array(0, 0, 1, 1, 0, 0, 1),
                'B' => array(0, 1, 1, 0, 0, 1, 1),
                'C' => array(1, 1, 0, 0, 1, 1, 0)
            ),
            '2' => array(
                'A' => array(0, 0, 1, 0, 0, 1, 1),
                'B' => array(0, 0, 1, 1, 0, 1, 1),
                'C' => array(1, 1, 0, 1, 1, 0, 0)
            ),
            '3' => array(
                'A' => array(0, 1, 1, 1, 1, 0, 1),
                'B' => array(0, 1, 0, 0, 0, 0, 1),
                'C' => array(1, 0, 0, 0, 0, 1, 0)
            ),
            '4' => array(
                'A' => array(0, 1, 0, 0, 0, 1, 1),
                'B' => array(0, 0, 1, 1, 1, 0, 1),
                'C' => array(1, 0, 1, 1, 1, 0, 0)
            ),
            '5' => array(
                'A' => array(0, 1, 1, 0, 0, 0, 1),
                'B' => array(0, 1, 1, 1, 0, 0, 1),
                'C' => array(1, 0, 0, 1, 1, 1, 0)
            ),
            '6' => array(
                'A' => array(0, 1, 0, 1, 1, 1, 1),
                'B' => array(0, 0, 0, 0, 1, 0, 1),
                'C' => array(1, 0, 1, 0, 0, 0, 0)
            ),
            '7' => array(
                'A' => array(0, 1, 1, 1, 0, 1, 1),
                'B' => array(0, 0, 1, 0, 0, 0, 1),
                'C' => array(1, 0, 0, 0, 1, 0, 0)
            ),
            '8' => array(
                'A' => array(0, 1, 1, 0, 1, 1, 1),
                'B' => array(0, 0, 0, 1, 0, 0, 1),
                'C' => array(1, 0, 0, 1, 0, 0, 0)
            ),
            '9' => array(
                'A' => array(0, 0, 0, 1, 0, 1, 1),
                'B' => array(0, 0, 1, 0, 1, 1, 1),
                'C' => array(1, 1, 1, 0, 1, 0, 0)
            )
        );
        $numberSetLeftCoding = array(
            '0' => array('A', 'A', 'A', 'A', 'A', 'A'),
            '1' => array('A', 'A', 'B', 'A', 'B', 'B'),
            '2' => array('A', 'A', 'B', 'B', 'A', 'B'),
            '3' => array('A', 'A', 'B', 'B', 'B', 'A'),
            '4' => array('A', 'B', 'A', 'A', 'B', 'B'),
            '5' => array('A', 'B', 'B', 'A', 'A', 'B'),
            '6' => array('A', 'B', 'B', 'B', 'A', 'A'),
            '7' => array('A', 'B', 'A', 'B', 'A', 'B'),
            '8' => array('A', 'B', 'A', 'B', 'B', 'A'),
            '9' => array('A', 'B', 'B', 'A', 'B', 'A')
        );



        // Calculate the barcode width
        $barcodewidth = (strlen($code)) * (7 * $width)
                + 3 * $width  // left
                + 5 * $width  // center
                + 3 * $width  // right
                + imagefontwidth($font) + 1
        ;

        $barcodelongheight = (int) (imagefontheight($font) / 2) + $height;

        // Create the image
        $img = ImageCreate(
                $barcodewidth, $barcodelongheight + imagefontheight($font) + 1
        );

        // Alocate the black and white colors
        $black = ImageColorAllocate($img, 0, 0, 0);
        $white = ImageColorAllocate($img, 255, 255, 255);

        // Fill image with white color
        imagefill($img, 0, 0, $white);

        // get the first digit which is the key for creating the first 6 bars
        $key = substr($code, 0, 1);

        // Initiate x position
        $xpos = 0;

        // print first digit
        imagestring($img, $font, $xpos, $height, $key, $black);
        $xpos = imagefontwidth($font) + 1;

        // Draws the left guard pattern (bar-space-bar)
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $barcodelongheight, $black);
        $xpos += $width;
        // space
        $xpos += $width;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $barcodelongheight, $black);
        $xpos += $width;

        // Draw left $code contents
        $set_array = $numberSetLeftCoding[$key];
        for ($idx = 1; $idx < 7; $idx++) {
            $value = substr($code, $idx, 1);
            imagestring($img, $font, $xpos + 1, $height, $value, $black);
            foreach ($numberSet[$value][$set_array[$idx - 1]] as $bar) {
                if ($bar) {
                    imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $height, $black);
                }
                $xpos += $width;
            }
        }

        // Draws the center pattern (space-bar-space-bar-space)
        // space
        $xpos += $width;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $barcodelongheight, $black);
        $xpos += $width;
        // space
        $xpos += $width;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $barcodelongheight, $black);
        $xpos += $width;
        // space
        $xpos += $width;


        // Draw right $code contents
        for ($idx = 7; $idx < 13; $idx++) {
            $value = substr($code, $idx, 1);
            imagestring($img, $font, $xpos + 1, $height, $value, $black);
            foreach ($numberSet[$value]['C'] as $bar) {
                if ($bar) {
                    imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $height, $black);
                }
                $xpos += $width;
            }
        }

        // Draws the right guard pattern (bar-space-bar)
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $barcodelongheight, $black);
        $xpos += $width;
        // space
        $xpos += $width;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $width - 1, $barcodelongheight, $black);
        $xpos += $width;

        self::verificarDiretorioExistente(APPLICATION_PATH . '/../data/CodigoBarras/');

        ImagePNG($img, APPLICATION_PATH . '/../data/CodigoBarras/' . $nomeArquivo);

        return APPLICATION_PATH . '/../data/CodigoBarras/' . $nomeArquivo;
    }

    /**
     * Gera o codigo no padrao EAN 128
     * @param string $code
     * @return string 
     */
    public static function gerarCodigoEAN128($code)
    {
        $code128c_codes = array(
            "00" => "11011001100",
            "01" => "11001101100",
            "02" => "11001100110",
            "03" => "10010011000",
            "04" => "10010001100",
            "05" => "10001001100",
            "06" => "10011001000",
            "07" => "10011000100",
            "08" => "10001100100",
            "09" => "11001001000",
            "10" => "11001000100",
            "11" => "11000100100",
            "12" => "10110011100",
            "13" => "10011011100",
            "14" => "10011001110",
            "15" => "10111001100",
            "16" => "10011101100",
            "17" => "10011100110",
            "18" => "11001110010",
            "19" => "11001011100",
            "20" => "11001001110",
            "21" => "11011100100",
            "22" => "11001110100",
            "23" => "11101101110",
            "24" => "11101001100",
            "25" => "11100101100",
            "26" => "11100100110",
            "27" => "11101100100",
            "28" => "11100110100",
            "29" => "11100110010",
            "30" => "11011011000",
            "31" => "11011000110",
            "32" => "11000110110",
            "33" => "10100011000",
            "34" => "10001011000",
            "35" => "10001000110",
            "36" => "10110001000",
            "37" => "10001101000",
            "38" => "10001100010",
            "39" => "11010001000",
            "40" => "11000101000",
            "41" => "11000100010",
            "42" => "10110111000",
            "43" => "10110001110",
            "44" => "10001101110",
            "45" => "10111011000",
            "46" => "10111000110",
            "47" => "10001110110",
            "48" => "11101110110",
            "49" => "11010001110",
            "50" => "11000101110",
            "51" => "11011101000",
            "52" => "11011100010",
            "53" => "11011101110",
            "54" => "11101011000",
            "55" => "11101000110",
            "56" => "11100010110",
            "57" => "11101101000",
            "58" => "11101100010",
            "59" => "11100011010",
            "60" => "11101111010",
            "61" => "11001000010",
            "62" => "11110001010",
            "63" => "10100110000",
            "64" => "10100001100",
            "65" => "10010110000",
            "66" => "10010000110",
            "67" => "10000101100",
            "68" => "10000100110",
            "69" => "10110010000",
            "70" => "10110000100",
            "71" => "10011010000",
            "72" => "10011000010",
            "73" => "10000110100",
            "74" => "10000110010",
            "75" => "11000010010",
            "76" => "11001010000",
            "77" => "11110111010",
            "78" => "11000010100",
            "79" => "10001111010",
            "80" => "10100111100",
            "81" => "10010111100",
            "82" => "10010011110",
            "83" => "10111100100",
            "84" => "10011110100",
            "85" => "10011110010",
            "86" => "11110100100",
            "87" => "11110010100",
            "88" => "11110010010",
            "89" => "11011011110",
            "90" => "11011110110",
            "91" => "11110110110",
            "92" => "10101111000",
            "93" => "10100011110",
            "94" => "10001011110",
            "95" => "10111101000",
            "96" => "10111100010",
            "97" => "11110101000",
            "98" => "11110100010",
            "99" => "10111011110",
            "START" => "11010011100",
            "FNC1" => "11110101110",
            "STOP" => "11000111010",
            "TERMINATE" => "11",
            "START_DATA" => "105",
            "FNC1_DATA" => "102");

        try {
            $barcode_data = "";
            $arr_barcode = str_split($code, 2);

            $checksum = intval($code128c_codes["START_DATA"]);
            // Get barcode data
            $i = 1;
            foreach ($arr_barcode as $pair) {
                $i++;
                $checksum += (intval($pair) * $i);
                $trans_pair = $code128c_codes[$pair];
                if ($trans_pair != "") {
                    $barcode_data .= $trans_pair;
                } else {
                    throw new Exception("Incorrect barcode format.");
                }
            }
            $checksum += (intval($code128c_codes["FNC1_DATA"]) * 1);
            $checksum = $checksum % 103;
            $barcode_data .= $code128c_codes[str_pad($checksum, 2, '0', STR_PAD_LEFT)];
            // Buid final barcode
            $final_barcode = $code128c_codes["START"] . $code128c_codes["FNC1"] . $barcode_data . $code128c_codes["STOP"] . $code128c_codes["TERMINATE"];

            return $final_barcode;
        } catch (Exception $e) {
            print $e;
        }
    }

    /**
     * Gera imagem do codigo de barras no padrao EAN 128
     * @param string $code
     * @param integer $width
     * @param integer $height
     * @param integer $font
     * @return string 
     */
    public function gerarImagemEAN128($code, $width = 3, $height = 40, $font = 4)
    {

        $bars = self::gerarCodigoEAN128($code);
        $filename = $code . ".png";

        /* we're going to use these globals */
        $bar_color = Array(0, 0, 0);
        $bg_color = Array(255, 255, 255);
        $text_color = Array(0, 0, 0);

        $scale = 1;
        $total_y = 0;
        $space = array("bottom" => 15, "top" => 5, "left" => 15, "right" => 15);

        /* set defaults if not specified */
        if ($scale < 1)
            $scale = 2;
        $total_y = (int) ($total_y);
        if ($total_y < 1)
            $total_y = (int) $scale * 60;
        if (!$space)
            $space = array('top' => 2 * $scale, 'bottom' => 2 * $scale, 'left' => 2 * $scale, 'right' => 2 * $scale);

        /* count total width based on the number of bars we need to paint */
        $xpos = 0;
        for ($i = 0; $i < strlen($bars); $i++) {
            $xpos+=1 * $scale;
            $width = true;
        }

        /* allocate the image */
        $total_x = ( $xpos ) + $space['right'] + $space['right'];
        $xpos = $space['left'];
        if (!function_exists("imagecreate")) {
            // GD is not installed or enabled
            print "You don't have the gd2 extension enabled\n";
            return "";
        }

        $im = imagecreate($total_x, $total_y);

        /* create image stuff */
        $col_bg = ImageColorAllocate($im, $bg_color[0], $bg_color[1], $bg_color[2]);
        $col_bar = ImageColorAllocate($im, $bar_color[0], $bar_color[1], $bar_color[2]);
        $col_text = ImageColorAllocate($im, $text_color[0], $text_color[1], $text_color[2]);
        $height = round($total_y - $space['bottom']);

        /* paint the bars */
        for ($i = 0; $i < strlen($bars); $i++) {
            $val = strtolower($bars[$i]);
            $h = $height;
            if ($val == "1") {
                imagefilledrectangle($im, $xpos, $space['top'], $xpos, $h, $col_bar);
            }
            $xpos+=1 * $scale;
        }
        /* write out the text */
        $fontsize = $scale * 8;
        $fontheight = $total_y - ($fontsize / 2.5) + 2;

        self::verificarDiretorioExistente(APPLICATION_PATH . '/../data/CodigoBarras/');

        ImagePNG($im, APPLICATION_PATH . '/../data/CodigoBarras/' . $filename);

        return APPLICATION_PATH . '/../data/CodigoBarras/' . $filename;
    }

    /**
     * Formata o codigo no padrao EAN 128
     * @param string $code
     * @return string 
     */
    public static function formatarCodigoEAN128Embalagem($code)
    {
        $code = self::preencheZerosEsquerda($code, 10);

        return $code;
    }

    /**
     * Formata o codigo no padrao EAN 128
     * @param string $code
     * @param string $codigoSequencial
     * @param string $numVolumes
     * @return string 
     */
    public static function formatarCodigoEAN128Volume($code)
    {
        $code = self::preencheZerosEsquerda($code, 14);

        return $code;
    }

    public static function gerarNovo($code) {

        // Nome arquivo
        $nomeArquivo = $code . ".png";
        $code = self::preencheZerosEsquerda($code, 11);
        $lw = 3; $hi = 40;
        $Lencode = array('0001101','0011001','0010011','0111101','0100011',
            '0110001','0101111','0111011','0110111','0001011');
        $Rencode = array('1110010','1100110','1101100','1000010','1011100',
            '1001110','1010000','1000100','1001000','1110100');
        $ends = '101'; $center = '01010';

        $ncode = '0'.$code;
        $even = 0;
        $odd = 0;
        $x = 0;
        for ($x=0;$x<13;$x++) {
            if ($x % 2) { $odd += $ncode[$x]; } else { $even += $ncode[$x]; }
        }
        $code.=(10 - (($odd * 3 + $even) % 10)) % 10;
        /* Create the bar encoding using a binary string */
        $bars=$ends;
        $bars.=$Lencode[$code[0]];
        for($x=1;$x<6;$x++) {
            $bars.=$Lencode[$code[$x]];
        }
        $bars.=$center;
        for($x=6;$x<13;$x++) {
            $bars.=$Rencode[$code[$x]];
        }
        $bars.=$ends;
        /* Generate the Barcode Image */
        $img = ImageCreate($lw*95+30,$hi+30);
        var_dump($bars);
        $fg = ImageColorAllocate($img, 0, 0, 0);
        var_dump($bars);
        $bg = ImageColorAllocate($img, 255, 255, 255);
        var_dump($bars);
        ImageFilledRectangle($img, 0, 0, $lw*95+30, $hi+30, $bg);
        var_dump($bars); exit;
        $shift=10;
        for ($x=0;$x<strlen($bars);$x++) {
            if (($x<10) || ($x>=45 && $x<50) || ($x >=85)) { $sh=10; } else { $sh=0; }
            if ($bars[$x] == '1') { $color = $fg; } else { $color = $bg; }
            ImageFilledRectangle($img, ($x*$lw)+15,5,($x+1)*$lw+14,$hi+5+$sh,$color);
        }
        /* Add the Human Readable Label */
        ImageString($img,4,5,$hi-5,$code[0],$fg);
        for ($x=0;$x<5;$x++) {
            ImageString($img,5,$lw*(13+$x*6)+15,$hi+5,$code[$x+1],$fg);
            ImageString($img,5,$lw*(53+$x*6)+15,$hi+5,$code[$x+6],$fg);
        }
        ImageString($img,4,$lw*95+17,$hi-5,$code[11],$fg);

        self::verificarDiretorioExistente(APPLICATION_PATH . '/../data/CodigoBarras/');

        ImagePNG($img, APPLICATION_PATH . '/../data/CodigoBarras/' . $nomeArquivo);

        return APPLICATION_PATH . '/../data/CodigoBarras/' . $nomeArquivo;
    }
}
