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
        $this->view->em = $this->getEntityManager();
        $params = array();
        if ($this->_getParam('idMapa') != null) {
            $idMapa = $params['idMapa'];
            $params['idMapa'] = $idMapa;
            $this->view->idMapa = $idMapa;
        }
        if ($this->_getParam('idProduto') != null) {
            $idProduto = $params['idProduto'];
            $params['idProduto'] = $idProduto;
            $this->view->idProduto = $idProduto;
        }
        if ($this->_getParam('grade') != null) {
            $grade = $params['grade'];
            $this->view->grade = $grade;
            $params['grade'] = $grade;
        }
        if ($this->_getParam('idExpedicao') != null) {
            $params['idExpedicao'] = $params['idExpedicao'];
        }
        if ($this->_getParam('clientes') != null) {
            $params['clientes'] = $params['clientes'];
        }
        if ($this->_getParam('pedidos') != null) {
            $params['pedidos'] = $params['pedidos'];
        }

        $this->view->idProduto = "8680";
        $this->view->grade = "UNICA";

        $pedidoCompleto = true;
        if ($this->_getParam('pedidoCompleto') != null) {
            $pedidoCompleto = $params['pedidoCompleto'];
        }
        $this->view->pedidoCompleto = $pedidoCompleto;
        //Apenas para mock e teste
        $params = array(
            'idProduto' => "8680",
            'grade' => "UNICA",
        );
        //'pedidos'=>array(20009662,11022547)
        $pedidos = $this->getEntityManager()->getRepository('wms:Expedicao')->getPedidosParaCorteByParams($params);
        $this->view->pedidos = $pedidos;
    }

    public function cortarAjaxAction(){
        $qtdCorte = $this->_getParam('qtdCorte');
        $motivo   = $this->_getParam('motivoCorte');
        $senha    = $this->_getParam('senha');
        //exemplo: $qtdCorte['codPedido']['codProduto']['grade'];

    }
}