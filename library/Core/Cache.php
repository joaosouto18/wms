<?php

namespace Core;

use \Zend_Cache;

/**
 * Description of Cache
 *
 * @author medina
 */
class Cache {
    /**
     *
     * @var \Zend_Cache
     */
    private $cache;
    
    /**
     * 
     */
    public function __construct()
    {
        $frontendOptions = array(
            'lifetime' => 604800, // cache lifetime de 6 semanas
            'automatic_serialization' => true
        );

        $backendOptions = array(
            'cache_dir' => APPLICATION_PATH . '/../cache/', // Directory where to put the cache files
            'cache_file_umask' => 0700
        );
        
        // getting a Zend_Cache_Core object
        $this->cache = \Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    }
    /**
     *
     * @param string $id
     * @return boolean 
     */
    public function load($id) {
        return $this->cache->load($id);
    }
    /**
     *
     * @param mixed $acl
     * @param string $id
     * @return boolean 
     */
    public function save($acl, $id) {
        return $this->cache->save($acl, $id);
    }
    /**
     *
     * @param string $id Id do cache para remocao
     * @return boolean 
     */
    public function delete($id)
    {
        return $this->cache->remove($id);
    }
}