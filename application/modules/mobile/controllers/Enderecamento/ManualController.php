<?php
use Wms\Controller\Action,
Wms\Service\Recebimento as LeituraColetor,
Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Mobile_Enderecamento_ManualController extends Action
{
    public function indexAction()
    {
        $recebimentoService = new \Mobile\Service\Recebimento($this->em);
        $this->view->recebimentos = $recebimentoService->listarRecebimentosNaoEnderecados(null);
    }

    public function lerCodigoBarrasAction()
    {
        $params = $this->_getAllParams();
        try{
            if (isset($params['submit'])&& $params['submit'] != null) {
                if (isset($params['produto']) && trim($params['produto']) == "") {
                    throw new \Exception("Informe um produto");
                }
                if (isset($params['endereco']) && trim($params['endereco']) == "") {
                    throw new \Exception("Informe um endereço");
                }
                if (isset($params['qtd']) && trim($params['qtd']) == "") {
                    throw new \Exception("Informe uma quantidade");
                }

                unset($params['module']);
                unset($params['controller']);
                unset($params['action']);
                unset($params['submit']);

                $this->validarEndereco($params['endereco'], $params, 'ler-codigo-barras', 'enderecar-manual');
            } else {
                $this->addFlashMessage('info', "Informe um produto, endereço e quantidade para endereçar");
            }
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
        }
    }

    public function validarEndereco($codBarraEndereco, $params, $urlOrigem, $urlDestino) {
        try{
            $LeituraColetor = new LeituraColetor();
            $endereco   = $LeituraColetor->retiraDigitoIdentificador($codBarraEndereco);

            if (!isset($endereco)) {
                throw new \Exception('Nenhum Endereço Informado');
            }

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
            $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($endereco);
            if (empty($idEndereco)) {
                throw new \Exception('Endereço não encontrado');
            }

            $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
            $params['endereco'] = $idEndereco;

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->find($idEndereco);

            if ($enderecoEn->getNivel() == '0') {
                $params['urlOrigem'] = $urlOrigem;
                $params['urlDestino'] = $urlDestino;
                $this->redirect('selecionar-nivel','enderecamento_manual','mobile', $params);
            }

            unset($params['urlDestino']);
            unset($params['urlOrigem']);
            $this->redirect($urlDestino,'enderecamento_manual','mobile', $params);

        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            $this->redirect($urlOrigem,'enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function selecionarNivelAction() {
        $params = $this->_getAllParams();

        $urlDestino = $params['urlDestino'];
        $urlOrigem = $params['urlOrigem'];

        try {

            $idEndereco = $params['endereco'];

            $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->find($idEndereco);
            $this->view->rua = $enderecoEn->getRua();
            $this->view->predio = $enderecoEn->getPredio();
            $this->view->apartamento = $enderecoEn->getApartamento();
            $this->view->endereco = $enderecoEn->getDescricao();

            if (isset($params['submit'])&& $params['submit'] != null) {

                if (trim($params['nivel']) != "") {
                    $tamanhoRua = $this->getSystemParameterValue('TAMANHO_CARACT_RUA');
                    $tamanhoPredio = $this->getSystemParameterValue('TAMANHO_CARACT_PREDIO');
                    $tamanhoNivel = $this->getSystemParameterValue('TAMANHO_CARACT_NIVEL');
                    $tamanhoApartamento = $this->getSystemParameterValue('TAMANHO_CARACT_APARTAMENTO');

                    $rua         = substr("000" . $enderecoEn->getRua(), -$tamanhoRua, $tamanhoRua);
                    $predio      = substr("000" . $enderecoEn->getPredio(), -$tamanhoPredio, $tamanhoPredio);
                    $nivel       = substr("000" . $params['nivel'], -$tamanhoNivel, $tamanhoNivel);
                    $apartamento = substr("000" . $enderecoEn->getApartamento(), -$tamanhoApartamento, $tamanhoApartamento);
                    $codBarras   = $rua . $predio . $nivel . $apartamento;

                    $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($codBarras);
                    if (count($idEndereco) == 0) {
                        throw  new \Exception("Nenhum Endereço Encontrado");
                    }

                    $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
                    $params['endereco'] = $idEndereco;

                    unset($params['module']);
                    unset($params['controller']);
                    unset($params['action']);
                    unset($params['submit']);
                    unset($params['urlDestino']);
                    unset($params['urlOrigem']);
                    unset($params['nivel']);

                    $this->redirect($urlDestino,'enderecamento_manual','mobile', $params);
                }
            }
            $this->addFlashMessage('info', "Informe um nível");

        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            $this->redirect($urlOrigem,'enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function enderecarManualAction(){
        $params = $this->_getAllParams();
        try {
            $produto = $params['produto'];
            $idEndereco = $params['endereco'];
            $idRecebimento = $params['id'];
            $qtd = $params['qtd'];

            $this->getEntityManager()->beginTransaction();

            $LeituraColetor = new LeituraColetor();
            $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');

            $paleteEn = $this->createPalete($qtd,$produto,$idRecebimento);
            $paleteRepo->alocaEnderecoPalete($paleteEn->getId(),$idEndereco);
            $paleteRepo->finalizar(array($paleteEn->getId()), $idPessoa);

            $this->addFlashMessage('success','Palete ' . $paleteEn->getId(). ' criado e endereçado com sucesso');
            $this->getEntityManager()->commit();
            $this->redirect('ler-codigo-barras','enderecamento_manual','mobile',array('id'=>$params['id']));

        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            $this->getEntityManager()->rollback();
            $this->redairect('ler-codigo-barras','enderecamento_manual','mobile',array('id'=>$params['id']));
        }
    }

    private function createPalete($qtd, $produto, $idRecebimento)
    {
        $LeituraColetor = new \Wms\Service\Coletor();

        $codigoBarrasProduto = $LeituraColetor->adequaCodigoBarras($produto);
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $info = $produtoRepo->getProdutoByCodBarras($codigoBarrasProduto);
        $produtoEn      = null;
        if ($info) {
            $produtoEn  = $produtoRepo->findOneBy(array('id'=>$info[0]['idProduto'], 'grade' =>'UNICA'));
        } else {
            $produtoEn  = $produtoRepo->findOneBy(array('id'=>$produto, 'grade' =>'UNICA'));
        }

        if (!isset($produtoEn)) {
            throw new \Exception('Produto não encontrado');
        }

        $idProduto = $produtoEn->getId();
        $result = $produtoRepo->getNormaPaletizacaoPadrao($idProduto, 'UNICA');
        $idNorma = $result['idNorma'];

        if ($idNorma == null) {
            throw  new \Exception("O Produto $produto não possui norma de paletização");
        }
        /** @var \Wms\Domain\Entity\Armazenagem\UnitizadorRepository $uniRepo */
        $uniRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador");
        $unitizadorEn  = $uniRepo->find($result['idUnitizador']);
        $statusEn      = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_RECEBIDO);

        $volumes = $produtoRepo->getEmbalagensOrVolumesByProduto($idProduto);

        if (count($volumes) == 0) {
            throw new \Exception('Produto não possui embalagens cadastradas');
        }

        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento")->find($idRecebimento);

        if (!isset($recebimentoEn)) {
            throw new \Exception('Recebimento não encontrado');
        }

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');

        $paleteEn = $paleteRepo->salvarPaleteEntity($produtoEn,$recebimentoEn,$unitizadorEn,$statusEn,$volumes, $idNorma, $qtd,null,'M');

        $idPalete = $paleteEn->getId();
        $this->_em->flush();
        $this->_em->clear();

        $paleteEn = $paleteRepo->find($idPalete);

        return $paleteEn;
    }


}

