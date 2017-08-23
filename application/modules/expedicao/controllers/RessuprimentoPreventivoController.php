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
        $dados = json_decode($this->_getParam('dados'));
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
        foreach ($dados as $value) {
            
            $produtoEn = $produtoRepo->findOneBy(array('id'=>$value->produto, 'grade'=>$value->grade));
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
            var_dump($qtdOnda);
//        $this->saveOs($produtoEn,$embalagens,$volumes,$qtdOnda,$ondaEn,$enderecoPulmaoEn,$idPicking,$repositorios,$validadeEstoque);
        }
        die;
    }

}
