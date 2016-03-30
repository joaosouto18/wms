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

        /** @var \Wms\Domain\Entity\ReabastecimentoManualRepository $reabasteceRepo */
        $reabasteceRepo = $this->em->getRepository("wms:Enderecamento\ReabastecimentoManual");

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

            $reabastEnt     = $reabasteceRepo->findOneBy(array('os' => $codOS, 'codProduto' => $codProduto));
            $this->somaConferenciaRepetida($reabastEnt,$qtd,$codOS);

            $contagem = new \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual();
            $produtoEn = $this->_em->getReference('wms:Produto', array('id' => $codProduto,'grade' => 'UNICA'));
            $contagem->setProduto($produtoEn);
            $contagem->setCodProduto($codProduto);
            $contagem->setQtd($qtd);
            $os = $this->getOs($codOS);
            $contagem->setOs($os['osEntity']);
            $this->em->persist($contagem);
            $this->em->flush();
            $this->addFlashMessage('success', 'Etiqueta consultada com sucesso');
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$os['codOs']);
        }

        $codigoBarrasEndereco = $coletorService->retiraDigitoIdentificador($codigoBarras);

        $idEndereco = null;
        if (count($codigoBarrasEndereco) >5) {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($codigoBarrasEndereco);
        }

        if ($idEndereco) {
            $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $result = $enderecoRepo->getProdutoByEndereco($codigoBarras,false);

            if (count($result) == 0)
            {
                $this->addFlashMessage('error', 'Nenhum produto encontrado para este picking');
                $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$codOS);
            }

            $reabastEnt     = $reabasteceRepo->findOneBy(array('os' => $codOS, 'depositoEndereco' => $idEndereco));
            $this->somaConferenciaRepetida($reabastEnt,$qtd,$codOS);

            $codProduto = $result[0]['codProduto'];
            $produtoEn = $this->_em->getReference('wms:Produto', array('id' => $codProduto,'grade' => 'UNICA'));

            $enderecoEn = $enderecoRepo->find($idEndereco);
            $os = $this->getOs($codOS);
            $contagem = new \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual();
            $contagem->setProduto($produtoEn);
            $contagem->setCodProduto($codProduto);
            $contagem->setOs($os['osEntity']);
            $contagem->setDepositoEndereco($enderecoEn);
            $contagem->setQtd($qtd);
            $this->em->persist($contagem);
            $this->em->flush();
            $this->addFlashMessage('success', 'Etiqueta consultada com sucesso');
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$os['codOs']);
        }

    }

    /**
     * @param $codOS
     * @return array
     * @throws Exception
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getOs($codOS)
    {
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
        return array(
            'osEntity' => $osEntity,
            'codOs' => $codOS
        );
    }

    protected function somaConferenciaRepetida($reabastEnt, $qtd, $codOS)
    {
        if ($reabastEnt && $qtd && $codOS) {
            $reabastEnt->setQtd($qtd + $reabastEnt->getQtd());
            $this->em->persist($reabastEnt);
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
            $this->addFlashMessage('success', 'Ordem de serviço: '.$codOS.' finalizada');
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/');
        }

    }

}

