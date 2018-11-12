<?php

namespace Wms\Module\Web\Grid;

/**
 * Description of MascaraRecurso
 *
 * @author medina
 */
class MascaraRecurso
{

    public static function condition($row)
    {
        $date = new \DateTime();
        
        return ($row['datInicioVigencia']->format('Ymd') > $date->format('Ymd')) ? true : false;
    }

}