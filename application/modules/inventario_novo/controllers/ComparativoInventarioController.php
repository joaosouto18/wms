<?php


use Wms\Module\Web\Controller\Action;
use Wms\Module\Web\Page;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_Novo_ComparativoInventarioController  extends Action
{

    public function indexAction()
    {

        $params = $this->getRequest()->getParams();
        $modeloExportacao = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");
        $invRepo = $this->getEntityManager()->getRepository("wms:InventarioNovo");

        $filtroForm = new \Wms\Module\InventarioNovo\Form\ComparativoInventarioForm();
        try {

            if (isset($params['btnSubmit']) || isset($params['btnExport'])) {
                $idInventarioERP = $params['codInventario'];
                $inv = $invRepo->findOneBy(array('codErp' => $idInventarioERP));
                if ($inv == null) {
                    throw new \Exception('Nenhum inventário no WMS encontrado setado para o inventário ' . $idInventarioERP . ' no ERP');
                }
            }

            if (isset($params['btnSubmit'])) {
                $idInventario = $params['codInventario'];
                $invWMS = $this->getServiceLocator()->getService("Inventario")->getResultadoInventarioComparativo($modeloExportacao,$idInventario);

                $invERP = array();
                $invERP[] = array('COD_PRODUTO' => '8', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => '	OVOS BRANCOS GRANDES "A" GRANEL CX 30 DZ');
                $invERP[] = array('COD_PRODUTO' => '1013', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1013');

                if (count($invERP) == 0) {
                    throw new \Exception("Nenhum produto setado para o inventário no ERP encontrado");
                }
                $result = $this->getServiceLocator()->getService("Inventario")->comparataInventarioWMSxERP($invWMS, $invERP);

                $filtroForm->init(true);

                $resultForm = new \Wms\Module\InventarioNovo\Form\ResultadoComparativoInventarioForm();
                $resultForm->setDefaultsGrid($result);
                $this->view->resultadoForm = $resultForm;
            } else if (isset($params['btnExport'])) {
                $idInventario = $inv->getId();

                $filtroForm->init(true);

                $modelo = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");
                if (empty($modelo))
                    throw new Exception("O modelo de exportação não foi definido! Por favor, defina em <b>Sistemas->Configurações->Inventário->Formato de Exportação do Inventário</b>");

                if ($modelo == 1) {
                    $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo1($idInventario);
                } elseif ($modelo == 3){
                    $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo3($idInventario);
                } elseif ($modelo == 4){
                    $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo4($idInventario);
                }
                $this->addFlashMessage('success', "Inventário $idInventario exportado com sucesso");

                $this->redirect('index');
            }
        } catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
        }

        $filtroForm->setDefaults($params);
        $this->view->form = $filtroForm;

    }
}