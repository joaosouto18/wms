<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page,
    Core\Util\Produto as ProdutoUtil;

class Expedicao_RessuprimentoPreventivoController extends Action {

    public function indexAction() {
        $this->configurePage();
        $form = new \Wms\Module\Expedicao\Form\RessuprimentoPreventivo();
        $form->init();
        $data = $this->_getAllParams();
        $form->populate($data);
        $this->view->form = $form;
    }

    public function listAjaxAction() {
        $EstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $ProdutoRepository   = $this->_em->getRepository('wms:Produto');
        
        $params     = $this->_getAllParams();
        $enderecos = $EstoqueRepo->getEstoqueAndVolumeByParams($params);
        $codProduto = ProdutoUtil::formatar($params['idProduto']);
        $grade = (isset($params['grade']) && !empty($params['grade'])) ? $params['grade'] : 'UNICA';
        
        $produtoEn  = $ProdutoRepository->findOneBy(array('id' => $codProduto, 'grade' => $grade));
        $endPicking = $ProdutoRepository->getEnderecoPicking($produtoEn);

        $this->view->endPicking = $endPicking;
        $this->view->enderecos = $enderecos;
    }

    public function configurePage() {
        $buttons[] = array(
            'label' => 'Limpar Pulmão',
            'cssClass' => 'button limpar-pulmao',
            'urlParams' => array(
                'module' => 'expedicao',
                'controller' => 'ressuprimento-preventivo',
                'action' => 'index',
            ),
            'tag' => 'a'
        );
        $buttons[] = array(
            'label' => 'Completar Picking',
            'cssClass' => 'button completar-picking',
            'urlParams' => array(
                'module' => 'expedicao',
                'controller' => 'ressuprimento-preventivo',
                'action' => 'index',
            ),
            'tag' => 'a'
        );
        Page::configure(array('buttons' => $buttons));
    }

}
