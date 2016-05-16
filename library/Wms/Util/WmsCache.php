<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 13/05/2016
 * Time: 14:19
 */

namespace Wms\Util;


class WmsCache
{

    /**
     * Exemplo de uso
     *
     * if (!WmsCache::checkDataCache("teste")) {
     *      $array = array(
     *          'Key1' => 'value1',
     *          'Key2' => 'value2',
     *          'Key3' => 'value3'
     *      );
     *      WmsCache::setDataCache(30, "teste", $array);
     * } else {
     *      var_dump(WmsCache::getDataCache("teste"));
     * }
     */

    /**
     * @param int|null $lifetime
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    private static function _getCacheObject($lifetime = null){

        $frontendOptions = array(
            'lifetime' => (!empty($lifetime)) ? $lifetime: 3600,
            'automatic_serialization' => true
        );

        $backendOptions = array(
            'cache_dir' => CACHE_PATH
        );

        return \Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    }

    /**
     * Método para a recuperação do objeto (caso exista) em cache
     *
     * @param string $cacheName
     * @return false|mixed
     */
    public static function getDataCache($cacheName){

        if (self::_getCacheObject()->test($cacheName)){

            return self::_getCacheObject()->load($cacheName);

        }

        return false;
    }

    /**
     * Método para salvar objetos em cache
     *
     * @param int|null $lifeTime
     * @param string|null $cacheName
     * @param mixed|string|int|array|object $data
     */
    public static function setDataCache($lifeTime = null, $cacheName = null, $data){

        if(!is_int($lifeTime = intval($lifeTime))) {
            $lifeTime = 3600;
        }

        $cacheObj = self::_getCacheObject($lifeTime);

        $cacheObj->save($data, $cacheName);
    }

    /**
     * Método para verificar se o cache existe ou ainda não expirou
     *
     * @param string $cacheName
     * @return false|int
     */
    public static function checkDataCache($cacheName){

        return self::_getCacheObject()->load($cacheName);

    }

    /**
     * Método para excluir um objeto (caso exista) do cache
     *
     * @param string $cacheName
     */
    public static function deleteDataCache($cacheName){

        if (self::_getCacheObject()->test($cacheName)){

            self::_getCacheObject()->remove($cacheName);

        }
    }
}