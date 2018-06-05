<?php
use Wms\Controller\Action,
    Wms\Module\Web\Form\FiltroCorte,
    Wms\Module\Web\Grid\Corte as CorteGrid;

/**
 * Descrição: Classe Controller destinada para exibir o filtro de busca
 * e os resultados do Grid para listar todos os cortes por dia / produto
 *
 * @author Diogo Marcos <contato@diogomarcos.com>
 */
class Web_Consulta_CorteController extends Action
{
    public function indexAction()
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");

        $form = new FiltroCorte();
        $form->init($utilizaGrade);
        $this->view->form = $form;

        $params = $this->_getAllParams();

        $data = new \DateTime;

        if ( !empty($params) ) {
            if ( empty($params['dataInicial']) ){
                $params['dataInicial']=$data->format('d/m/Y');
            }
        } else {
            $data = new \DateTime;
            $params = array(
                'dataInicial' => $data->format('d/m/Y')
            );
        }

        $form->populate($params);

        $Grid = new CorteGrid();
        $this->view->grid = $Grid->init($params)->render();
    }
}