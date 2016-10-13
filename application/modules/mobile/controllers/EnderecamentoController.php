<?php
use Wms\Controller\Action,
    Wms\Domain\Entity\Enderecamento\Palete as Palete,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Domain\Entity\Enderecamento\Estoque;


class Mobile_EnderecamentoController extends Action
{

    public function leituraPickingAction()
    {
        $form = new PickingLeitura();
        $form->init();
        $this->view->form = $form;
        $codigoBarrasEndereco = $this->_getParam('codigoBarras');
        $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
        $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');

        if ($codigoBarrasEndereco) {
            $LeituraColetor = new \Wms\Service\Coletor();
            $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarrasEndereco);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $Endereco = $enderecoRepo->getEnderecoIdByDescricao($codigoBarras);

            if (count($Endereco) == 0) {
                $this->addFlashMessage('error','Endereço não encontrado');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $caracteristicaEndereco =  $Endereco[0]['COD_CARACTERISTICA_ENDERECO'];

            if ($caracteristicaEndereco != $idCaracteristicaPicking && $caracteristicaEndereco != $idCaracteristicaPickingRotativo)
            {
                $this->addFlashMessage('error','Código bipado não é um endereço de picking');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $idEndereco = $Endereco[0]['COD_DEPOSITO_ENDERECO'];
            $enderecoEn = $enderecoRepo->find($idEndereco);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $result = $enderecoRepo->getProdutoByEndereco($Endereco[0]['DSC_DEPOSITO_ENDERECO'],false);

            if (count($result) == 0)
            {
                $this->addFlashMessage('error', 'Nenhum produto encontrado para este picking');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $existeEstoque = false;

            foreach($result as $estoque)
            {
                $codProduto = $estoque['codProduto'];
                $grade      = $estoque['grade'];

                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
                $resultado = $estoqueRepo->getExisteEnderecoPulmao($codProduto, $grade);

                if ($resultado == true){
                    $existeEstoque = true;
                    break;
                }
            }

            if ($existeEstoque == false)
            {
                $this->addFlashMessage('error', 'O produto não possui endereço de estoque no pulmão');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $contagem = $this->em->getRepository("wms:Enderecamento\RelatorioPicking")->findBy(array('depositoEndereco'=>$enderecoEn));

            if ($contagem != NULL)
            {
                $this->addFlashMessage('error', 'O endereço informado já foi bipado');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            else
            {
                $atividadeEntity = $this->em->getReference('wms:Atividade', \Wms\Domain\Entity\Atividade::RESSUPRIMENTO);
                $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
                $pessoaEntity = $this->em->getReference('wms:Pessoa', $idPessoa);

                $os = new OrdemServicoEntity();
                $os->setDataInicial(new DateTime());
                $os->setAtividade($atividadeEntity);
                $os->setDscObservacao('Ressuprimento Manual Preventivo');
                $os->setPessoa($pessoaEntity);
                $os->setFormaConferencia('C');
                $this->em->persist($os);

                $contagem = new \Wms\Domain\Entity\Enderecamento\RelatorioPicking();
                $contagem->setDepositoEndereco($enderecoEn);
                $contagem->setCodProduto($codProduto);
                $contagem->setGrade($grade);
                $contagem->setOs($os);
                $this->em->persist($contagem);

                $this->em->flush();
            }

            $this->addFlashMessage('success', "O endereço ". substr($codigoBarrasEndereco,0,-1) ." foi adicionado");
        }
    }

    public function listarPickingAction()
    {
        $relatorioRepo = $this->em->getRepository("wms:Enderecamento\RelatorioPicking");

        $removerId = $this->_getParam('remover');
        if ($removerId) {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $removerId));
            $relatorioEn = $relatorioRepo->findOneBy(array('depositoEndereco' => $enderecoEn));
            if (!empty($relatorioEn)) {
                $this->getEntityManager()->remove($relatorioEn);
                $this->getEntityManager()->flush();
                $this->addFlashMessage('info', "O endereço $removerId foi removido.");
            }
            $this->_redirect('/mobile/enderecamento/listar-picking');
        }

        $enderecosSelecionados = $relatorioRepo->getSelecionados();
        $this->view->pickings = $enderecosSelecionados;

    }

    public function lerCodigoBarrasAction()
    {
        $layout = \Zend_Layout::getMvcInstance();
        $layout->setLayout('leitura');
    }

    public function buscarAction()
    {
        $idPalete = $this->_getParam("uma");

        if (!isset($idPalete)) {
            $this->createXml('error','Nenhum Palete Informado');
        }

        $LeituraColetor = new \Wms\Service\Coletor();
        $idPalete = $LeituraColetor->retiraDigitoIdentificador($idPalete);

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
        $paleteEn = $paleteRepo->find($idPalete);

        if ($paleteEn == NULL) {
            $this->createXml('error','Palete não encontrado');
        }
        if ($paleteEn->getCodStatus() == Palete::STATUS_CANCELADO) {
            $this->createXml('error','Palete cancelado');
        }

        $this->validarEndereco($paleteEn, $LeituraColetor, $paleteRepo);
    }

    public function validarEndereco($paleteEn, $LeituraColetor, $paleteRepo)
    {
        $endereco   = $LeituraColetor->retiraDigitoIdentificador($this->_getParam("endereco"));
        $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
        $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');


        if (!isset($endereco)) {
            $this->createXml('error','Nenhum Endereço Informado');
        }
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
        $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($endereco);
        if (empty($idEndereco)) {
            $this->createXml('error','Endereço não encontrado');
        }
        $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $enderecoRepo->find($idEndereco);

        if ($enderecoEn->getIdCaracteristica() == $idCaracteristicaPicking || $enderecoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
            $elementos = array();
            $elementos[] = array('name' => 'nivelzero', 'value' => true);
            $elementos[] = array('name' => 'rua', 'value' => $enderecoEn->getRua());
            $elementos[] = array('name' => 'predio', 'value' => $enderecoEn->getPredio());
            $elementos[] = array('name' => 'apartamento', 'value' => $enderecoEn->getApartamento());
            $elementos[] = array('name' => 'uma', 'value' => $paleteEn->getId());
            $this->createXml('info','Escolha um nível',null, $elementos);
        }

        $this->validaEnderecoPicking($enderecoEn->getDescricao(), $paleteEn, $enderecoEn->getIdCaracteristica(), $enderecoEn);

        if ($enderecoEn->getIdEstruturaArmazenagem() == Wms\Domain\Entity\Armazenagem\Estrutura\Tipo::BLOCADO) {
            $paleteRepo->alocaEnderecoPaleteByBlocado($paleteEn->getId(), $idEndereco);
        } else {
            $enderecoReservado = $paleteEn->getDepositoEndereco();

            if (($enderecoReservado == NULL) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
                $this->enderecar($enderecoEn,$paleteEn,$enderecoRepo, $paleteRepo);
            } else {
                $this->createXml('info','Confirmar novo endereço','/mobile/enderecamento/confirmar-novo-endereco/uma/' . $paleteEn->getId() . '/endereco/' . $idEndereco);
            }
        }
    }

    public function validaNivelAction()
    {
        $tamanhoRua = $this->getSystemParameterValue('TAMANHO_CARACT_RUA');
        $tamanhoPredio = $this->getSystemParameterValue('TAMANHO_CARACT_PREDIO');
        $tamanhoNivel = $this->getSystemParameterValue('TAMANHO_CARACT_NIVEL');
        $tamanhoApartamento = $this->getSystemParameterValue('TAMANHO_CARACT_APARTAMENTO');

        $nivel = $this->_getParam("nivel");
        if (strlen($this->_getParam("nivel")) < 2) {
            $nivel = '0'.$this->_getParam("nivel");
        }
        $capacidadePicking  = $this->_getParam('capacidadePicking');
        $idPalete           = $this->_getParam("uma");
        $rua                = substr($this->_getParam("rua"), -$tamanhoRua, $tamanhoRua);
        $predio             = substr($this->_getParam("predio"), -$tamanhoPredio, $tamanhoPredio);
        $nivel              = substr($nivel, -$tamanhoNivel, $tamanhoNivel);
        $apartamento        = substr($this->_getParam("apartamento"), -$tamanhoApartamento, $tamanhoApartamento);
        $codBarras          = $rua . $predio . $nivel . $apartamento;

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

        $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($codBarras);
        if (count($idEndereco) == 0) {
            $this->createXml('error','Endereço não encontrado');
        }
        $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $enderecoRepo->find($idEndereco);

        $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        $enderecoReservado = null;
        if (isset($paleteEn) && !empty($paleteEn)) {
            $this->validaEnderecoPicking($codBarras, $paleteEn, $enderecoEn->getIdCaracteristica(), $enderecoEn, $capacidadePicking);
            $enderecoReservado = $paleteEn->getDepositoEndereco();
        }

        if (($enderecoReservado == null) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
            $this->enderecar($enderecoEn,$paleteEn,$enderecoRepo, $paleteRepo);
        } else {
            $this->createXml('info','Confirmar novo endereço','/mobile/enderecamento/confirmar-novo-endereco/uma/' . $idPalete . '/endereco/' . $idEndereco);
        }

    }

    /**
     *  Verifica se o endereço passado é o endereço de picking do produto
     * @param $codBarras
     * @return int
     */
    public function validaEnderecoPicking($endereco, $paleteEn, $caracteristicaEnd, $enderecoEn = null, $capacidadePicking = 0)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
        $paleteProdutoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\PaleteProduto');
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');

        $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
        $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');

        //Se for picking do produto entao o nivel poderá ser escolhido
        //@TODO Validar se existe Picking Rotativo cadastrado para o produto.
        //Se sim, o sistema deverá exibir o endereço e só permitir armazenar no endereço cadastrado e permitir alterar a capacidade do picking (apenas para picking Dinâmico);
        //Se o produto não possuir endereço de Picking Dinâmico cadastrado, o sistema deverá solicitar a quantidade a ser endereçada e a capacidade do picking.

        if ($caracteristicaEnd == $idCaracteristicaPickingRotativo || $caracteristicaEnd == $idCaracteristicaPicking) {
            $produtosEn = $paleteProdutoRepo->getProdutoByUma($paleteEn->getId());
            foreach ($produtosEn as $produto) {
                $estoqueEn = $estoqueRepo->findOneBy(array('codProduto' => $produto->getId(), 'grade' => $produto->getGrade()));
                if (isset($estoqueEn) && !empty($estoqueEn)) {
                    if ($enderecoEn->getId() != $estoqueEn->getDepositoEndereco()->getId()) {
//                        $this->createXml('error','Existe estoque para o Produto '.$estoqueEn->getCodProduto().' grade '.$estoqueEn->getGrade().' no endereco '.$estoqueEn->getDepositoEndereco()->getDescricao());
                    }
                }

                $embalagens = $embalagemRepo->findBy(array('codProduto' => $produto->getId(), 'grade' => $produto->getGrade()));
                foreach ($embalagens as $embalagemEn) {
                    $enderecoEmbalagem = $embalagemEn->getEndereco();
                    if (isset($enderecoEmbalagem) && !empty($enderecoEmbalagem)) {
                        $caracteristicaEndAntigo = $embalagemEn->getEndereco()->getCaracteristica()->getId();
                        if ($caracteristicaEndAntigo == $idCaracteristicaPicking && $embalagemEn->getEndereco()->getId() != $enderecoEn->getId()) {
                            $this->createXml('error','Produto Ja cadastrado no Picking '.$embalagemEn->getEndereco()->getDescricao());
                        }
                    }

                    $embalagemEn->setEndereco($enderecoEn);
                    $embalagemEn->setCapacidadePicking($capacidadePicking);
                    $this->getEntityManager()->persist($embalagemEn);
                }
                $this->getEntityManager()->flush();
            }
            return true;
        }
        return false;
    }

    public function enderecar($enderecoEn, $paleteEn, $enderecoRepo = null, $paleteRepo = null)
    {
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
        $paleteProdutoRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\PaleteProduto");
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");

        if($enderecoRepo->verificaBloqueioInventario($enderecoEn->getId())) {
            $this->createXml('error','Endereço bloqueado por inventário');
        }

        $idPalete = $paleteEn->getId();

        //Se for endereco de picking nao existe regra de espaco nem o endereco fica indisponivel
        $enderecoAntigo = $paleteEn->getDepositoEndereco();
        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
        $unitizadorEn = $paleteEn->getUnitizador();
        $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
        $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');

        if ($enderecoEn->getIdCaracteristica() == $idCaracteristicaPicking || $enderecoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                $this->createXml('error',"Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
            $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        } else {
            if ($enderecoRepo->enderecoOcupado($enderecoEn->getId())) {
                $this->createXml('error','Endereço já ocupado');
            }
            if ($enderecoRepo->getValidaTamanhoEndereco($enderecoEn->getId(),$unitizadorEn->getLargura(false) * 100) == false) {
                $this->createXml('error','Espaço insuficiente no endereço');
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoEn,$qtdAdjacente,"OCUPAR");
            $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        }

        $paleteEn->setDepositoEndereco($enderecoEn);
        $this->em->persist($paleteEn);
        $this->em->flush();
        $paleteProdutoEn = $paleteProdutoRepo->findOneBy(array('uma' => $idPalete));
        $validade = $paleteProdutoEn->getValidade();
        $dataValidade = null;
        if (isset($validade) && !empty($validade) && !is_null($validade))
            $dataValidade['dataValidade'] = $paleteProdutoEn->getValidade()->format('Y-m-d');

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $paleteRepo->finalizar(array($idPalete), $idPessoa, OrdemServicoEntity::COLETOR, $dataValidade);

        $this->createXml('success','Palete endereçado com sucesso ');
    }

    public function confirmarNovoEnderecoAction()
    {
        $idPalete = $this->_getParam("uma");
        $idEndereco = $this->_getParam("endereco");

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->em->getRepository("wms:Enderecamento\Palete")->find($idPalete);

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $this->em->getRepository("wms:Deposito\Endereco")->find($idEndereco);

        $this->view->enderecoAntigo = $paleteEn->getDepositoEndereco()->getDescricao();;
        $this->view->enderecoSelecionado = $enderecoEn->getDescricao();
        $this->view->idEnderecoSelecionado = $enderecoEn->getId();
        $this->view->uma = $idPalete;
    }

    public function efetivarEnderecamentoAction()
    {
        $confirma = $this->_getParam("confirma");
        $idPalete = $this->_getParam("uma");
        $idEndereco = $this->_getParam("endereco");
        $nivel = $this->_getParam("nivel");

        if ($confirma == "N") {
            $this->addFlashMessage('error','Selecione outro endereço de pulmão');
            $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
        }

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $this->em->getRepository("wms:Deposito\Endereco")->find($idEndereco);
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if ($enderecoRepo->enderecoOcupado($enderecoEn->getId())) {
            $this->addFlashMessage('success','Endereço selecionado está ocupado');
            $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
        }

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        //REGRA PARA LIBERAR O ENDEREÇO ANTIGO
        $enderecoAntigo = $paleteEn->getDepositoEndereco();
        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
        $unitizadorEn = $paleteEn->getUnitizador();
        if ($nivel == '00') {
            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
        } else {
            if ($enderecoRepo->getValidaTamanhoEndereco($idEndereco,$unitizadorEn->getLargura(false) * 100) == false) {
                $this->addFlashMessage('error','Espaço insuficiente no endereço');
                $this->_redirect('mobile/enderecamento/ler-codigo-barras');
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoEn,$qtdAdjacente,"OCUPAR");
            $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        }

        $paleteEn->setDepositoEndereco($enderecoEn);
        $this->em->persist($paleteEn);
        $this->em->flush();

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $paleteRepo->finalizar(array($idPalete),$idPessoa, OrdemServicoEntity::COLETOR);

        $this->addFlashMessage('success','Palete Endereçado com sucesso');
        $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
    }

    public function listarPaletesAction()
    {
        ini_set('max_execution_time', 3000);
        $idRecebimento = $this->_getParam("id");

        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');
            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo    = $this->em->getRepository('wms:Recebimento');

            $paletesSelecionados = $this->_getParam('palete');

            $repositorios = array(
                'enderecoRepo'            => $this->getEntityManager()->getRepository("wms:Deposito\Endereco"),
                'normaPaletizacaoRepo'    => $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao"),
                'estoqueRepo'             => $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque"),
                'reservaEstoqueRepo'      => $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque"),
                'produtoRepo'             => $this->getEntityManager()->getRepository('wms:Produto'),
                'recebimentoRepo'         => $recebimentoRepo,
                'modeloEnderecamentoRepo' => $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo'),
            );

            //METODO PARA IMPRIMIR OS PALETES
            if ($this->_getParam('imprimir') != null) {
                if (count($paletesSelecionados) > 0) {
                    $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
                    $Uma->imprimirPaletes ($paletesSelecionados, $this->getSystemParameterValue("MODELO_RELATORIOS"));
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            //METODO PARA ALTERAR A NORMA DE PALETIZAÇÂO CASO CONFERIDO ERRADO
            if ($this->_getParam('trocarNorma') != null) {
                if (count($paletesSelecionados) >0) {
                    foreach ($paletesSelecionados as $paletes) {
                        $idPaletes = explode(',',$paletes);
                        foreach ($idPaletes as $idPalete) {
                            $paleteEn = $paleteRepo->findOneBy(array('id'=>$idPalete));
                            if ($paleteEn != null) {
                                $produto = $paleteEn->getProdutos();
                                $codProduto     = $produto[0]->getProduto()->getId();
                                $grade          = $produto[0]->getProduto()->getGrade();
                                $codRecebimento = $paleteEn->getRecebimento()->getId();
                                if ($paleteEn->getImpresso() == 'N') {
                                    $paleteRepo->desfazerPalete($idPalete);
                                    $paleteEn->setCodStatus(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_RECEBIMENTO);
                                    $this->getEntityManager()->persist($paleteEn);
                                    $this->getEntityManager()->flush();
                                }
                                $paleteRepo->alterarNorma($codProduto,$grade,$codRecebimento,$idPalete);
                            }
                        }
                    }
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            //IDENTIFICO OS PRODUTOS DO RECEBIMENTO
            $produtos = $recebimentoRepo->getProdutosByRecebimento($idRecebimento);

            //GERAR OS PALETES PARA CADA PRODUTO DO RECEBIMENTO INDIVIDUALMENTE
            foreach ($produtos as $produto) {
                $codProduto = $produto['codigo'];
                $grade      = $produto['grade'];

                //PEGANDO OS PALETES GERADOS DO PRODUTO E ALOCANDO UM ENDEREÇO
                $paletes = $paleteRepo->getPaletes($idRecebimento,$codProduto,$grade,false,$tipoEnderecamento = 'A');
                $paleteRepo->alocaEnderecoAutomaticoPaletes($paletes,$repositorios);
            }

            $paletesResumo = $this->getPaletesExibirResumo($idRecebimento);
            if (count($paletesResumo) == 0) {
                $this->addFlashMessage('error','Nenhum Palete para imprimir no momento');
            }

            $this->view->paletes = $paletesResumo;
            $this->getEntityManager()->commit();
        } catch(Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error',$e->getMessage());
        }
    }

    public function getPaletesExibirResumo($codRecebimento){
        $statusEnderecamento = Palete::STATUS_EM_ENDERECAMENTO;
        $SQL = "SELECT LISTAGG(UMA, ', ') WITHIN GROUP (ORDER BY UMA) ALL_UMA,
                       COUNT(DISTINCT UMA) as QTD_UMA,
                       COD_PRODUTO,
                       DSC_GRADE,
                       DSC_PRODUTO,
                       DSC_UNITIZADOR,
                       SUM(QTD) as QTD
                  FROM (
                SELECT DISTINCT PROD.COD_PRODUTO,
                                PROD.DSC_GRADE,
                                PROD.DSC_PRODUTO,
                                PP.UMA,
                                PP.QTD,
                                UN.DSC_UNITIZADOR
                  FROM PALETE_PRODUTO PP
                  LEFT JOIN PALETE P ON P.UMA = PP.UMA
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = P.COD_UNITIZADOR
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PROD.COD_PRODUTO = PE.COD_PRODUTO AND PROD.DSC_GRADE = PE.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = PE.COD_PRODUTO AND PV.DSC_GRADE = PROD.DSC_GRADE
                 WHERE P.COD_RECEBIMENTO = $codRecebimento
                   AND P.COD_STATUS = $statusEnderecamento
                   AND P.IND_IMPRESSO = 'N'
                   AND P.COD_DEPOSITO_ENDERECO IS NOT NULL
                   AND PE.DTH_INATIVACAO IS NULL
                   AND PV.DTH_INATIVACAO IS NULL)
                GROUP BY COD_PRODUTO, DSC_GRADE, DSC_PRODUTO, DSC_UNITIZADOR";

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public function detalheEnderecoAction()
    {
        ini_set('max_execution_time', 3000);
        $idRecebimento = $this->_getParam("id");

        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo    = $this->em->getRepository('wms:Deposito\Endereco');

            $repositorios = array(
                'normaPaletizacaoRepo'    => $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao"),
                'estoqueRepo'             => $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque"),
                'reservaEstoqueRepo'      => $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque"),
                'produtoRepo'             => $this->getEntityManager()->getRepository('wms:Produto'),
                'recebimentoRepo'         => $this->getEntityManager()->getRepository('wms:Recebimento'),
                'modeloEnderecamentoRepo' => $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo'));

            $paletesSelecionados = $this->_getParam('palete');

            if ($this->_getParam('imprimir') != null) {
                if (count($paletesSelecionados) >0) {
                    $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
                    $Uma->imprimirPaletes ($paletesSelecionados, $this->getSystemParameterValue("MODELO_RELATORIOS"));
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            if ($this->_getParam('trocarNorma') != null) {
                if (count($paletesSelecionados) >0) {
                    foreach ($paletesSelecionados as $idPalete) {
                        $paleteEn = $paleteRepo->findOneBy(array('id'=>$idPalete));
                        if ($paleteEn != null) {
                            $produto = $paleteEn->getProdutos();
                            $codProduto     = $produto[0]->getProduto()->getId();
                            $grade          = $produto[0]->getProduto()->getGrade();
                            $codRecebimento = $paleteEn->getRecebimento()->getId();
                            if ($paleteEn->getImpresso() == 'N') {
                                $paleteRepo->desfazerPalete($idPalete);
                                $paleteEn->setCodStatus(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_RECEBIMENTO);
                                $this->getEntityManager()->persist($paleteEn);
                                $this->getEntityManager()->flush();
                            }
                            $paleteRepo->alterarNorma($codProduto,$grade,$codRecebimento,$idPalete);
                        }
                    }
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            $codProduto = $this->_getParam('produto');
            $grade = $this->_getParam('grade');

            $paletes = array();

            $tmpPaletes = $paleteRepo->getPaletes($idRecebimento,$codProduto,$grade, false, true, true);
            foreach ($tmpPaletes as $tmpPalete) {
                if (($tmpPalete['IND_IMPRESSO'] != 'S') &&
                    ($tmpPalete['COD_SIGLA'] != Palete::STATUS_ENDERECADO) &&
                    ($tmpPalete['COD_SIGLA'] != Palete::STATUS_CANCELADO)) {
                    $tmp = array();
                    $tmp['uma'] = $tmpPalete['UMA'];
                    $tmp['unitizador'] = $tmpPalete['UNITIZADOR'];
                    $tmp['qtd'] = $tmpPalete['QTD'];
                    $tmp['produto'] = $tmpPalete['COD_PRODUTO'] . ' / ' . $tmpPalete['DSC_GRADE'] . ' - ' . $tmpPalete['DSC_PRODUTO'];
                    $tmp['codProduto'] = $tmpPalete['COD_PRODUTO'];
                    $tmp['dscGrade'] = $tmpPalete['DSC_GRADE'];
                    $tmp['dscProduto'] = $tmpPalete['DSC_PRODUTO'];
                    $tmp['idEndereco'] = 0;
                    $tmp['endereco'] = '';
                    $tmp['motivoNaoLiberar'] = '';

                    if ($tmpPalete['QTD_VOL_TOTAL'] > $tmpPalete['QTD_VOL_CONFERIDO']) {
                        $tmp['motivoNaoLiberar'] = 'Aguardando conf. todos volumes';
                    }

                    $paleteEn = $paleteRepo->findOneBy(array('id'=>$tmp['uma']));
                    if ($paleteEn->getDepositoEndereco() == null) {

                        $sugestaoEndereco = $paleteRepo->getSugestaoEnderecoPalete($paleteEn, $repositorios);

                        if ($sugestaoEndereco != null) {
                            foreach($sugestaoEndereco as $sugestao) {

                                $tmp['idEndereco'] = $sugestao['COD_DEPOSITO_ENDERECO'];
                                $tmp['endereco'] = $sugestao['DSC_DEPOSITO_ENDERECO'];

                                $permiteEnderecar = $enderecoRepo->getValidaTamanhoEndereco($tmp['idEndereco'],$paleteEn->getUnitizador()->getLargura(false) * 100);
                                if ($permiteEnderecar == true) {
                                    $paleteRepo->alocaEnderecoPalete($tmp['uma'],$tmp['idEndereco']);
                                    $this->getEntityManager()->flush();
                                    break;
                                }
                            }





//                            if ($permiteEnderecar == true) {
//                                $paleteRepo->alocaEnderecoPalete($tmp['uma'],$sugestaoEndereco['COD_DEPOSITO_ENDERECO']);
//                                $this->getEntityManager()->flush();
//                            } else {
//                                $tmp['motivoNaoLiberar'] = "Palete " . $tmp['uma'] . " não cabe no endereço " . $tmp['endereco'];
//                            }
                        }
                    } else {
                        $tmp['idEndereco'] = $paleteEn->getDepositoEndereco()->getId();
                        $tmp['endereco'] = $paleteEn->getDepositoEndereco()->getDescricao();
                    }

                    if (($tmp['motivoNaoLiberar'] == '') && ($tmp['idEndereco'] == 0)) {
                        $tmp['motivoNaoLiberar'] = 'Sem Sugestão de Endereço';
                    }
                    $paletes[] = $tmp;

                }
            }

            if (count($paletes) == 0) {
                $this->addFlashMessage('error','Nenhum Palete para imprimir no momento');
            }

            $this->view->paletes = $paletes;
            $this->getEntityManager()->commit();
        } catch(Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error',$e->getMessage());
        }

    }

    public function imprimirUmaAction()
    {
        $idRecebimento = $this->_getParam("id");
        $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');

        $dadosPalete = array();
        $dadosPalete['endereco'] = '01.00.000.01';
        $dadosPalete['idUma']    = '1231';
        $dadosPalete['picking']  = '01.00.000.00';
        $dadosPalete['qtd']      = 123;
        $paletesArray = array(0=>$dadosPalete);

        $param = array();
        $param['idRecebimento'] = $idRecebimento;
        $param['codProduto']    = '3908040';
        $param['grade']         = 'PRETO...';

        $param['codProduto']    = '688037';
        $param['grade']         = 'IMB/PRET';

        $param['paletes']       = $paletesArray;
        $param['dataValidade']  = null;

        $Uma->imprimir($param, $this->getSystemParameterValue("MODELO_RELATORIOS"));

        //$this->redirect('ler-codigo-barras','recebimento','mobile',array('idRecebimento'=>$idRecebimento));
    }

    public function movimentacaoAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');

        $this->view->codigoBarras = $codigoBarras;
        $this->view->nivel = $nivel;
    }

    public function umaByEnderecoAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');
        $this->view->codigoBarras = $codigoBarras;

        try {
            if (empty($codigoBarras))
                throw new \Exception ("Necessário informar o Endereço");

            if (empty($nivel) && !isset($nivel))
                throw new \Exception ("Necessário informar o Nivel");

            $LeituraColetor = new LeituraColetor();
            $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);
            $endereco = $this->getEnderecoByParametro($codigoBarras);

            if (empty($endereco))
                throw new \Exception ("Endereço Inválido");

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $result = $estoqueRepo->getProdutoByNivel($endereco[0]['DSC_DEPOSITO_ENDERECO'], $nivel);

            if ($result == NULL) {
                throw new \Exception ("Endereço selecionado está vazio");
            } else {
                $idEstoque = $result[0]['id'];

                if ($result[0]['uma']) {
                    $this->_redirect('/mobile/enderecamento/endereco-uma/cb/' . $idEstoque . '/end/' . $codigoBarras . '/nivelAntigo/' . $nivel);
                } else {
                    $this->_redirect('/mobile/enderecamento/endereco-produto/cb/' . $idEstoque . '/end/' . $codigoBarras . '/nivelAntigo/' . $nivel);
                }
            }
        }  catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }

    }

    public function enderecoProdutoAction()
    {
        $idEstoque = $this->_getParam('cb');
        $this->view->cb = $idEstoque;
        $this->view->end = $this->_getParam('end');
        $this->view->nivel = $this->_getParam('nivel');

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque")->findOneBy(array('id'=>$idEstoque));

        $rua         = $estoqueEn->getDepositoEndereco()->getRua();
        $predio      = $estoqueEn->getDepositoEndereco()->getPredio();
        $nivel       = $estoqueEn->getDepositoEndereco()->getNivel();
        $apartamento = $estoqueEn->getDepositoEndereco()->getApartamento();
        $idEstoque   = $estoqueEn->getDepositoEndereco()->getId();

        $this->view->rua = $rua;
        $this->view->predio = $predio;
        $this->view->nivel = $nivel;
        $this->view->apartamento = $apartamento;

    }

    public function enderecoUmaAction()
    {
        $idEstoque = $this->_getParam('cb');
        $this->view->cb = $idEstoque;
        $this->view->end = $this->_getParam('end');
        $this->view->nivel = $this->_getParam('nivel');

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque")->findOneBy(array('id'=>$idEstoque));

        $rua         = $estoqueEn->getDepositoEndereco()->getRua();
        $predio      = $estoqueEn->getDepositoEndereco()->getPredio();
        $nivel       = $estoqueEn->getDepositoEndereco()->getNivel();
        $apartamento = $estoqueEn->getDepositoEndereco()->getApartamento();
        $idEstoque   = $estoqueEn->getDepositoEndereco()->getId();

        $this->view->rua = $rua;
        $this->view->predio = $predio;
        $this->view->nivel = $nivel;
        $this->view->apartamento = $apartamento;
    }

    public function enderecoDestinoAction()
    {
        $LeituraColetor = new LeituraColetor();
        $this->view->codigoBarrasUMA = $codBarrasUma = $LeituraColetor->retiraDigitoIdentificador($this->_getParam('codigoBarrasUMA'));
        $this->view->etiquetaProduto = $codBarras = $this->_getParam('etiquetaProduto');
        $this->view->idEstoque = $idEstoque = $this->_getParam('cb');
        $enderecoParam = $this->_getParam('end');
        $nivel = $this->_getParam('nivelAntigo');
        $enderecoByParametro = $this->getEnderecoByParametro($enderecoParam);

        /** @var \Wms\Domain\Entity\Deposito\Endereco $endereco */
        $endereco = $this->getEnderecoNivel($enderecoByParametro[0]['DSC_DEPOSITO_ENDERECO'],$nivel);

        $embalagemEn = null;

        if (!empty($codBarrasUma)){
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
            $paleteProdutoRepo = $this->em->getRepository('wms:Enderecamento\PaleteProduto');

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteProduto $paleteProduto */
            $paleteProduto = $paleteProdutoRepo->findOneBy(array('uma'=>$codBarrasUma));
            if (empty($paleteProduto))
                throw new Exception("UMA $codBarrasUma não encontrada!");

            $embalagemEn = $paleteProduto->getEmbalagemEn();

        } else if (!empty($codBarras)){
            $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
            /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEn */
            $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));
            if (empty($embalagemEn))
                throw new Exception("Não foi encontrada a embalagem $codBarras");
        }

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository('wms:Enderecamento\Estoque');

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $endereco, 'codProduto' => $embalagemEn->getCodProduto(), 'grade' => $embalagemEn->getGrade()));
        if (empty($estoqueEn))
            throw new Exception("Não foi encontrado o estoque com endereco " . $endereco->getDescricao() . " produto " . $embalagemEn->getCodProduto() . " grade " . $embalagemEn->getGrade());

        $this->view->qtd = $qtd = $estoqueEn->getQtd();

        $idEndereco = $endereco->getId();
        $codProduto = $embalagemEn->getCodProduto();
        $grade = $embalagemEn->getGrade();

        $SQL = "SELECT RE.*
                  FROM RESERVA_ESTOQUE RE
                 INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                 WHERE RE.COD_DEPOSITO_ENDERECO = $idEndereco 
                   AND REP.COD_PRODUTO = '$codProduto'
                   AND REP.DSC_GRADE = '$grade'
                   AND RE.TIPO_RESERVA = 'S'
                   AND RE.IND_ATENDIDA = 'N'";
        $verificaReservaSaida = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($verificaReservaSaida) > 0) {
            throw new \Exception ("Existe Reserva de Saída para esse endereço que ainda não foi atendida!");
        }
    }

    public function confirmaEnderecamentoAction()
    {
        ini_set('max_execution_time', 3000);
        $params = array();
        $qtd = $this->_getParam('qtd');
        $params['uma'] = $this->_getParam('uma');
        $params['etiquetaProduto'] = $this->_getParam('etiquetaProduto');
        $params['idEstoque'] = $this->_getParam('cb');
        $enderecoNovo = $this->_getParam('novoEndereco');
        $nivelNovo = $this->_getParam('nivel');
        $enderecoAntigo = $this->_getParam('end');
        $nivelAntigo = $this->_getParam('nivelAntigo');

        /** @var \Wms\Domain\Entity\Produto $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $volumeRepo */
        $volumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');

        $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
        $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');

        try {

            $this->getEntityManager()->beginTransaction();

            if ($enderecoNovo) {
                $LeituraColetor = new LeituraColetor();
                $enderecoNovo = $LeituraColetor->retiraDigitoIdentificador($enderecoNovo);
            }

            $enderecoAntigo = $this->getEnderecoByParametro($enderecoAntigo);
            if ($enderecoAntigo) {
                $enderecoAntigo = $this->getEnderecoNivel($enderecoAntigo[0]['DSC_DEPOSITO_ENDERECO'], $nivelAntigo);
            }
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');

            if (isset($params['uma']) && !empty($params['uma'])) {
                $estoqueEn = $estoqueRepo->findBy(array('uma' => $params['uma'], 'depositoEndereco' => $enderecoAntigo));
                /** @var Estoque $estoque */
                foreach ($estoqueEn as $estoque) {
                    //INSERE NOVO ESTOQUE
                    $params['qtd'] = $qtd;
                    $newEndereco = $this->getEnderecoByParametro($enderecoNovo);
                    $params['endereco'] = $endereco = $this->getEnderecoNivel($newEndereco[0]['DSC_DEPOSITO_ENDERECO'], $nivelNovo);
                    if (!isset($endereco) || empty($endereco)) {
                        $this->addFlashMessage('error', 'Novo Endereço não Encontrado!');
                        $this->_redirect('/mobile/enderecamento/movimentacao');
                    }

                    /** @var \Wms\Domain\Entity\Produto $produtoEn */
                    $produtoEn = $params['produto'] = $produtoRepo->findOneBy(array('id' => $estoque->getCodProduto(), 'grade' => $estoque->getGrade()));
                    $params['embalagem'] = $embalagemEn = $embalagemRepo->findOneBy(array('id' => $estoque->getProdutoEmbalagem()));
                    $params['volume'] = $volumeEn = $volumeRepo->findOneBy(array('id' => $estoque->getProdutoVolume()));

                    if ($enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPicking ||
                        $enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                            throw new \Exception("Só é permitido transferir de Picking para Picking Dinâmico!");
                        }
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if (isset($embalagemEn)) {
                                $embalagens = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                                foreach ($embalagens as $embalagemEn) {
                                    $embalagemEn->setEndereco($endereco);
                                    $this->getEntityManager()->persist($embalagemEn);
                                }
                                $this->getEntityManager()->flush();
                            } else if (isset($volumeEn)) {
                                $volumeEn->setEndereco($endereco);
                                $this->getEntityManager()->persist($volumeEn);
                                $this->getEntityManager()->flush();
                            }
                        }
                    } else {
                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E O ENDEREÇO DO PRODUTO ESTÁ VAZIO ... E TRAVA
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                            if ((isset($embalagemEn) && is_null($embalagemEn->getEndereco())) || isset($volumeEn) && is_null($volumeEn->getEndereco())) {
                                throw new \Exception("Esse Endereço de Picking não está cadastrado para esse produto!");
                            }
                        }

                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING DINAMICO E SE O ENDERECO DO PRODUTO ESTÁ VAZIO E SALVA O ENDEREÇO DE DESTINO
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if (isset($embalagemEn) && is_null($embalagemEn->getEndereco())) {
                                $embalagens = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                                foreach ($embalagens as $embalagemEn) {
                                    $embalagemEn->setEndereco($endereco);
                                    $this->getEntityManager()->persist($embalagemEn);
                                }
                                $this->getEntityManager()->flush();
                            } else if (isset($volumeEn) && is_null($volumeEn->getEndereco())) {
                                $volumeEn->setEndereco($endereco);
                                $this->getEntityManager()->persist($volumeEn);
                                $this->getEntityManager()->flush();
                            }
                        }

                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E SE O ENDEREÇO DE DESTINO É DIFERENTE DO ENDEREÇO CADASTRADO NO PRODUTO E EXIBE MENSAGEM DE ERRO
                        if (($endereco->getIdCaracteristica() == $idCaracteristicaPicking || $endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo)) {
                            if (isset($embalagemEn)) {
                                if ($endereco->getId() !== $embalagemEn->getEndereco()->getId()) {
                                    throw new \Exception("Produto ja cadastrado no Picking " . $embalagemEn->getEndereco()->getDescricao() . "!");
                                }
                            } else if (isset($volumeEn)) {
                                if ($endereco->getId() !== $volumeEn->getEndereco()) {
                                    throw new \Exception("Produto ja cadastrado no Picking " . $embalagemEn->getEndereco()->getDescricao() . "!");
                                }
                            }
                        }
                    }

                    $estoqueDestino = $estoqueRepo->findOneBy(array('codProduto' => $produtoEn, 'grade' => $produtoEn->getGrade(), 'depositoEndereco' => $endereco));
                    if ($produtoEn->getValidade() == 'S' ) {
                        $valEstOrigem = $estoque->getValidade();
                        $valEstDestino = (!empty($estoqueDestino))? $estoqueDestino->getValidade() : null;

                        if (!empty($valEstOrigem)) {
                            if (!empty($valEstDestino)) {
                                $validade = ($valEstOrigem < $valEstDestino)? $valEstOrigem : $valEstDestino;
                            } else {
                                $validade = $valEstOrigem;
                            }
                        } elseif(!empty($valEstDestino)) {
                            $validade = $valEstDestino;
                        } else {
                            /** @var \Wms\Domain\Entity\Enderecamento\Palete $umaOrigem */
                            $umaOrigem = (!empty($estoque->getUma()))? $this->em->find('wms:Enderecamento\Palete', $estoque->getUma()) : null;

                            if (!empty($estoqueDestino))
                                /** @var \Wms\Domain\Entity\Enderecamento\Palete $umaDestino */
                                $umaDestino = (!empty($estoqueDestino->getUma()))? $this->em->find('wms:Enderecamento\Palete', $estoqueDestino->getUma()) : null;

                            $valUmaOrigem = (!empty($umaOrigem))? $umaOrigem->getValidade() : null;
                            $valUmaDestino = (!empty($umaDestino))? $umaDestino->getValidade() : null;

                            if (!empty($valUmaOrigem)) {
                                if (!empty($valUmaDestino)) {
                                    $validade = ($valUmaOrigem < $valUmaDestino)? $valUmaOrigem : $valUmaDestino;
                                } else {
                                    $validade = $valUmaOrigem;
                                }
                            } elseif(!empty($valUmaDestino)) {
                                $validade = $valUmaDestino;
                            }
                        }
                        if (isset($validade) && !empty($validade)) {
                            $params['validade'] = $validade->format('d/m/Y');
                        }
                    }

                    if (empty($estoqueDestino))
                        $data['uma'] = $estoque->getUma();

                    $params['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoAntigo->getDescricao();
                    $estoqueRepo->movimentaEstoque($params);
                    //RETIRA ESTOQUE
                    $params['endereco'] = $enderecoAntigo;
                    $params['validade'] = $estoque->getValidade()->format('d/m/Y');
                    $params['qtd'] = $qtd * -1;
                    $params['observacoes'] = "Transferencia de Estoque - Destino: ".$endereco->getDescricao();
                    $estoqueRepo->movimentaEstoque($params);
                }
            } else if (isset($params['etiquetaProduto']) && !empty($params['etiquetaProduto'])) {
                $LeituraColetor = new LeituraColetor();
                $params['etiquetaProduto'] = $LeituraColetor->analisarCodigoBarras($params['etiquetaProduto']);

                $params['embalagem'] = $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $params['etiquetaProduto']));
                $volumeEn = $volumeRepo->findOneBy(array('codigoBarras' => $params['etiquetaProduto']));

                if (isset($params['embalagem']) && !empty($params['embalagem'])) {
                    /** @var Wms\Domain\Entity\Produto $produtoEn */
                    $produtoEn = $params['produto'] = $produtoRepo->findOneBy(array('id' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                    $params['qtd'] = $qtd;
                    $newEndereco = $this->getEnderecoByParametro($enderecoNovo);
                    if (!isset($newEndereco) || empty($newEndereco))
                        throw new \Exception("Novo Endereço não encontrado!");

                    $params['endereco'] = $endereco = $this->getEnderecoNivel($newEndereco[0]['DSC_DEPOSITO_ENDERECO'], $nivelNovo);

                    if ($enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPicking ||
                        $enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                            throw new \Exception("Só é permitido transferir de Picking para Picking Dinâmico!");
                        }
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            $embalagens = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                            foreach ($embalagens as $embalagemEn) {
                                $embalagemEn->setEndereco($endereco);
                                $this->getEntityManager()->persist($embalagemEn);
                            }
                            $this->getEntityManager()->flush();
                        }
                    } else {
                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E O ENDEREÇO DO PRODUTO ESTÁ VAZIO ... E TRAVA
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                            if (isset($embalagemEn) && is_null($embalagemEn->getEndereco())) {
                                throw new \Exception("Esse Endereço de Picking não está cadastrado para esse produto!");
                            }
                        }

                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING DINAMICO E SE O ENDERECO DO PRODUTO ESTÁ VAZIO E SALVA O ENDEREÇO DE DESTINO
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if (isset($embalagemEn) && is_null($embalagemEn->getEndereco())) {
                                $embalagens = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                                foreach ($embalagens as $embalagemEn) {
                                    $embalagemEn->setEndereco($endereco);
                                    $this->getEntityManager()->persist($embalagemEn);
                                }
                                $this->getEntityManager()->flush();
                            }
                        }

                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E SE O ENDEREÇO DE DESTINO É DIFERENTE DO ENDEREÇO CADASTRADO NO PRODUTO E EXIBE MENSAGEM DE ERRO
                        if (($endereco->getIdCaracteristica() == $idCaracteristicaPicking || $endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo)) {
                            if (isset($embalagemEn)) {
                                if ($endereco->getId() !== $embalagemEn->getEndereco()->getId()) {
                                    throw new \Exception("Produto ja cadastrado no Picking " . $embalagemEn->getEndereco()->getDescricao() . "!");
                                }
                            }
                        }
                    }

                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $enderecoAntigo, 'codProduto' => $embalagemEn->getCodProduto(), 'grade' => $embalagemEn->getGrade()));
                    if (!$estoqueEn)
                        throw new \Exception("Estoque não Encontrado!");

                    $estoqueDestino = $estoqueRepo->findOneBy(array('codProduto' => $produtoEn, 'grade' => $produtoEn->getGrade(), 'depositoEndereco' => $endereco));
                    if ($produtoEn->getValidade() == 'S' ) {
                        $valEstOrigem = $estoqueEn->getValidade();
                        $valEstDestino = (!empty($estoqueDestino))? $estoqueDestino->getValidade() : null;

                        if (!empty($valEstOrigem)) {
                            if (!empty($valEstDestino)) {
                                $validade = ($valEstOrigem < $valEstDestino)? $valEstOrigem : $valEstDestino;
                            } else {
                                $validade = $valEstOrigem;
                            }
                        } elseif(!empty($valEstDestino)) {
                            $validade = $valEstDestino;
                        } else {
                            /** @var \Wms\Domain\Entity\Enderecamento\Palete $umaOrigem */
                            $umaOrigem = (!empty($estoqueEn->getUma()))? $this->em->find('wms:Enderecamento\Palete', $estoqueEn->getUma()) : null;

                            if (!empty($estoqueDestino))
                                /** @var \Wms\Domain\Entity\Enderecamento\Palete $umaDestino */
                                $umaDestino = (!empty($estoqueDestino->getUma()))? $this->em->find('wms:Enderecamento\Palete', $estoqueDestino->getUma()) : null;

                            $valUmaOrigem = (!empty($umaOrigem))? $umaOrigem->getValidade() : null;
                            $valUmaDestino = (!empty($umaDestino))? $umaDestino->getValidade() : null;

                            if (!empty($valUmaOrigem)) {
                                if (!empty($valUmaDestino)) {
                                    $validade = ($valUmaOrigem < $valUmaDestino)? $valUmaOrigem : $valUmaDestino;
                                } else {
                                    $validade = $valUmaOrigem;
                                }
                            } elseif(!empty($valUmaDestino)) {
                                $validade = $valUmaDestino;
                            }
                        }
                        if (isset($validade) && !empty($validade)) {
                            $params['validade'] = $validade->format('d/m/Y');
                        }
                    }

                    $params['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoAntigo->getDescricao();
                    $estoqueRepo->movimentaEstoque($params);
                    //RETIRA ESTOQUE
                    $params['observacoes'] = "Transferencia de Estoque -  Destino: ".$params['endereco']->getDescricao();
                    $params['endereco'] = $enderecoAntigo;
                    $params['validade'] = $estoqueEn->getValidade()->format('d/m/Y');
                    $params['qtd'] = $qtd * -1;
                    $estoqueRepo->movimentaEstoque($params);
                }

                if (isset($volumeEn) && !empty($volumeEn)) {
                    $norma = $volumeEn->getNormaPaletizacao()->getId();
                    $codProduto = $volumeEn->getCodProduto();
                    $grade = $volumeEn->getGrade();
                    $volumes = $volumeRepo->findBy(array('normaPaletizacao' => $norma, 'codProduto' => $codProduto, 'grade' => $grade));
                    foreach ($volumes as $volume) {
                        $params['qtd'] = $qtd;
                        $newEndereco = $this->getEnderecoByParametro($enderecoNovo);
                        $params['endereco'] = $endereco = $this->getEnderecoNivel($newEndereco[0]['DSC_DEPOSITO_ENDERECO'], $nivelNovo);
                        $params['volume'] = $volume;
                        $params['produto'] = $produtoRepo->findOneBy(array('id' => $volume->getProduto(), 'grade' => $grade));

                        if ($enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPicking ||
                            $enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                                throw new \Exception("Só é permitido transferir de Picking para Picking Dinâmico!");
                            }
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                                $volume->setEndereco($endereco);
                                $this->getEntityManager()->persist($volume);
                                $this->getEntityManager()->flush();
                            }
                        } else {
                            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E O ENDEREÇO DO PRODUTO ESTÁ VAZIO ... E TRAVA
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                                if (isset($volume) && is_null($volume->getEndereco())) {
                                    throw new \Exception("Esse Endereço de Picking não está cadastrado para esse produto!");
                                }
                            }

                            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING DINAMICO E SE O ENDERECO DO PRODUTO ESTÁ VAZIO E SALVA O ENDEREÇO DE DESTINO
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                                if (isset($volume) && is_null($volume->getEndereco())) {
                                    $volume->setEndereco($endereco);
                                    $this->getEntityManager()->persist($volume);
                                    $this->getEntityManager()->flush();
                                }
                            }

                            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E SE O ENDEREÇO DE DESTINO É DIFERENTE DO ENDEREÇO CADASTRADO NO PRODUTO E EXIBE MENSAGEM DE ERRO
                            if (($endereco->getIdCaracteristica() == $idCaracteristicaPicking || $endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo)) {
                                if (isset($volume)) {
                                    if ($endereco->getId() !== $volume->getEndereco()) {
                                        throw new \Exception("Produto ja cadastrado no Picking " . $embalagemEn->getEndereco()->getDescricao() . "!");
                                    }
                                }
                            }
                        }

                        $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $enderecoAntigo, 'codProduto' => $volume->getProduto(), 'grade' => $volume->getGrade()));
                        if (!$estoqueEn)
                            throw new \Exception("Estoque não Encontrado!");

                        $params['validade'] = null;
                        $params['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoAntigo->getDescricao();
                        $estoqueRepo->movimentaEstoque($params);

                        //RETIRA ESTOQUE
                        $params['observacoes'] = "Transferencia de Estoque -  Destino: ".$params['endereco']->getDescricao();
                        $params['endereco'] = $enderecoAntigo;
                        $params['qtd'] = $qtd * -1;
                        $estoqueRepo->movimentaEstoque($params);

                    }
                }
            }

            $this->getEntityManager()->commit();
            $this->addFlashMessage('success', 'Endereço alterado com sucesso!');
            $this->_redirect('/mobile/enderecamento/movimentacao');

        }  catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception ($e->getMessage());
        }
    }

    private function getEnderecoNivel($dscEndereco, $nivel)
    {
        if (strlen($dscEndereco) < 8) {
            $rua = 0;
            $predio = 0;
            $nivel = 0;
            $apartamento = 0;
        } else {
            $dscEndereco = str_replace('.','',$dscEndereco);
            if (strlen($dscEndereco) == 8){
                $tempEndereco = "0" . $dscEndereco;
            } else {
                $tempEndereco = $dscEndereco;
            }
            $rua = intval( substr($tempEndereco,0,2));
            $predio = intval(substr($tempEndereco,2,3));
            $apartamento = intval(substr($tempEndereco,7,2));
        }

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        return $enderecoRepo->findOneBy(array('rua' => $rua, 'predio' => $predio, 'apartamento' => $apartamento, 'nivel' => $nivel));
    }

    public function getEnderecoByParametro($dscEndereco)
    {
        $tamanhoRua         = $this->getSystemParameterValue('TAMANHO_CARACT_RUA');
        $tamanhoPredio      = $this->getSystemParameterValue('TAMANHO_CARACT_PREDIO');
        $tamanhoNivel       = $this->getSystemParameterValue('TAMANHO_CARACT_NIVEL');
        $tamanhoApartamento = $this->getSystemParameterValue('TAMANHO_CARACT_APARTAMENTO');

        $sql = " SELECT DSC_DEPOSITO_ENDERECO, NUM_NIVEL, COD_DEPOSITO_ENDERECO, COD_CARACTERISTICA_ENDERECO
                 FROM DEPOSITO_ENDERECO
                 WHERE
                 (CAST(SUBSTR('00' || NUM_RUA,-$tamanhoRua,$tamanhoRua)
                    || SUBSTR('00' || NUM_PREDIO,-$tamanhoPredio,$tamanhoPredio)
                    || SUBSTR('00' || NUM_NIVEL,-$tamanhoNivel,$tamanhoNivel)
                    || SUBSTR('00' || NUM_APARTAMENTO,-$tamanhoApartamento,$tamanhoApartamento) as INT)) = " . $dscEndereco;

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getCapacidadePickingAjaxAction()
    {
        $dscEndereco = $this->_getParam('endereco');
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $$embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

        $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $dscEndereco));
        if (!empty($enderecoEn)) {
            $embalagemEn = $embalagemRepo->findOneBy(array('endereco' => $enderecoEn));
            if (!empty($embalagemEn)) {
                $this->_helper->json(array('status' => 'Ok', 'caracteristicaEndereco' => $enderecoEn->getIdCaracteristica(), 'capacidadePicking' => $embalagemEn->getCapacidadePicking()));
            }
        }
        $this->_helper->json(array('status' => 'Error', 'Msg' => 'Endereço não encontrado'));
    }
}

