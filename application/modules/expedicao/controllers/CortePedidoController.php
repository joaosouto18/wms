<?php
use Wms\Module\Web\Controller\Action,
    Wms\Service\Recebimento as LeituraColetor;

class Expedicao_CortePedidoController  extends Action
{

    /*
     * params
     * idExpedicao    int     Obrigatorio, id da Expedição que quer cortar os pedidos
     * idMapa         int     Opcional, id do Mapa que os pedidos serão cortados, caso seja corte de mapa
     * idProduto      string  Opcional, id do produto que será filtrado
     * grade          string  Opcional, grade do produto que será filtrado
     * clientes       array   Opcional, array contendo os códigos dos clientes que serão filtrados
     * pedidos        array   Opcional, array contendo os códigos dos pedidos que serão filtrados
     * pedidoCompleto boolean opcional, se for true será exibido todos os produtos do pedido, para false, apenas o produto filtrado caso haja filtro por produto
     */
    public function listAction()
    {
        $params = array();
        if ($this->_getParam('idExpedicao') != null) {
            $params['idExpedicao'] = $params['idExpedicao'];
        }
        if ($this->_getParam('idMapa') != null) {
            $params['idMapa'] = $params['idMapa'];
        }
        if ($this->_getParam('idProduto') != null) {
            $params['idProduto'] = $params['idProduto'];
        }
        if ($this->_getParam('grade') != null) {
            $params['grade'] = $params['grade'];
        }
        if ($this->_getParam('clientes') != null) {
            $params['clientes'] = $params['clientes'];
        }
        if ($this->_getParam('pedidos') != null) {
            $params['pedidos'] = $params['pedidos'];
        }
        if ($this->_getParam('pedidoCompleto') != null) {
            $params['pedidoCompleto'] = $params['pedidoCompleto'];
        }

        //Apenas para mock e teste
        $params = array(
            'idExpedicao' => 7,
            'pedidos'=>array(20009662,11022547),
            'pedidoCompleto'=>true
        );

        $pedidos = $this->getEntityManager()->getRepository('wms:Expedicao')->getPedidosParaCorteByParams($params);
        $this->view->pedidos = $pedidos;
        var_dump($pedidos);
    }

    public function cortarAjaxAction(){

    }
}