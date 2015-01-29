<?php
use Wms\Module\Expedicao\Report\Produtos,
        Wms\Module\Expedicao\Report\ProdutosSemDadosLogisticos as ProdutosSemDados;

class Expedicao_Relatorio_ProdutosExpedicaoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $idExpedicao    = $this->getRequest()->getParam('id');
        $central        = $this->getRequest()->getParam('central');
        $cargas         = $this->getRequest()->getParam('cargas');
        $RelProdutos    = new Produtos("L","mm","A4");
        $modelo = $this->getSystemParameterValue("MODELO_RELATORIOS");

        $RelProdutos->imprimir($idExpedicao, $central, $cargas, null, $modelo);
    }

    public function semDadosAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $modelo = $this->getSystemParameterValue("MODELO_RELATORIOS");

        $Relatorio = new ProdutosSemDados();
        $Relatorio->imprimir($idExpedicao, $ExpedicaoRepo->getProdutosSemDadosByExpedicao($idExpedicao));
    }

}