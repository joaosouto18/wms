<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao\ModeloSeparacao as ModelosGrid,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Domain\Entity\Expedicao;

class Expedicao_ModeloSeparacaoController  extends  Crud
{
    protected $entityName = 'Expedicao\ModeloSeparacao';

    public function indexAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloRepo */
        $modeloRepo   = $this->em->getRepository('wms:Expedicao\ModeloSeparacao');

        $modelos = $modeloRepo->getModelos();

        $grid = new ModelosGrid();
        $this->view->grid = $grid->init($modelos)->render();
    }
}