<?php

use Wms\Module\Web\Page;

class Inventario_ComparativoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        ini_set('max_execution_time', 3000);
        ini_set('memory_limit', '-1');
        $this->configurePage();
        $params = $this->_getAllParams();
        $form = new \Wms\Module\Inventario\Form\FormComparativo();
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueErpRepository $estoqueErpRepo */
        $estoqueErpRepo = $this->_em->getRepository("wms:Enderecamento\EstoqueErp");

        $form->populate($params);
        $this->view->form = $form;

        $idInventario = null;
        if (isset($params['inventario'])&& ($params['inventario'] != null)) {
            $idInventario = $params['inventario'];
        }

        if (!empty($params['inventario']) || !empty($params['divergencia']) || !empty($params['tipoDivergencia']) || !empty($params['linhaSeparacao'])) {
            $result = $estoqueErpRepo->getProdutosDivergentesByInventario($idInventario, $params);
            $grid = new \Wms\Module\Inventario\Grid\ComparativoEstoque();
            $this->view->grid = $grid->init($result);

            if (isset($params['gerarPdf']) && !empty($params['gerarPdf'])) {
                $pdf = array();
                foreach ($result as $line) {
                    $pdf[] = array(
                        'Código' => $line['COD_PRODUTO'],
                        'Grade' => $line['DSC_GRADE'],
                        'Produto' => $line['DSC_PRODUTO'],
                        'Estoque WMS' => $line['ESTOQUE_WMS'],
                        'Estoque ERP' => $line['ESTOQUE_ERP'],
                        'Divergência' => $line['DIVERGENCIA']);
                }
                $this->exportCSV($pdf, 'comparativoEstoque', 'Comparativo de Estoque');
            }
        }

    }

    public function saldoAction(){
        ini_set('max_execution_time', 3000);
        ini_set('memory_limit', '-1');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        $idAcao = $this->getSystemParameterValue('COD_ACAO_INTEGRACAO_ESTOQUE');
        $acaoEn = $acaoIntRepo->find($idAcao);
        if ($acaoEn != null) {
            $acaoIntRepo->processaAcao($acaoEn);
        } else {
            $this->addFlashMessage('error','Integração com ERP não configurada');
        }

        $this->redirect('index');
    }

    public function configurePage()
    {
        $buttons[] = array(
            'label' => 'Consultar Saldo do ERP',
            'cssClass' => 'button atualizarEstoque',
            'urlParams' => array(
                'module' => 'inventario',
                'controller' => 'comparativo',
                'action' => 'saldo',
            ),
            'tag' => 'a'
        );
        Page::configure(array('buttons' => $buttons));
    }

}