<?php

use Wms\Domain\Entity\NotaFiscal,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Grid\Produto\Classe as ClasseGrid;

/**
 * Description of Web_ProdutoClasseController
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class Web_ProdutoClasseController extends \Wms\Controller\Action
{

    protected $entityName = 'Produto\Classe';

    public function indexAction()
    {
        $grid = new ClasseGrid;
        $this->view->grid = $grid->init()->render();
    }

}
