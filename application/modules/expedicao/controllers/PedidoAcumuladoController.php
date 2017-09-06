<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Grid\RessuprimentoPreventivo as OsGrid;

class Expedicao_PedidoAcumuladoController extends Action {

    public function indexAction() {
        $form = new \Wms\Module\Expedicao\Form\PedidoAcumulado();
        $form->init();
        $data = $this->_getAllParams();
        $form->populate($data);
        $this->view->form = $form;
    }

    public function pickingAjaxAction() {
        $params = $this->_getAllParams();
        $OndaRessupRep = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $dados = $OndaRessupRep->calculaProdutoAcumuladoByParams($params);
        $this->view->dados = $dados;
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

        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioEn = $this->getEntityManager()->getRepository("wms:Usuario")->find($idUsuario);
        $ondaEn = new \Wms\Domain\Entity\Ressuprimento\OndaRessuprimento();
        $ondaEn->setDataCriacao(new \DateTime());
        $ondaEn->setDscObservacao("");
        $ondaEn->setUsuario($usuarioEn);
        $ondaEn->setTipoOnda("P");
        $this->getEntityManager()->persist($ondaEn);

        foreach ($dados as $value) {
            $produtoEn = $produtoRepo->findOneBy(array('id' => $value->produto, 'grade' => $value->grade));
            foreach (json_decode($value->pulmao) as $pulmao) {
                $enderecoPulmaoEn = $enderecoRepo->findOneBy(array('descricao' => $pulmao));
                $qtdOnda = json_decode($value->qtdOnda);
                $validadeEstoque = $value->validadeEstoque;
                $idPicking = $value->idPicking;
                $embalagens = array();
                $volumes = array();
                if ($value->tipo == 1) {
                    $embalagens = json_decode($value->embalagens);
                    $embalagens = $embalagens->$pulmao;
                    $volumes = null;
                } else {
                    $volumes = json_decode($value->volumes);
                    $volumes = $volumes->$pulmao;
                    $embalagens = null;
                }
                $OndaRessupRep->saveOs($produtoEn, $embalagens, $volumes[0], $qtdOnda->$pulmao, $ondaEn, $enderecoPulmaoEn, $idPicking, $repositorios, $validadeEstoque);
            }
        }
        $this->em->flush();
        $OndaRessupRep->sequenciaOndasOs();
        $this->_helper->json(array('success' => 'Onda Gerada'));
        die;
    }

}
