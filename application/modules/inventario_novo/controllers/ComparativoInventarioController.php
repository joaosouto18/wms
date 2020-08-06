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
                    throw new \Exception('Nenhum inventário no WMS referenciado para o inventário do ERP código ' . $idInventarioERP );
                }
            }

            if (isset($params['btnSubmit'])) {
                $idInventario = $params['codInventario'];
                $invWMS = $this->getServiceLocator()->getService("Inventario")->getResultadoInventarioComparativo($modeloExportacao,$idInventario);

                $invERP = array();
                $invERP[] = array('COD_PRODUTO' => '8', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'OVOS BRANCOS GRANDES "A" GRANEL CX 30 DZ');
                $invERP[] = array('COD_PRODUTO' => '5917', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'FRANGO CONGELADO MAROMBI CX17KG	');
                $invERP[] = array('COD_PRODUTO' => '570', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'ESPET GRAN BOVINO AURORA 40X100G CX 4KG	');
                $invERP[] = array('COD_PRODUTO' => '1013', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1013');

                if (count($invERP) == 0) {
                    throw new \Exception("Inventário no ERP código " . $idInventario . " não encontrado ou sem nenhum produto definido");
                }
                $filtroForm->init(true);

                $result = $this->getServiceLocator()->getService("Inventario")->comparataInventarioWMSxERP($invWMS, $invERP);

                if (count($result['apenas-wms']) >0) $this->addFlashMessage('info','Existem produtos que constam apenas no inventário do WMS');
                if (count($result['apenas-erp']) >0) $this->addFlashMessage('info','Existem produtos que constam apenas no inventário do ERP');

                $resultForm = new \Wms\Module\InventarioNovo\Form\ResultadoComparativoInventarioForm();
                $resultForm->init($result);
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