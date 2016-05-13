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
     * @param string $cacheName
     * @return false|mixed
     * @throws \Exception
     */
    public static function getDataCache($cacheName){

        if (self::_getCacheObject()->test($cacheName)){

            return self::_getCacheObject()->load($cacheName);

        }

        throw new \Exception("Nenhum cache com este nome foi encontrado");
    }

    /**
     * @param int|null $lifeTime
     * @param string|null $cacheName
     * @param mixed|string|int|array|object $data
     * @throws \Exception
     */
    public static function setDataCache($lifeTime = null, $cacheName = null, $data){

        if(!is_int($lifeTime = intval($lifeTime))) {
            $lifeTime = 3600;
        }

        $cacheObj = self::_getCacheObject($lifeTime);

        $cacheObj->save($data, $cacheName);
    }

    /**
     * @param string $cacheName
     * @return false|int
     */
    public static function checkDataCache($cacheName){
        
        return self::_getCacheObject()->load($cacheName);

    }

    public static function deleteDataCache($cacheName){

        if (self::_getCacheObject()->test($cacheName)){

            self::_getCacheObject()->remove($cacheName);

        }

        throw new \Exception("Nenhum cache com este nome foi encontrado");
    }
}