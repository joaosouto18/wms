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
            $this->getServiceLocator()->getService("Inventario")->registrarContagem(
                $this->_getParam("inventario"),
                $this->_getParam("contagem"),
                $this->_getParam("produto"),
                $this->_getParam("conferencia"), \Wms\Domain\Entity\OrdemServico::COLETOR);

            $this->_helper->json(["status" => "ok", 'response' => "Contagem efetuada com sucesso!"]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }

    public function finalizarContagemOsAction()
    {
        try {

            $inventario = $this->_getParam("inventario");
            $contagem = $this->_getParam("contagem");

            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");
            $invServc->finalizarOs($inventario, $contagem);

            $this->_helper->json(["status" => "ok", 'response' => "Contagem efetuada com sucesso!"]);
        } catch (Exception $e) {
            $this->_helper->json(["status" => "error", 'exception' => $e->getMessage()]);
        }
    }
}