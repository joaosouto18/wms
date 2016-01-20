<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao as ExpedicaoGrid,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria,
    Wms\Module\Expedicao\Report\Produtos;

class Expedicao_Relatorio_ProdutosController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        ini_set('max_execution_time', 3000);
        $linhaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Armazenagem\LinhaSeparacao');
        $filialRepo = $this->getEntityManager()->getRepository('wms:Filial');

        $form = new FiltroExpedicaoMercadoria;
        $this->view->form = $form;
        $this->view->filiais = $filialRepo->getIdExternoValue();
        $this->view->linhaSeparacao = $linhaSeparacaoRepo->getIdValue();
        $params = $form->getParams();
        if (!$params) {
            $dataI1 = new \DateTime;
            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI1->format('d/m/Y')
            );
            $form->populate($params);
        }
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $sessao = new \Zend_Session_Namespace('deposito');
        $params['centrais'] = $sessao->centraisPermitidas;

        $expedicoes = $expedicaoRepo->buscar($params);
        $this->view->expedicoes = $expedicoes;
    }

    public function imprimirAction()
    {
        $expedicoes = $this->_getParam("expedicao");
        $filial = $this->_getParam("filial");
        $linhaSeparacao = $this->_getParam("linhaSeparacao");
        if ($linhaSeparacao== "") $linhaSeparacao = NULL;

        if (count($expedicoes) == 0) {
            $this->addFlashMessage('error','Selecione uma expedição');
            $this->redirect("index","relatorio_produto",'expedicao');
        }

        $strExp = "";
        foreach ($expedicoes as $expedicaoId){
            $strExp = $strExp . $expedicaoId;
            if ($expedicaoId !=end($expedicoes)) $strExp = $strExp . ',';
        }

        $modelo = $this->getSystemParameterValue("MODELO_RELATORIOS");

        $RelProdutos = new Produtos("L","mm","A4");
        $RelProdutos->imprimir($strExp, $filial,NULL,$linhaSeparacao, $modelo);

    }
}