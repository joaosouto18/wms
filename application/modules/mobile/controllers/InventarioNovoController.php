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
        $this->renderScript('inventario-novo\inventarios.phtml');
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
                    $this->_getParam("end")
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
            $this->_helper->json([
                    "status" => "ok",
                    "response" => [
                        "produto" => $this->_em->getRepository('wms:Produto')->getEmbalagemByCodBarras($this->_getParam("codbarras"))[0],
                        "usaGrade" => $this->getSystemParameterValue("UTILIZA_GRADE")
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
                $this->_getParam("contagem"),
                $this->_getParam("endereco"),
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
                $this->_getParam("endereco"),
                $this->_getParam("contagem"),
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
                $this->_getParam("endereco"),
                $this->_getParam("contagem"),
                \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' => $response]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }
}