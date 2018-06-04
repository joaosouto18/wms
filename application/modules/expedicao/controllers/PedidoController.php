<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria,
    Wms\Module\Web\Grid\Expedicao\Pedido as PedidoGrid,
    \Wms\Domain\Entity\Integracao\AcaoIntegracao as AcaoIntegracao,

    Wms\Module\Web\Page;

class Expedicao_PedidoController  extends Action
{
    public function listAction()
    {
        $form = new FiltroExpedicaoMercadoria();
        $form->init("/expedicao/pedido/list");
        $this->view->form = $form;
        $params = $this->_getAllParams();

        $s1 = new Zend_Session_Namespace('sessionAction');
        $s1->setExpirationSeconds(900, 'action');
        $s1->action=$params;

        $s = new Zend_Session_Namespace('sessionUrl');
        $s->setExpirationSeconds(900, 'url');
        $s->url=$params;

        $dataI1 = new \DateTime;

        if ( !empty($params) ) {

            if ( !empty($params['idExpedicao']) || !empty($params['pedido']) || !empty($params['codCargaExterno'])  ){
                $idExpedicao=null;
                $idCarga=null;
                $pedido=null;

                if (!empty($params['idExpedicao']) )
                    $idExpedicao=$params['idExpedicao'];

                if (!empty($params['pedido']) )
                    $pedido=$params['pedido'];


                if (!empty($params['codCargaExterno']) )
                    $idCarga=$params['codCargaExterno'];

                $params=array();
                $params['idExpedicao']=$idExpedicao;
                $params['codCargaExterno']=$idCarga;
                $params['pedido']=$pedido;
            } else {
                if ( empty($params['dataInicial1']) ){
                    $params['dataInicial1']=$dataI1->format('d/m/Y');
                }
            }
            if ( !empty($params['control']) )
                $this->view->control = $params['control'];
            unset($params['control']);

        } else {
            $dataI1 = new \DateTime;
            $dataI2 = new \DateTime;
            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI2->format('d/m/Y')
            );
            unset($params['control']);
        }

        $form->populate($params);

        $Grid = new PedidoGrid();
        $this->view->grid = $Grid->init($params)
            ->render();

    }

    public function consultarAction(){
        $codPedido = $this->_getParam("COD_PEDIDO");

        $pedidoRepository = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
        $dadosPedido = $pedidoRepository->getDadosPedidoByCodPedido($codPedido);
        $codCliente = $dadosPedido[0]['COD_CLIENTE'];
        $cliente = $dadosPedido[0]['CLIENTE'];
        $codExpedicao = $dadosPedido[0]['COD_EXPEDICAO'];
        $codCarga = $dadosPedido[0]['COD_CARGA_EXTERNO'];
        $placa = $dadosPedido[0]['DSC_PLACA_EXPEDICAO'];
        $situacoExpedicao = $dadosPedido[0]['SITUACAO'];
        $qtdEtiquetas = $dadosPedido[0]['ETIQUETAS_GERADAS'];
        $qtdProdutos = $dadosPedido[0]['QTD_PRODUTOS'];
        $itinerario = $dadosPedido[0]['DSC_ITINERARIO'];
        $linhaEntrega = $dadosPedido[0]['DSC_LINHA_ENTREGA'];
        $rua = $dadosPedido[0]['RUA'];
        $numero = $dadosPedido[0]['NUMERO'];
        $complemento = $dadosPedido[0]['COMPLEMENTO'];
        $bairro = $dadosPedido[0]['NOM_BAIRRO'];
        $cidade = $dadosPedido[0]['CIDADE'];
        $uf = $dadosPedido[0]['UF'];
        $cep = $dadosPedido[0]['CEP'];
        $filialEstoque = $dadosPedido[0]['FILIAL_ESTOQUE'];
        $filialTransbordo = $dadosPedido[0]['FILIAL_TRANSBORDO'];


        $this->view->pesoTotal = $dadosPedido[0]['NUM_PESO'];
        $this->view->cubagemTotal = $dadosPedido[0]['NUM_CUBAGEM'];
        $this->view->codPedido = $codPedido;
        $this->view->codCliente = $codCliente;
        $this->view->cliente = $cliente;
        $this->view->codExpedicao = $codExpedicao;
        $this->view->codCarga = $codCarga;
        $this->view->placa = $placa;
        $this->view->situacaoExpedicao = $situacoExpedicao;
        $this->view->qtdEtiquetas = $qtdEtiquetas;
        $this->view->qtdProdutos = $qtdProdutos;
        $this->view->itinerario = $itinerario;
        $this->view->linhaEntrega = $linhaEntrega;
        $this->view->rua = $rua;
        $this->view->numero = $numero;
        $this->view->complemento = $complemento;
        $this->view->bairro = $bairro;
        $this->view->cidade = $cidade;
        $this->view->uf = $uf;
        $this->view->cep = $cep;
        $this->view->filialEstoque = $filialEstoque;
        $this->view->filialTransbordo = $filialTransbordo;
        $this->view->produtos = $pedidoRepository->getProdutosByPedido($codPedido);
        $etiquetas = $pedidoRepository->getEtiquetasByPedido($codPedido);
        $this->view->etiquetas = $etiquetas;
    }

}