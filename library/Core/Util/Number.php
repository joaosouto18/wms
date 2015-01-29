<?php
namespace Core\Util;

class Number
{
    /**
     *
     * @param type $val
     * @return type 
     */
    public static function toInt($float)
    {
	return (int) preg_replace('[\D]', '', $float);
    }

}
