<?php
/**
 * Created by PhpStorm.
 * User: Cesar
 * Date: 13/03/2017
 * Time: 13:55
 */

namespace Wms\Util;

use Exception;

class DetalhaResumoConferenciaException extends \Exception
{
    /** @var $link string */
    private $link;

    public function __construct($message = "", $link = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}