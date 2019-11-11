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
            $this->_helper->json([
                "status" => "ok",
                "response" => $this->em->getRepository('wms:InventarioNovo')->getInventarios('stdClass', ['status' => \Wms\Domain\Entity\InventarioNovo::STATUS_LIBERADO], ["id" => "DESC"])
            ]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function getContagensAction()
    {
        try{
            $this->_helper->json([
                "status" => "ok",
                "response" => $this->em->getRepository('wms:InventarioNovo\InventarioContEnd')->getContagens($this->_getParam("id"))
            ]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function getEnderecosAction()
    {
        try {
            $this->_helper->json([
                "status" => "ok",
                "response" => $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->getArrEnderecos($this->_getParam("id"), $this->_getParam("sq"))
            ]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function getInfoEnderecoAction()
    {
        try {
            $this->_helper->json([
                "status" => "ok",
                "response" => $this->getServiceLocator()->getService("Inventario")->getInfoEndereco(
                    $this->_getParam("id"),
                    $this->_getParam("sq"),
                    $this->_getParam("divrg"),
                    $this->_getParam("end"),
                    $this->_getParam("isPicking")
                    )
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function getInfoProdutoAction()
    {
        try {
			$codbarras = \Wms\Util\Coletor::adequaCodigoBarras($this->_getParam("codbarras"));
            $elemento = $this->_em->getRepository('wms:Produto')->getEmbalagemByCodBarras($codbarras);

            if (empty($elemento))
                throw new Exception("Nenhuma embalagem/volume ativo foi encontrado com esse código de barras ". $this->_getParam("codbarras"));

            $this->_helper->json([
                    "status" => "ok",
                    "response" => [
                        "produto" => $elemento[0]
                    ]
                ]
            );
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function contagemProdutoAction()
    {
        try {
            $this->getServiceLocator()->getService("Inventario")->novaConferencia(
                $this->_getParam("inventario"),
                $this->_getParam("contEnd"),
                $this->_getParam("produto"),
                $this->_getParam("conferencia"),
                \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' => "Contagem efetuada com sucesso!"]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function confirmarProdutoZeradoAction()
    {
        try {
            $this->getServiceLocator()->getService("Inventario")->confirmarProdutoZerado(
                $this->_getParam("inventario"),
                $this->_getParam("contEnd"),
                $this->_getParam("produto"),
                \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' =>  "Produto zerado com sucesso!"]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function finalizarContagemOsAction()
    {
        try {

            $response = $this->getServiceLocator()->getService("Inventario")->finalizarOs(
                $this->_getParam("inventario"),
                $this->_getParam("contEnd"),
                \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' => $response]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }
}