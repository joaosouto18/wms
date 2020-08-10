<?php


use Wms\Module\Web\Controller\Action;
use Wms\Module\Web\Page;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_Novo_ComparativoInventarioController  extends Action
{

    public function indexAction()
    {

        try {

            $params = $this->getRequest()->getParams();

            /** @var \Wms\Domain\Entity\InventarioNovoRepository $invRepo */
            $invRepo = $this->getEntityManager()->getRepository("wms:InventarioNovo");
            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

            $modeloExportacao = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");
            $idAcao = $this->getSystemParameterValue("ID_INTEGRACAO_COMPARATIVO_INVENTARIO_ERP");

            if (($modeloExportacao != 1) && ($modeloExportacao !=3) && ($modeloExportacao != 4)) {
                throw new \Exception("Modelo de exportação de inventário não compatível com esta funcionalidade");
            }
            if ($idAcao == "") {
                throw new \Exception("Para acessar este recurso é preciso que uma integração de comparação de inventário com ERP esteja configurada");
            }

            $acaoEn = $acaoIntRepo->find($idAcao);
            if ($acaoEn == null) {
                throw new \Exception("Integração de comparação de inventário com ERP não encontrada para o ID especificado no parametro");
            }
            if ($acaoEn->getTipoAcao()->getId() != \Wms\Domain\Entity\Integracao\AcaoIntegracao::INTEGRACAO_COMPARATIVO_INVENTARIO_ERP) {
                throw new \Exception("Integração de Comparativo de Inventário com ERP Mal configurada");
            }

            $filtroForm = new \Wms\Module\InventarioNovo\Form\ComparativoInventarioForm();
            if (isset($params['btnSubmit']) || isset($params['btnExport'])) {
                $idInventarioERP = $params['codInventario'];
                $inventarioInternoEn = $invRepo->findOneBy(array('codErp' => $idInventarioERP));
                if ($inventarioInternoEn == null) {
                    throw new \Exception('Nenhum inventário no WMS referenciado para o inventário do ERP código ' . $idInventarioERP );
                }

                /*
                 * MOCK PARA TESTES E DEMONSTRAÇÃO
                 $invERP = array();
                 $invERP[] = array('COD_PRODUTO' => '8', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'OVOS BRANCOS GRANDES "A" GRANEL CX 30 DZ');
                 $invERP[] = array('COD_PRODUTO' => '5917', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'FRANGO CONGELADO MAROMBI CX17KG	');
                 $invERP[] = array('COD_PRODUTO' => '570', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'ESPET GRAN BOVINO AURORA 40X100G CX 4KG	');
                 $invERP[] = array('COD_PRODUTO' => '1013', 'DSC_GRADE' => 'UNICA', 'DSC_PRODUTO' => 'Produto de Teste 1013');
                 */

                $options = array(0 => $idInventarioERP);
                $invERP = $acaoIntRepo->processaAcao($acaoEn,$options,'E','P',null, \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::CODIGO_ESPECIFICO);
                if (count($invERP) == 0) {
                    throw new \Exception("Inventário no ERP código " . $idInventarioERP . " não encontrado ou sem nenhum produto definido");
                }

                $filtroForm->init(true);
            }

            if (isset($params['btnSubmit'])) {

                $invWMS = $this->getServiceLocator()->getService("Inventario")->getResultadoInventarioComparativo($modeloExportacao,$idInventarioERP);
                $result = $this->getServiceLocator()->getService("Inventario")->comparataInventarioWMSxERP($invWMS, $invERP);

                if (count($result['apenas-wms']) >0) $this->addFlashMessage('info','Existem produtos que constam apenas no inventário do WMS');
                if (count($result['apenas-erp']) >0) $this->addFlashMessage('info','Existem produtos que constam apenas no inventário do ERP');

                $resultForm = new \Wms\Module\InventarioNovo\Form\ResultadoComparativoInventarioForm();
                $resultForm->init($result);
                $resultForm->setDefaultsGrid($result);
                $this->view->resultadoForm = $resultForm;
            } else if (isset($params['btnExport'])) {
                $idInventario = $inventarioInternoEn->getId();

                $modelo = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");
                if (empty($modelo))
                    throw new Exception("O modelo de exportação não foi definido! Por favor, defina em <b>Sistemas->Configurações->Inventário->Formato de Exportação do Inventário</b>");

                if ($modelo == 1) {
                    $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo1($idInventario, $invERP);
                } elseif ($modelo == 3){
                    $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo3($idInventario, $invERP);
                } elseif ($modelo == 4){
                    $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo4($idInventario, $invERP);
                }
                $this->addFlashMessage('success', "Inventário $idInventario exportado com sucesso");

                $this->redirect('index');
            }
        } catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
        }

        if (isset($filtroForm)) {
            $filtroForm->setDefaults($params);
            $this->view->form = $filtroForm;
        }

    }
}