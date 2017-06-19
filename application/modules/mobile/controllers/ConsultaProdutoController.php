<?php
use Wms\Controller\Action,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Domain\Entity\Expedicao;

class Mobile_ConsultaProdutoController extends Action
{
    public function indexAction()
    {
        $form = new PickingLeitura();
        $form->setControllerUrl("consulta-produto");
        $form->setActionUrl("index");
        $form->setLabel("Busca de Produto");
        $form->setLabelElement("Código de Barras ou Código do Produto");
        $form->init();
        $this->view->form = $form;

        $codigoBarras = $this->_getParam('codigoBarras');


        $this->view->exibe = false;
        if ($codigoBarras != NULL) {
            $this->view->exibe = true;
            $recebimentoService = new \Wms\Service\Recebimento;
            $codigoBarras = $recebimentoService->analisarCodigoBarras($codigoBarras);

            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $info = $produtoRepo->getProdutoByCodBarras($codigoBarras);

            if ($info == NULL) {
                $this->addFlashMessage('error', 'Nenhum produto encontrado para o código de barras ' . $codigoBarras);
                $this->redirect("index",'consulta-produto');
            }

            $params = array();
            $params['idProduto'] = $this->view->codProduto = $info[0]['idProduto'];
            $this->view->linhaSeparacao = $info[0]['linhaSeparacao'];
            $params['grade'] = $this->view->grade = $info[0]['grade'];
            $this->view->codigoBarras = $info[0]['codigoBarras'];
            $this->view->descricao = $info[0]['descricao'];
            $this->view->unitizador = $info[0]['unitizador'];
            $this->view->lastro = $info[0]['numLastro'];
            $this->view->camadas = $info[0]['numCamadas'];
            $this->view->norma = $info[0]['numNorma'];
            $this->view->peso = $info[0]['numPeso'];
            $this->view->picking = $info[0]['picking'];
            $this->view->diasVidaUtil = $info[0]['diasVidaUtil'];

            if ($info[0]['idEmbalagem'] != NULL) {
                $dscEmbalagem = $info[0]['descricaoEmbalagem'] . " (" . $info[0]['quantidadeEmbalagem'] . ")";
                $this->view->tipo = "Embalagem";
                $params['idVolume'] = 0;
                $idVolume = null;
            } else {
                $dscEmbalagem = $info[0]['descricaoVolume'];
                $this->view->tipo = "Vol. " . $info[0]['sequenciaVolume'].' de '.$info[0]['numVolumes'];
                $params['idVolume'] = $info[0]['idVolume'];
                $idVolume = $info[0]['idVolume'];
            }
            $this->view->embalagem = $dscEmbalagem;

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo   = $this->_em->getRepository('wms:Enderecamento\Estoque');
            $this->view->pulmoes = $estoqueRepo->getEstoqueAndVolumeByParams($params, null, true, "ORDER BY E.DTH_VALIDADE, C.COD_CARACTERISTICA_ENDERECO, DE.DSC_DEPOSITO_ENDERECO");

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo   = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoque');
            $this->view->reservas = $reservaEstoqueRepo->getResumoReservasNaoAtendidasByParams($params);


        }

    }

}

