<?php
/**
 * Created by PhpStorm.
 * User: Cesar
 * Date: 13/03/2017
 * Time: 13:55
 */

namespace Wms\Util;

use Exception;

class WMS_Exception extends \Exception
{
    /** @var $link string */
    private $link;

    public function __construct($message = "", $link = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        self::setLink($link);
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

}