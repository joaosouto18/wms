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

        /** @var \Wms\Domain\Entity\Enderecamento\ReabastecimentoManualRepository $reabasteceRepo */
        $reabasteceRepo = $this->em->getRepository("wms:Enderecamento\ReabastecimentoManual");

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $info = $produtoRepo->getProdutoByCodBarras($codigoBarrasProduto);
        $produtoEn = $produtoRepo->find(array('id' => $codigoBarras, 'grade' => 'UNICA'));


        $config = \Zend_Registry::get('config');
        $consultaPreco = false;
        if (isset($config->database,$config->database->viewErp,$config->database->viewErp->habilitado))
            $consultaPreco = true;

        if ($info || $produtoEn) {

            if ($info) {
                $codProduto = $info[0]['idProduto'];
            } else {
                $codProduto = $codigoBarras;
            }

            $preco = null;
            if ($consultaPreco)
                $preco = $this->getPrecoView($codProduto);

            /** @var \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual $reabastEnt */
            $reabastEnt = $reabasteceRepo->findOneBy(array('os' => $codOS, 'codProduto' => $codProduto));

            $os = $this->getOs($codOS);
            $codOS = $os['codOS'];
            $this->somaConferenciaRepetida($reabastEnt,$qtd,$codOS, $preco);

            $contagem = new \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual();
            $produtoEn = $this->_em->getReference('wms:Produto', array('id' => $codProduto,'grade' => 'UNICA'));
            $contagem->setProduto($produtoEn);
            $contagem->setCodProduto($codProduto);
            $contagem->setQtd($qtd);
            $contagem->setOs($os['osEntity']);
            $this->em->persist($contagem);
            $this->em->flush();

            if (!empty($preco)) {
                $this->addFlashMessage('success', 'Etiqueta consultada com sucesso. OS:' . $codOS . ' Preço:' . $preco);
            } else {
                $this->addFlashMessage('success', "A quantidade $qtd foi adicionada à OS de reabastecimento $codOS para o produto $codProduto");
            }
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$codOS);
        }

        $codigoBarrasEndereco = $coletorService->retiraDigitoIdentificador($codigoBarras);

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
        $endereco = \Wms\Util\Endereco::formatar($codigoBarrasEndereco);

        $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
        if (empty($enderecoEn)) {
            throw new Exception("Endereço não encontrado");
        }

        try {

            $result = $enderecoRepo->getProdutoByEndereco($endereco,false);

            if (count($result) == 0)
            {
                throw new Exception('error', 'Nenhum produto encontrado para este endereço');
            }

            $reabastEnt = $reabasteceRepo->findOneBy(array('os' => $codOS, 'depositoEndereco' => $enderecoEn));

            $codProduto = $result[0]['codProduto'];
            $produtoEn = $this->_em->getReference('wms:Produto', array('id' => $codProduto,'grade' => 'UNICA'));

            $preco = null;
            if ($consultaPreco)
                $preco = $this->getPrecoView($codProduto);

            $os = $this->getOs($codOS);
            $codOS = $os['codOS'];
            $this->somaConferenciaRepetida($reabastEnt,$qtd,$codOS, $preco);

            $contagem = new \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual();
            $contagem->setProduto($produtoEn);
            $contagem->setCodProduto($codProduto);
            $contagem->setOs($os['osEntity']);
            $contagem->setDepositoEndereco($enderecoEn);
            $contagem->setQtd($qtd);
            $this->em->persist($contagem);
            $this->em->flush();

            if (!empty($preco)) {
                $this->addFlashMessage('success', 'Consulta realizada com sucesso.Preço:' . $preco);
            } else {
                $this->addFlashMessage('success', "A quantidade $qtd foi adicionada à OS de reabastecimento $codOS para o produto $codProduto");
            }
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$codOS);
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/codOs/'.$codOS);
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
            'codOS' => $codOS
        );
    }

    /**
     * @param $reabastEnt \Wms\Domain\Entity\Enderecamento\ReabastecimentoManual
     * @param $qtd
     * @param $codOS
     * @param $preco
     */
    protected function somaConferenciaRepetida($reabastEnt, $qtd, $codOS, $preco = null)
    {
        if ($reabastEnt && $qtd && $codOS) {
            $reabastEnt->setQtd($qtd + $reabastEnt->getQtd());
            $this->em->persist($reabastEnt);
            $this->em->flush();
            if (!empty($preco)) {
                $this->addFlashMessage('success', 'Etiqueta consultada com sucesso. OS:' . $codOS . ' Preço:' . $preco);
            } else {
                $codProduto = $reabastEnt->getCodProduto();
                $this->addFlashMessage('success', "A quantidade $qtd foi adicionada à OS de reabastecimento $codOS para o produto $codProduto");
            }
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
        $this->addFlashMessage('error', 'Não foi identicada a OS');
        $this->_redirect('/mobile/enderecamento_reabastecimento-manual/index/');
    }

    /**
     * @param $codProduto
     * @return string
     */
    private function getPrecoView($codProduto)
    {
        $config = \Zend_Registry::get('config');
        $viewErp = false;
        if (isset($config->database,$config->database->viewErp,$config->database->viewErp->habilitado))
            $viewErp = $config->database->viewErp->habilitado;
        $preco = 'Não disponível';
        if ($viewErp) {
            $conexao = \Wms\Domain\EntityRepository::conexaoViewERP();
            $query = "select PRECO from FN_GET_PROD_IMPERIUM where CODPROD = $codProduto";
            $precoResult = \Wms\Domain\EntityRepository::nativeQuery($query, 'all', $conexao);
            if (!empty($precoResult)) {
                $preco = $precoResult[0]['PRECO'];
            }
        }
        return $preco;
    }

}

