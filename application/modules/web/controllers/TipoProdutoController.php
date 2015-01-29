<?php

use Wms\Domain\Entity\Produto\Tipo,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Grid\Produto\Tipo as TipoGrid;

/**
 * Description of Web_TipoProdutoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_TipoProdutoController extends Crud
{

    protected $entityName = 'Produto\Tipo';

    public function indexAction()
    {
        $grid = new TipoGrid;
        $this->view->grid = $grid->init()->render();
    }

}