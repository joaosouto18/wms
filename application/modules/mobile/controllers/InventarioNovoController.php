<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 29/11/2018
 * Time: 15:44
 */

use Wms\Controller\Action;

class Mobile_InventarioNovoController extends Action
{
    public function listagemInventariosAction()
    {
        $this->view->desconsideraZeroEsquerda = true; ($this->getSystemParameterValue("DESCONSIDERA_ZERO_ESQUERDA") == "S");
        $this->view->usaGrade = ($this->getSystemParameterValue("UTILIZA_GRADE") == "S");
        $arrQtdDigitos = \Wms\Util\Endereco::getQtdDigitos();
        $mascara = \Wms\Util\Endereco::mascara($arrQtdDigitos,'0');
        $arrMasc = \Wms\Util\Endereco::separar($mascara, $arrQtdDigitos);
        $arrMasc["mask"] = $mascara;
        $this->view->endConfig = json_encode($arrMasc);
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
        $this->renderScript('inventario-novo' . DIRECTORY_SEPARATOR .'inventarios.phtml');
    }

    public function getInventariosAction()
    {
        try {
            $this->_helper->json(
                [
                "status" => "ok",
                "response" => $this->em->getRepository('wms:InventarioNovo')->getInventarios('stdClass', ['status' => \Wms\Domain\Entity\InventarioNovo::STATUS_LIBERADO], ["id" => "DESC"])
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function getContagensAction()
    {
        try{
            $id = $this->_getParam("id");
            $this->getServiceLocator()->getService("Inventario")->verificarRequisicaoColetor($id);

            $this->_helper->json(
                [
                "status" => "ok",
                    "response" => $this->em->getRepository('wms:InventarioNovo\InventarioContEnd')->getContagens($id)
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }

    public function getEnderecosAction()
    {
        try {
            $id = $this->_getParam("id");
            $this->getServiceLocator()->getService("Inventario")->verificarRequisicaoColetor($id);

            $this->_helper->json(
                [
                "status" => "ok",
                    "response" => $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->getArrEnderecos($id, $this->_getParam("sq"))
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }

    public function getInfoEnderecoAction()
    {
        try {
            /** @var \Wms\Service\InventarioService $inventarioSrvc */
            $inventarioSrvc = $this->getServiceLocator()->getService("Inventario");

            $id = $this->_getParam("id");
            $idEndereco = $this->_getParam("end");

            $inventarioSrvc->verificarRequisicaoColetor($id, $idEndereco);

            $this->_helper->json(
                [
                "status" => "ok",
                    "response" => $inventarioSrvc->getInfoEndereco(
                        $id,
                    $this->_getParam("sq"),
                    $this->_getParam("divrg"),
                        $idEndereco,
                    $this->_getParam("isPicking")
                    )
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }

    public function getInfoProdutoAction()
    {
        try {
            $codBarras = $this->_getParam("codbarras");
			$codbarrasAdequado = \Wms\Util\Coletor::adequaCodigoBarras($codBarras);
            $elemento = $this->_em->getRepository('wms:Produto')->getEmbalagemByCodBarras($codbarrasAdequado);

            if (empty($elemento))
                throw new Exception("Nenhuma embalagem/volume ativo foi encontrado com esse código de barras ". $codBarras);

            $id = $this->_getParam("id");
            $idEndereco = $this->_getParam("end");
            $this->getServiceLocator()->getService("Inventario")->verificarRequisicaoColetor($id, $idEndereco, $elemento[0]['idProduto'], $elemento[0]['grade']);

            $this->_helper->json(
                [
                    "status" => "ok",
                    "response" => [
                        "produto" => $elemento[0]
                    ]
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }

    public function contagemProdutoAction()
    {
        try {
            /** @var \Wms\Service\InventarioService $inventarioSrvc */
            $inventarioSrvc = $this->getServiceLocator()->getService("Inventario");

            $inventario = $this->_getParam("inventario");
            $contEnd = $this->_getParam("contEnd");
            $produto = $this->_getParam("produto");

            $inventarioSrvc->verificarRequisicaoColetor($inventario['id'], $contEnd['idEnd'], $produto['idProduto'], $produto['grade']);

            $inventarioSrvc->novaConferencia(
                $inventario,
                $contEnd,
                $produto,
                $this->_getParam("conferencia"),
                \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' => "Contagem efetuada com sucesso!"]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }

    public function confirmarProdutoZeradoAction()
    {
        try {

            /** @var \Wms\Service\InventarioService $inventarioSrvc */
            $inventarioSrvc = $this->getServiceLocator()->getService("Inventario");

            $inventario = $this->_getParam("inventario");
            $contEnd = $this->_getParam("contEnd");
            $produto = $this->_getParam("produto");

            $inventarioSrvc->verificarRequisicaoColetor($inventario['id'], $contEnd['idEnd'], $produto['idProduto'], $produto['grade']);

            $inventarioSrvc->confirmarProdutoZerado( $inventario, $contEnd, $produto, \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' =>  "Produto zerado com sucesso!"]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }

    public function finalizarContagemOsAction()
    {
        try {

            /** @var \Wms\Service\InventarioService $inventarioSrvc */
            $inventarioSrvc = $this->getServiceLocator()->getService("Inventario");

            $inventario = $this->_getParam("inventario");
            $contEnd = $this->_getParam("contEnd");

            $inventarioSrvc->verificarRequisicaoColetor($inventario['id'], $contEnd['idEnd']);

            $response = $inventarioSrvc->finalizarOs( $inventario, $contEnd, \Wms\Domain\Entity\OrdemServico::COLETOR);

            if ($response['code'] === 3) {
                throw new Exception($response['msg'], 4001);
            }

            $this->_helper->json(
                [
                    "status" => "ok",
                    'response' => $response
                ]
            );

        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage(), "errorCode" => $e->getCode()]);
        }
    }
}