<?php

namespace Wms\Util;

/**
 * Description of Endereco
 *
 * @author medina
 *
 * @update 06/01/2017
 * @author Tarcísio César
 *
 */
class Endereco
{

    const FORMATO_DESCRICAO = 1;
    const FORMATO_COD_BARRAS = 2;

    /**
     * Esta função retorna uma matriz associativa com a quantidade de digitos de cada elemento do endereço de acordo com os parametros definidos
     *
     * Ex.:
     *
     * retorno = array('rua' => 2, 'predio' => 3, 'nivel' => 2, 'apto' => 2)
     *
     * @return array
     */
    public static function getQtdDigitos()
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $arrCriterio = array('TAMANHO_CARACT_RUA', 'TAMANHO_CARACT_PREDIO', 'TAMANHO_CARACT_NIVEL', 'TAMANHO_CARACT_APARTAMENTO');

        $params = $em->getRepository('wms:Sistema\Parametro')->findBy(array('constante' => $arrCriterio));

        $arrParams = array();

        /** @var \Wms\Domain\Entity\Sistema\Parametro $param */
        foreach ($params as $param) {
            $arrParams[$param->getConstante()] = $param->getValor();
        }

        return array(
            'rua' => $arrParams['TAMANHO_CARACT_RUA'],
            'predio' => $arrParams['TAMANHO_CARACT_PREDIO'],
            'nivel' => $arrParams['TAMANHO_CARACT_NIVEL'],
            'apto' => $arrParams['TAMANHO_CARACT_APARTAMENTO']
        );
    }

    /**
     * Esta função retorna um inteiro co a soma total de digitos de um endereço de acordo com os parametros definidos
     *
     * @param null|array $qtdDigitos
     * @param bool $considerarSeparador
     * @return int
     */
    public static function getTotalDigitos($qtdDigitos = null, $considerarSeparador = false)
    {
        $arrQtdDigitos = (empty($qtdDigitos) || !is_array($qtdDigitos))? self::getQtdDigitos() : $qtdDigitos;

        $total = 0;

        foreach ($arrQtdDigitos as $elemento) {
            $total += (int) $elemento;
        }

        if ($considerarSeparador)
            $total += (count($arrQtdDigitos) - 1);

        return $total;
    }

    /**
     * Esta função recebe um array ($qtdDigitos) com a quantidade de digitos de cada campo do endereço
     * Caso necessite da mascara em um digito específico, o mesmo deve ser passado no parametro como string $digito
     *
     * Ex.:
     * $qtdDigitos = array('rua' => 2, 'predio' => 3, 'nivel' => 2, 'apto' => 2)
     * $digito = '9' (default = '0')
     *
     * retorno = '99.999.99.99'
     *
     * @param array $qtdDigitos
     * @param string $digito
     * @return string Retorna o formato de acordo com as configurações de cada campo do endereço
     */
    public static function mascara($qtdDigitos = null, $digito = '0')
    {
        $qtdDigitos = (empty($qtdDigitos) || !is_array($qtdDigitos))? self::getQtdDigitos() : $qtdDigitos;

        $arrParams = array(
            'rua' => self::formatarRua($digito, $qtdDigitos['rua'], $digito),
            'predio' => self::formatarPredio($digito, $qtdDigitos['predio'], $digito),
            'nivel' => self::formatarNivel($digito, $qtdDigitos['nivel'], $digito),
            'apto' => self::formatarApto($digito, $qtdDigitos['apto'], $digito)
        );

        return implode('.', $arrParams);
    }

    /**
     * Retorna o endereco separado no formato de cada elemento
     * O parametro $qtdDigitos recebe uma matriz associativa com a quantidade de digitos de cada elemento do endereço
     * Caso não seja passado será aplicado o interno de acordo com a definições dos parametros
     *
     * Ex.:
     * $endereco = 01.001.01.01
     * $qtdDigitos = array('rua' => 2, 'predio' => 3, 'nivel' => 2, 'apto' => 2)
     *
     * retorno = array('rua' => '01', 'predio' => '001', 'nivel' => '01', 'apto' => '01')
     *
     * @param string $endereco
     * @param array|null $qtdDigitos
     * @return array Matriz associativa de (rua, predio, nivel, apto)
     * @throws \Exception
     */
    public static function separar($endereco, $qtdDigitos = null)
    {
        $result = null;

        //Se endereço tiver ponto "." ele será o critério de quebra
        if (strpos($endereco,'.')) {

            $valor = explode('.', $endereco);

            $result = array(
                'rua' => $valor[0],
                'predio' => $valor[1],
                'nivel' => $valor[2],
                'apto' => $valor[3],
            );

        }

        //Se não tiver ponto "." o critério será a qtd de digitos para cada campo
        //de acordo com a configuração dos parametros de cada elemento
        else {
            $qtdDigitos = (empty($qtdDigitos) || !is_array($qtdDigitos))? self::getQtdDigitos() : $qtdDigitos;
            $dgtRua = (int) $qtdDigitos['rua'];
            $dgtPredio = (int) $qtdDigitos['predio'];
            $dgtNivel = (int) $qtdDigitos['nivel'];
            $dgtApto = (int) $qtdDigitos['rua'];

            $totalDigtos = self::getTotalDigitos($qtdDigitos);
            if (($totalDigtos - strlen($endereco)) == 1) {
                $endereco = '0' . $endereco;
            } elseif (($totalDigtos - strlen($endereco)) > 1) {
                throw new \Exception('Endereço não contém a quantidade mínima de dígitos');
            }

            $result = array(
                'rua' => (int) substr($endereco, 0, $dgtRua),
                'predio' => (int) substr($endereco, $dgtRua, $dgtPredio),
                'nivel' => (int) substr($endereco, ($dgtRua + $dgtPredio), $dgtNivel),
                'apto' => (int) substr($endereco, ($dgtRua + $dgtPredio + $dgtNivel), $dgtApto),
            );

        }
        return $result;
    }

    /**
     * Retorna o endereco formatado de acordo com os parametros de endereço do sistema
     * O parametro $formato recebe um inteiro das constantes FORMATO_DESCRICAO ou FORMATO_COD_BARRAS para
     * definir em qual formato se espera o retorno, caso não definido o padrão é o formato de descrição
     *
     * Ex.:
     * $endereco = '1.4.0.1'  ou   $endereco = array('rua' => '1', 'predio' => '4', 'nivel' => '0', 'apto' => '1')
     * $dgtComplementar = empty (default ='0')
     * $formato = empty (default = FORMATO_DESCRICAO)
     *
     * retorno = '01.004.00.01'
     *
     *  Ex2.:
     * $endereco = '1.4.0.1'  ou   $endereco = array('rua' => '1', 'predio' => '4', 'nivel' => '0', 'apto' => '1')
     * $dgtComplementar = empty (default ='0')
     * $formato = FORMATO_COD_BARRAS
     *
     * retorno = '010040001'
     *
     * O parametro $dgtComplementar não é obrigatório por padrão será o digito '0'
     * Define apenas qual o digito será utilizado para preencher o formato
     *
     * Ex.:
     * $endereco = '2.03.2.1'
     * $dgtComplementar = '9' (default ='0')
     * $formato = empty (default = FORMATO_DESCRICAO)
     *
     * retorno = '92.903.92.91'
     *
     * O parametro $qtdDigitos recebe uma matriz associativa com a quantidade de digitos de cada elemento do endereço
     * Caso não seja passado será aplicado o interno de acordo com a definições dos parametros
     *
     * @param array|string $endereco
     * @param string $dgtComplementar
     * @param int $formato
     * @param array $qtdDigitos
     * @return string $dscEndereco
     * @throws \Exception Caso $endereco seja passado faltando algum parametro
     */
    public static function formatar($endereco, $qtdDigitos = null, $dgtComplementar = '0', $formato = self::FORMATO_DESCRICAO)
    {
        $arrEndereco = (!is_array($endereco)) ? self::separar($endereco) : $endereco;

        $qtdDigitos = (empty($qtdDigitos) || !is_array($qtdDigitos))? self::getQtdDigitos() : $qtdDigitos;
        $dscEndereco = array();

        if (isset($arrEndereco['rua'])) {
            $rua = self::formatarRua($arrEndereco['rua'], (int)$qtdDigitos['rua'], $dgtComplementar);
            $dscEndereco['rua'] = $rua;
        } else {
            throw new \Exception('Elemento "rua" não definido');
        }

        if (isset($arrEndereco['predio'])) {
            $predio = self::formatarPredio($arrEndereco['predio'], (int) $qtdDigitos['predio'], $dgtComplementar);
            $dscEndereco['predio'] = $predio;
        } else {
            throw new \Exception('Elemento "$predio" não definido');
        }

        if (isset($arrEndereco['nivel'])) {
            $nivel = self::formatarNivel($arrEndereco['nivel'], (int) $qtdDigitos['nivel'], $dgtComplementar);
            $dscEndereco['nivel'] = $nivel;
        } else {
            throw new \Exception('Elemento "nivel" não definido');
        }

        if (isset($arrEndereco['apto'])) {
            $apartamento = self::formatarApto($arrEndereco['apto'], (int) $qtdDigitos['apto'], $dgtComplementar);
            $dscEndereco['apto'] = $apartamento;
        } else {
            throw new \Exception('Elemento "apto" não definido');
        }

        $result = null;
        if ($formato == self::FORMATO_DESCRICAO) {
            $result = implode('.', $dscEndereco);
        } elseif ($formato == self::FORMATO_COD_BARRAS){
            $result = implode('', $dscEndereco);
        } else {
            throw new \Exception("Formato de retorno fora do padrão");
        }

        return $result;
    }

    /**
     * Esta função formata exclusivamente o campo relativo ao nome
     * Se $qtdDigitos não for informada, por padrão será a qtd definida no parametro
     * Se $dgtComplementar não for informada, por padrão será o digito '0'
     * Define apenas qual o digito será utilizado para preencher o formato
     *
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = 2
     * $dgtComplementar = null (default = '0')
     *
     * retorno = '03'
     *
     * Ou
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = null (exemplo de valor do parametro do sistema = 2)
     * $dgtComplementar = '9'
     *
     * retorno = '93'
     *
     * @param int|string $elemento
     * @param int|null $qtdDigitos
     * @param string $dgtSuplementar
     * @return string
     */
    public static function formatarRua($elemento, $qtdDigitos = null, $dgtSuplementar = '0')
    {
        $qtdDigitos = (empty($qtdDigitos) || !is_numeric($qtdDigitos)) ? (int) self::getQtdDigitos()['rua'] : (int) $qtdDigitos;
        return str_pad($elemento, $qtdDigitos, $dgtSuplementar, STR_PAD_LEFT);
    }

    /**
     * Esta função formata exclusivamente o campo relativo ao nome
     * Se $qtdDigitos não for informada, por padrão será a qtd definida no parametro
     * Se $dgtComplementar não for informada, por padrão será o digito '0'
     * Define apenas qual o digito será utilizado para preencher o formato
     *
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = 3
     * $dgtComplementar = null (default = '0')
     *
     * retorno = '003'
     *
     * Ou
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = null (exemplo de valor do parametro do sistema = 3)
     * $dgtComplementar = '9'
     *
     * retorno = '993'
     *
     * @param int|string $elemento
     * @param int|null $qtdDigitos
     * @param string $dgtSuplementar
     * @return string
     */
    public static function formatarPredio($elemento, $qtdDigitos = null, $dgtSuplementar = '0')
    {
        $qtdDigitos = (empty($qtdDigitos) || !is_numeric($qtdDigitos)) ? (int) self::getQtdDigitos()['predio'] : (int) $qtdDigitos;
        return str_pad($elemento, $qtdDigitos, $dgtSuplementar, STR_PAD_LEFT);
    }

    /**
     * Esta função formata exclusivamente o campo relativo ao nome
     * Se $qtdDigitos não for informada, por padrão será a qtd definida no parametro
     * Se $dgtComplementar não for informada, por padrão será o digito '0'
     * Define apenas qual o digito será utilizado para preencher o formato
     *
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = 2
     * $dgtComplementar = null (default = '0')
     *
     * retorno = '03'
     *
     * Ou
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = null (exemplo de valor do parametro do sistema = 2)
     * $dgtComplementar = '9'
     *
     * retorno = '93'
     *
     * @param int|string $elemento
     * @param int|null $qtdDigitos
     * @param string $dgtSuplementar
     * @return string
     */
    public static function formatarNivel($elemento, $qtdDigitos = null, $dgtSuplementar = '0')
    {
        $qtdDigitos = (empty($qtdDigitos) || !is_numeric($qtdDigitos)) ? (int) self::getQtdDigitos()['nivel'] : (int) $qtdDigitos;
        return str_pad($elemento, $qtdDigitos, $dgtSuplementar, STR_PAD_LEFT);
    }

    /**
     * Esta função formata exclusivamente o campo relativo ao nome
     * Se $qtdDigitos não for informada, por padrão será a qtd definida no parametro
     * Se $dgtComplementar não for informada, por padrão será o digito '0'
     * Define apenas qual o digito será utilizado para preencher o formato
     *
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = 2
     * $dgtComplementar = null (default = '0')
     *
     * retorno = '03'
     *
     * Ou
     * Ex.:
     * $elemento = 3 ou '3'
     * $qtdDigito = null (exemplo de valor do parametro do sistema = 2)
     * $dgtComplementar = '9'
     *
     * retorno = '93'
     *
     * @param int|string $elemento
     * @param int|null $qtdDigitos
     * @param string $dgtSuplementar
     * @return string
     */
    public static function formatarApto($elemento, $qtdDigitos = null, $dgtSuplementar = '0')
    {
        $qtdDigitos = (empty($qtdDigitos) || !is_numeric($qtdDigitos)) ? (int) self::getQtdDigitos()['apto'] : (int) $qtdDigitos;
        return str_pad($elemento, $qtdDigitos, $dgtSuplementar, STR_PAD_LEFT);
    }

}
