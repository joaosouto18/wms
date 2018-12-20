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
                "response" => $this->em->getRepository('wms:InventarioNovo')->getInventarios('stdClass', ['status' => \Wms\Domain\Entity\InventarioNovo::STATUS_LIBERADO])
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
                "response" => $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->getInfoEndereco(
                    $this->_getParam("id"),
                    $this->_getParam("sq"),
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
            $params = $this->getRequest()->getParams();
            $this->_helper->json(["status" => "ok", 'response' => $params]);
            $inventario = $this->_getParam("inventario");
            $contagem = $this->_getParam("contagem");
            $endereco = $this->_getParam("endereco");
            $produto = $this->_getParam("produto");
            $conferencia = $this->_getParam("conferencia");



        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }
}