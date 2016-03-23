<?php
use Wms\Controller\Action;

class Mobile_Enderecamento_ReabastecimentoManualController extends Action
{
    public function indexAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $qtd          = $this->_getParam('qtd');
        $codOS        = $this->_getParam('codOs');

        $form = new \Wms\Module\Mobile\Form\ReabastecimentoManual();
        $form->init();
        $form->populate(array('codOs' => $codOS));
        $this->view->form = $form;

        if (!$codigoBarras || !$qtd) {
            return false;
        }

        $coletorService = new \Wms\Service\Coletor;
        $codigoBarrasProduto = $coletorService->adequaCodigoBarras($codigoBarras);

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $info = $produtoRepo->getProdutoByCodBarras($codigoBarrasProduto);
        $produtoEn = $produtoRepo->find(array('id' => $codigoBarras, 'grade' => 'UNICA'));

        if ($info || $produtoEn) {

            if ($info) {
                $codProduto = $info[0]['idProduto'];
            } else {
                $codProduto = $codigoBarras;
            }

            /** @var \Wms\Domain\Entity\ReabastecimentoManualRepository $reabasteceRepo */
            $reabasteceRepo = $this->em->getRepository("wms:Enderecamento\ReabastecimentoManual");
            $reabastEnt     = $reabasteceRepo->findOneBy(array('os' => $codOS, 'codProduto' => $codProduto));
            if ($reabastEnt) {
                $reabastEnt->setQtd($qtd + $reabastEnt->getQtd());
                $this->em->persist($reabastEnt);
                $this->em->flush();
                $this->addFlashMessage('success', 'Etiqueta consultada com sucesso');
                $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$codOS);
            }

            $contagem = new \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual();
            $produtoEn = $this->_em->getReference('wms:Produto', array('id' => $codProduto,'grade' => 'UNICA'));
            $contagem->setProduto($produtoEn);
            $contagem->setCodProduto($codProduto);
            $contagem->setQtd($qtd);
            if (empty($codOS)) {
                /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemRepo */
                $ordemRepo = $this->em->getRepository("wms:OrdemServico");
                $osEntity = $ordemRepo->criarOs(array('atividade' => \Wms\Domain\Entity\Atividade::REABASTECIMENTO_MANUAL,
                    'observacao' => 'Reabastecimento Manual iniciado'
                ));
                $codOS = $osEntity->getId();
            } else {
                $osEntity = $this->_em->getReference('wms:OrdemServico', $codOS);
            }
            $contagem->setOs($osEntity);
            $this->em->persist($contagem);
            $this->em->flush();
            $this->addFlashMessage('success', 'Etiqueta consultada com sucesso');
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$codOS);
        }

    }

    public function fecharAction()
    {
        $codOS        = $this->_getParam('codOs');
        if ($codOS) {
            /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemRepo */
            $ordemRepo = $this->em->getRepository("wms:OrdemServico");
            $ordemRepo->finalizar($codOS, 'Reabastecimento Manual finalizado');
            $this->addFlashMessage('success', 'Ordem de serviço finalizada');
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/');
        }

    }

}

