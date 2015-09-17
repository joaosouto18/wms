<?php
namespace Wms\Module\Enderecamento\Form;

use Wms\Module\Web\Form;


class Modelo extends Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array('module' => 'enderecamento', 'controller' => 'modelo', 'action' => 'index')))
            ->setAttribs(array(
                'method' => 'POST',
                'id' => 'modelo-enderecamento'
            ));
    }



}