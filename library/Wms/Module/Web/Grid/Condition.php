<?php

namespace Wms\Module\Web\Grid;

/**
 * Description of Deposito
 *
 * @author medina
 */
class Condition
{

    public static function isAtivo($row)
    {        
        return ($row['isAtivo'] == 'S') ? true : false;
    }
    
    public static function isInativo($row)
    {        
        return ($row['isAtivo'] == 'N') ? true : false;
    }

}