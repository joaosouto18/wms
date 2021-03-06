<?php

namespace Wms\Domain;
 
use Doctrine\ORM\Mapping\Entity;

class Configurator {
    /** Configure a target object with the provided options.
     * 
     *  The options passed in must be a Traversable object with option names for keys.
     *  Option names are case insensitive and will be stripped of underscores. By convention,
     *  option names are in lowercase and with underscores separated words.
     * 
     *  The target object is expected to have a setter method for each option passed. By convention,
     *  setters are named using camelcase with a 'set' prefix.
     * 
     *  Example: option_name -> setOptionName()
     * 
     *  @param object $target The object that needs to be configured.
     *  @param \Traversable $options The configuration to apply. Traversable is amongst
                                     others implemented by Zend\Config and arrays
     *  @param boolean $tryCall When true and $target has a __call() function, try call if no setter is available.
     *  @return Object
     *  @throws \Exception
     */
    public static function configure($target, $options, $tryCall=false)
    {
        if ( !is_object($target) )
        {
            throw new \Exception('Target should be an object');
        }
        if ( !($options instanceof Traversable) && !is_array($options) )
        { 
            throw new \Exception('$options should implement Traversable');
        }
         
        $tryCall = (bool) $tryCall && method_exists($target, '__call');
         
        foreach ($options as $name => &$value)
        { 
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))); 
     
            if( $tryCall || method_exists($target,$setter) )
            {
                call_user_func(array($target,$setter),$value);
            }
            else
            {
                continue; // instead of throwing an exception
            }
        }   
        return $target;
    }

    public static function configureToArray($element, $tryCall=false)
    {
        if(!is_array($element) && is_object($element)) {

            $tryCall = (bool)$tryCall && method_exists($element, '__call');
            $result = array();

            foreach (get_class_methods($element) as $method) {
                $pos = strpos($method, 'get');
                if ($pos === 0) {
                    $attribute = lcfirst(str_replace('get', '', $method));
                    if ($tryCall || method_exists($element, $method)) {
                        $value = call_user_func(array($element, $method));
                        $result[$attribute] = $value;
                    }
                }
            }

            return $result;
        } else if (is_array($element)){
            $result =  array();

            foreach ($element as $obj){
                $tryCall = (bool)$tryCall && method_exists($obj, '__call');
                $arr = array();

                foreach (get_class_methods($obj) as $method) {
                    $pos = strpos($method, 'get');
                    if ($pos === 0) {
                        $attribute = lcfirst(str_replace('get', '', $method));
                        if ($tryCall || method_exists($obj, $method)) {
                            $value = call_user_func(array($obj, $method));
                            $arr[$attribute] = $value;
                        }
                    }
                }
                array_push($result,$arr);
            }
            return $result;
        } else {

           throw new \Exception('Element should be an object');
        }
    }
}
 
