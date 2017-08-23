<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Grid\RessuprimentoPreventivo as OsGrid;

class Expedicao_RessuprimentoPreventivoController extends Action {

    public function indexAction() {
        $form = new \Wms\Module\Expedicao\Form\RessuprimentoPreventivo();
        $form->init();
        $data = $this->_getAllParams();
        $form->populate($data);
        $this->view->form = $form;
    }

    public function pickingAjaxAction() {
        $params = $this->_getAllParams();
        $OndaRessupRep = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $dados = $OndaRessupRep->calculaRessuprimentoPreventivoByParams($params);
        $this->view->dados = $dados;
    }

    public function listAjaxAction() {
        $EstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $params = $this->_getAllParams();
        $enderecos = $EstoqueRepo->getEstoquePreventivoByParams($params);
        $this->view->enderecos = $enderecos;
    }

    public function confirmarAcaoAjaxAction() {
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
        $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $pedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
        $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        $reservaEstoqueOndaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueOnda");
        $repositorios = array(
            'produtoRepo' => $produtoRepo,
            'embalagemRepo' => $embalagemRepo,
            'reservaEstoqueExpRepo' => $reservaEstoqueExpedicaoRepo,
            'reservaEstoqueOndaRepo' => $reservaEstoqueOndaRepo,
            'reservaEstoqueRepo' => $reservaEstoqueRepo,
            'ondaRepo' => $ondaRepo,
            'pedidoRepo' => $pedidoRepo,
            'volumeRepo' => $volumeRepo,
            'enderecoRepo' => $enderecoRepo,
            'usuarioRepo' => $usuarioRepo,
            'expedicaoRepo' => $expedicaoRepo,
            'estoqueRepo' => $estoqueRepo,
            'osRepo' => $ordemServicoRepo,
            'siglaRepo' => $siglaRepo
        );


        $dados = json_decode($this->_getParam('dados'));
        $OndaRessupRep = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        foreach ($dados as $value) {

            $produtoEn = $produtoRepo->findOneBy(array('id' => $value->produto, 'grade' => $value->grade));
            $enderecoPulmaoEn = $enderecoRepo->findOneBy(array('descricao' => $value->pulmao));
            $qtdOnda = $value->qtdOnda;
            $validadeEstoque = $value->validadeEstoque;
            $idPicking = $value->idPicking;

            if ($value->tipo == 1) {
                $embalagem = json_decode($value->embalagens);
                if (is_array($embalagem)) {
                    foreach ($embalagem as $value) {
                        $embalagens[] = str_replace(array("'", '[', ']'), '', $value);
                    }
                } else {
                    $embalagens[] = str_replace(array("'", '[', ']'), '', $embalagem);
                }
                $volumes = null;
            } else {
                $volume = json_decode($value->volumes);
                if (is_array($volume)) {
                    foreach ($volume as $valueVol) {
                        $volumes[] = str_replace(array("'", '[', ']'), '', $valueVol);
                    }
                } else {
                    $volumes[] = str_replace(array("'", '[', ']'), '', $volume);
                }
                $embalagens = null;
            }
            $OndaRessupRep->saveOs($produtoEn, $embalagens, $volumes, $qtdOnda, 1000, $enderecoPulmaoEn, $idPicking, $repositorios, $validadeEstoque);
        }
//        $this->em->flush();
        die;
    }

}
