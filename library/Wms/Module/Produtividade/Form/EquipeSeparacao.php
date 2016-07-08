<?php
namespace Wms\Module\Produtividade\Form;

use Wms\Module\Produtividade\Form\Subform\EtiquetaSeparacao;
use Wms\Module\Produtividade\Form\Subform\MapaSeparacao;
use Wms\Module\Web\Form;

class EquipeSeparacao extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'equipe-separacao-form', 'class' => 'saveForm'));

        $this->addSubFormTab('Vincular Etiquetas', new EtiquetaSeparacao, 'etiquetas');
        $this->addSubFormTab('Vincular Mapas', new MapaSeparacao(), 'mapas');
    }

}