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
        if ($this->_getParam('COD_MAPA_SEPARACAO')!= null) {
            $idMapa = $this->_getParam('COD_MAPA_SEPARACAO');
            $params['idMapa'] = $idMapa;
            $this->view->idMapa = $idMapa;
        }
        if ($this->_getParam('COD_PRODUTO') != null) {
            $idProduto = $this->_getParam('COD_PRODUTO');
            $params['idProduto'] = $idProduto;
            $this->view->idProduto = $idProduto;
        }
        if ($this->_getParam('DSC_GRADE') != null) {
            $grade = $this->_getParam('DSC_GRADE');
            $this->view->grade = $grade;
            $params['grade'] = $grade;
        }
        if ($this->_getParam('COD_EXPEDICAO') != null) {
            $params['idExpedicao'] = $this->_getParam('COD_EXPEDICAO');
        }
        if ($this->_getParam('clientes') != null) {
            $params['clientes'] = $this->_getParam('clientes');
        }

        if ($this->_getParam('pedidos') != null) {
            $params['pedidos'] = $this->_getParam('id');
        }

        $pedidoCompleto = true;
        if ($this->_getParam('pedidoCompleto') != null) {
            $pedidoCompleto = $this->_getParam('pedidoCompleto');
            if ($pedidoCompleto == 'N') {
                $pedidoCompleto = false;
            }
        }
        $this->view->pedidoCompleto = $pedidoCompleto;
        $this->view->idExpedicao = $this->_getParam('COD_EXPEDICAO');

        //'pedidos'=>array(20009662,11022547)
        $pedidos = $this->getEntityManager()->getRepository('wms:Expedicao')->getPedidosParaCorteByParams($params);
        $this->view->pedidos = $pedidos;
    }

    public function cortarPedidoAction()
    {
        $this->view->pedido = $pedido = $this->_getParam('id',0);

        $senha    = $this->_getParam('senha');
        $motivo   = $this->_getParam('motivoCorte');

        if (isset($senha) && !empty($senha) && isset($motivo) && !empty($motivo)) {

            try {
                $this->getEntityManager()->beginTransaction();

                if ($senha != $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA'))
                    throw new \Exception("Senha Informada Inválida");

                /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
                $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
                $pedidoProdutos = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto')
                    ->findBy(array('codPedido' => $pedido));

                if (!isset($pedidoProdutos) || empty($pedidoProdutos))
                    throw new \Exception("Produtos nao encontrados para o Pedido $pedido");

                foreach ($pedidoProdutos as $produto) {
                    $expedicaoRepo->cortaPedido($pedido, $produto->getCodProduto(), $produto->getGrade(), $produto->getQuantidade(), $this->_getParam('motivoCorte',null));
                }

                $this->getEntityManager()->commit();
                $this->addFlashMessage('success','Pedido '.$pedido.' Cortado com Sucesso');
                $this->_redirect('/expedicao');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();
                return $e->getMessage();
            }
        }
    }

    //exemplo: $qtdCorte['codPedido']['codProduto']['grade'];
    public function cortarAjaxAction(){
        $qtdCorte = $this->_getParam('qtdCorte');
        $motivo   = $this->_getParam('motivoCorte');
        $senha    = $this->_getParam('senha');
        if ($senha != $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA')) {
            $this->addFlashMessage('error','Senha Informada Inválida');
            $this->_redirect('/expedicao/os/index/id/'.$this->_getParam('idExpedicao'));
        }

        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
            $expedicaoRepo->executaCortePedido($qtdCorte,$motivo);
            $this->getEntityManager()->commit();
            $this->addFlashMessage('success','Pedidos Cortados com Sucesso');
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error',$e->getMessage());
        }

        $this->_redirect('/expedicao/os/index/id/'.$this->_getParam('idExpedicao'));
    }
}