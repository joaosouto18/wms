<?php
use Wms\Controller\Action,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Util\Coletor as ColetorUtil;

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

        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");

        $codigoBarras = $this->_getParam('codigoBarras');

        $this->view->exibe = false;
        if ($codigoBarras != NULL) {
            $this->view->exibe = true;
            $codigoBarras = ColetorUtil::adequaCodigoBarras($codigoBarras);

            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $info = $produtoRepo->getProdutoByCodBarras($codigoBarras);

            if ($info == NULL) {
                $this->addFlashMessage('error', 'Nenhum produto encontrado para o código de barras ' . $codigoBarras);
                $this->redirect("index",'consulta-produto');
            }
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            $capacidadePicking = $info[0]['capacidadePicking'];
            if ($capacidadePicking > 0) {
                $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($info[0]['idProduto'], $info[0]['grade'], $capacidadePicking);
                $capacidadePicking = implode(' + ', $vetSeparar);
            }
            $params = array();
            $params['idProduto'] = $this->view->codProduto = $info[0]['idProduto'];
            $this->view->linhaSeparacao = $info[0]['linhaSeparacao'];
            $params['grade'] = $this->view->grade = $info[0]['grade'];
            $this->view->codigoBarras = $info[0]['codigoBarras'];
            $this->view->capacidadePicking = $capacidadePicking;
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
            $endPulmoes = $estoqueRepo->getEstoqueAndVolumeByParams($params, null, true, "ORDER BY ESTQ.DTH_VALIDADE, C.COD_CARACTERISTICA_ENDERECO, DE.DSC_DEPOSITO_ENDERECO", false);

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo   = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoque');
            $reservas = $reservaEstoqueRepo->getResumoReservasNaoAtendidasByParams($params);

            foreach ($endPulmoes as $key => $pulmao) {
                $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($params['idProduto'], $params['grade'], $pulmao['QTD']);
                if(is_array($vetEstoque)) {
                    $qtdEstoque = implode(' + ', $vetEstoque);
                }else{
                    $qtdEstoque = $vetEstoque;
                }
                $endPulmoes[$key]['QTD'] = $qtdEstoque;
            }

            $totalEntrada = 0;
            $totalSaida = 0;

            foreach ($reservas as $key => $reserva) {
                if ($reserva['QTD_RESERVADA'] > 0) {
                    $totalEntrada = $totalEntrada + $reserva['QTD_RESERVADA'];
                } else {
                    $totalSaida = $totalSaida + $reserva['QTD_RESERVADA'];
                }

                $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($params['idProduto'], $params['grade'], $reserva['QTD_RESERVADA']);
                if(is_array($vetEstoque)) {
                    $qtdEstoque = implode(' + ', $vetEstoque);
                }else{
                    $qtdEstoque = $vetEstoque;
                }
                $reservas[$key]['QTD_RESERVADA'] = $qtdEstoque;
            }

            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($params['idProduto'], $params['grade'], $totalEntrada);
            if(is_array($vetEstoque)) {
                $qtdEstoque = implode(' + ', $vetEstoque);
            }else{
                $qtdEstoque = $vetEstoque;
            }
            $totalEntrada = $qtdEstoque;

            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($params['idProduto'], $params['grade'], $totalSaida);
            if(is_array($vetEstoque)) {
                $qtdEstoque = implode(' + ', $vetEstoque);
            }else{
                $qtdEstoque = $vetEstoque;
            }
            $totalSaida = $qtdEstoque;


            $this->view->reservas = $reservas;
            $this->view->pulmoes = $endPulmoes;
            $this->view->totalEntrada = $totalEntrada;
            $this->view->totalSaida = $totalSaida;

        }

    }

}

