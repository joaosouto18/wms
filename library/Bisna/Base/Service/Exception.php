<?php
namespace Bisna\Base\Service;

/**
 * Description of Exception
 *
 * @author Daniel Lima <yourwebmaker@gmail.com>
 */
class Exception extends \Exception
{
    public function __construct($errorMessage, $errorCode)
    {
	return $errorMessage;
    }
}

?>
