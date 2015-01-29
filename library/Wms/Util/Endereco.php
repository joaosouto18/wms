<?php

namespace Wms\Util;

/**
 * Description of Endereco
 *
 * @author medina
 */
class Endereco
{

    /**
     * Retorna formato de mascara para cada elemento de um endereco
     * @return array Matriz associativa de (RUA, PREDIO, NIVEL, APTO)
     */
    public static function mascara()
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $parametro = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 3, 'constante' => 'FORMATO_MASCARA_ENDERECO'));

        $valor = explode('.', $parametro->getValor());

        return array(
            'RUA' => strlen($valor[0]),
            'PREDIO' => strlen($valor[1]),
            'NIVEL' => strlen($valor[2]),
            'APTO' => strlen($valor[3]),
        );
    }

    /**
     * Retorna o endereco separado no formato de cada elemento de um endereco
     * @param String $endereco
     * @return array Matriz associativa de (RUA, PREDIO, NIVEL, APTO)
     */
    public static function separar($endereco)
    {
        $valor = explode('.', $endereco);

        return array(
            'RUA' => $valor[0],
            'PREDIO' => $valor[1],
            'NIVEL' => $valor[2],
            'APTO' => $valor[3],
        );
    }

    /**
     * Retorna o endereco formatado de acordo com a mascara definida nos parametros do sistema
     * @param Array $endereco
     * @return string $dscEndereco
     */
    public static function formatar(array $endereco)
    {
        $mascaraEndereco = self::mascara();

        $rua = str_pad($endereco['RUA'], $mascaraEndereco['RUA'], '0', STR_PAD_LEFT);
        $predio = str_pad($endereco['PREDIO'], $mascaraEndereco['PREDIO'], '0', STR_PAD_LEFT);
        $nivel = str_pad($endereco['NIVEL'], $mascaraEndereco['NIVEL'], '0', STR_PAD_LEFT);
        $apartamento = str_pad($endereco['APTO'], $mascaraEndereco['APTO'], '0', STR_PAD_LEFT);

        $dscEndereco = array($rua, $predio, $nivel, $apartamento);

        $dscEndereco = implode('.', $dscEndereco);

        return $dscEndereco;
    }

}
