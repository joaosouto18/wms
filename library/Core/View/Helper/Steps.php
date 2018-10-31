<?php

/**
 * Description of Steps
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Renato Medina
 */
class Core_View_Helper_Steps extends \Zend_View_Helper_Abstract
{

    /**
     *
     * @param array $list
     * @param int $key
     * @return string
     * @throws \Exception 
     */
    public function steps(array $list = array(), $index = 0)
    {
        $html = '<ul class="steps">';

        if (!is_array($list))
            throw new \Exception('The sent list is not a array');

        foreach ($list as $key => $row) {
            
            if(is_array($row)) {
                $checkedClass = ($row['checked'] == 1) ? 'checked' : '';
                $text = $row['text'];
            }
            else {
                $checkedClass = ($key <= $index) ? 'checked' : '';
                $text = $row;
            }
            
            $html .= '
                <li class="' . $checkedClass . '">
                    <span class="dot"></span>
                    <span class="text">' . $text . '</span>
                </li>
            ';
        }
        $html .= '</ul>';

        return $html;
    }

}