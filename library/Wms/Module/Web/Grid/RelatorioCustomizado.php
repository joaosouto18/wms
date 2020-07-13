<?php

namespace Wms\Module\Web\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class RelatorioCustomizado extends Grid
{
    /**
     *
     * @param array $params 
     */

    public function init($arrayResult, $assemblyData)
    {
        $title = $assemblyData['title'];

        $this->setAttrib('title',$title);
        $this->setSource(new \Core\Grid\Source\ArraySource($arrayResult))
            ->setId($title)
            ->setAttrib('class', $title);

        foreach ($arrayResult[0] as $key => $value) {
            $this->addColumn(array(
               'label' => str_replace('_',' ',$key),
               'index' => $key
            ));
        }

        $pager = new Pager((count($arrayResult) - 1),0,count($arrayResult));
        $this->setPager($pager);
        //$this->showPager = false;
        $this->showExport = false;

        return $this;
    }

}

