<?php
use Wms\Controller\Action,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Util\Coletor as ColetorUtil,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Util\Endereco as EnderecoUtil;

class Mobile_OndaRessuprimentoController extends Action
{

    public function listarOndasAction()
    {

        $codProduto = null;
        $grade = null;
        $html = '
            <div class="row pull-right" >
         <a  class="btn" style="width: auto;float: none; margin: 0px 0px 6px 0px" href="/mobile/onda-ressuprimento/filtrar" title="filtrar" >Filtrar Produto</a>
         <a  class="btn" style="width: auto;float: none; margin: 0px 0px 6px 0px" href="/mobile/onda-ressuprimento/filtrar/expedicao/1" title="filtrar" >Filtrar Expedicao</a>
        </div>';

        if ($this->_getParam('codProduto') != null || $this->_getParam('expedicao') != null) {
            $codProduto = $this->_getParam('codProduto');
            $grade = $this->_getParam('grade');
            $html .= '
         <a  class="finalizar" href="/mobile/onda-ressuprimento/listar-ondas/" style="background-image: -moz-linear-gradient(center top , #e5c062, #e5c062); border: 1px solid #e5c062; float: none; margin: 0px 0px 0px 0px" href="teste" title="Filtrar" >Mostrar Todas</a>
        ';
        }
        $expedicao = $this->_getParam('expedicao');
        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
        $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $ondas = $ondaRepo->getOndasEmAberto($codProduto,$grade, null, $expedicao);

        $menu = array();
        $enderecoAnterior = null;
        foreach ($ondas as $onda) {
            $aviso = "";
            if ($enderecoAnterior == $onda['Endereco']) {
                $aviso = " *** ";
            }
            $botao = array(
                'url' => '/mobile/onda-ressuprimento/selecionar-endereco/idOnda/'.$onda['OndaOsId'],
                'label' => $aviso . '' . $onda['Onda']. ' - '.$onda['Endereco'] . $aviso,
            );
            $enderecoAnterior = $onda['Endereco'];
            $menu[] = $botao;
        }
        $this->view->menu = $menu;

        $this->view->header = $html;
        $this->view->showQuantidade = true;
        $this->renderScript('menu.phtml');
    }

    public function filtrarAction(){

        $form = new PickingLeitura();
        $form->setControllerUrl("onda-ressuprimento");
        $form->setActionUrl("filtrar");
        $expedicao = $this->_getParam('expedicao');
        if($expedicao == 1){
            $form->setLabel("Busca de Expedicao");
            $form->setLabelElement("Código da Expedicao");
        }else {
            $form->setLabel("Busca de Produto/Picking");
            $form->setLabelElement("Código de Barras ou Código do Produto");
        }

        $codigoBarras = $this->_getParam('codigoBarras');
        if ($codigoBarras != NULL) {
            if($expedicao == 1){
                $this->redirect("listar-ondas",'onda-ressuprimento','mobile',array('expedicao'=>$codigoBarras));
            }
            //VERIFICA SE FOI DIGITADO O CÓDIGO DE UM PRODUTO
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $produtoEn = $produtoRepo->findOneBy(array('id'=>$codigoBarras));
            if ($produtoEn != null) {
                $this->redirect("listar-ondas",'onda-ressuprimento','mobile',array('codProduto'=>$produtoEn->getId(), 'grade'=>$produtoEn->getGrade()));
            }

            //VERIFICA SE FOI DIGITADO O CÓDIGO DE BARRAS DE UM PRODUTO
            $codBarrasProduto = ColetorUtil::adequaCodigoBarras($codigoBarras, true);
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $info = $produtoRepo->getProdutoByCodBarras($codBarrasProduto);
            if ($info!= null) {
                $this->redirect("listar-ondas",'onda-ressuprimento','mobile',array('codProduto'=>$info[0]['idProduto'], 'grade'=>$info[0]['grade']));
            }

            //VERIFICA SE FOI DIGITADO O CÓDIGO DE BARRAS DE UM ENDEREÇO DE PICKING
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $codBarrasEndereco = ColetorUtil::retiraDigitoIdentificador($codigoBarras);
            $result = $enderecoRepo->getProdutoByEndereco($codBarrasEndereco,false);
            if (count($result) > 0){
                $this->redirect("listar-ondas",'onda-ressuprimento','mobile',array('codProduto'=>$result[0]['codProduto'], 'grade'=>$result[0]['grade']));
            }

            $this->addFlashMessage("error","Nenhum Produto ou Endereço encontrado no código de barras");
            $this->_redirect('/mobile/onda-ressuprimento/filtrar');
        }


        $this->view->form = $form;
        $form->init();
    }

    public function selecionarEnderecoAction(){
        $idOnda = $this->_getParam('idOnda');

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $OndaRessuprimentoRepo */
        $OndaRessuprimentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $valores = $OndaRessuprimentoRepo->getDadosOnda($idOnda);

        $ondaOsEn = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs")->findOneBy(array('id'=>$idOnda));

        $temLote = false;
        foreach ($valores as $item) {
            if ($item['Lote'] != \Wms\Domain\Entity\Produto\Lote::LND) $temLote = true;
            $arrayQtds[$item['Lote']] = $embalagemRepo->getQtdEmbalagensProduto($valores[0]['Codigo'], $valores[0]['Grade'], $item['Qtde']);
        };

        $this->view->produtos = $ondaOsEn->getProdutos();
        $this->view->idOnda = $idOnda;
        $this->view->codProduto = $valores[0]['Codigo'];
        $this->view->grade = $valores[0]['Grade'];
        $this->view->dscProduto = $valores[0]['Produto'];
        $this->view->endPulmao = $valores[0]['Pulmao'];
        $this->view->idEnderecoPulmao = $valores[0]['idPulmao'];
        $this->view->endPicking = $valores[0]['Picking'];
        $this->view->temLote = $temLote;
        $this->view->qtd = $arrayQtds;
    }

    public function validarEnderecoAction()
    {

        $idOnda = $this->_getParam('idOnda');
        $idEnderecoPulmao = $this->_getParam('idEnderecoPulmao');
        $qtd = $this->_getParam('qtd');
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');

        try{
            if ($codigoBarras) {
              $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarras);
            }

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $endereco = \Wms\Util\Endereco::formatar($codigoBarras);
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
            if (empty($enderecoEn)) {
                throw new Exception("Endereço não encontrado");
            }

            $result = $estoqueRepo->getProdutoByNivel($endereco, $nivel);

            if ($result == NULL)
            {
                throw new Exception("Endereço selecionado está vazio");
            }
            if ($result[0]['idEndereco'] != $idEnderecoPulmao) {
                throw new Exception("Endereço selecionado errado");
            }

            if ($result[0]['uma']) {
                $this->_redirect('/mobile/onda-ressuprimento/selecionar-uma/idOnda/' . $idOnda);
            } else {
                $this->_redirect('/mobile/onda-ressuprimento/selecionar-produto/idOnda/' . $idOnda );
            }
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
            $this->_redirect('/mobile/onda-ressuprimento/selecionar-endereco/idOnda/'.$idOnda);
        }
    }

    public function selecionarUmaAction()
    {
        $idOnda = $this->_getParam('idOnda');

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $OndaRessuprimentoRepo */
        $OndaRessuprimentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $valores = $OndaRessuprimentoRepo->getDadosOnda($idOnda);

        $ondaOsEn = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs")->findOneBy(array('id'=>$idOnda));

        $temLote = false;
        foreach ($valores as $item) {
            if ($item['Lote'] != \Wms\Domain\Entity\Produto\Lote::LND) $temLote = true;
            $arrayQtds[$item['Lote']] = $embalagemRepo->getQtdEmbalagensProduto($valores[0]['Codigo'], $valores[0]['Grade'], $item['Qtde']);
        };

        $this->view->produtos = $ondaOsEn->getProdutos();
        $this->view->idOnda = $idOnda;
        $this->view->codProduto = $valores[0]['Codigo'];
        $this->view->grade = $valores[0]['Grade'];
        $this->view->dscProduto = $valores[0]['Produto'];
        $this->view->endPulmao = $valores[0]['Pulmao'];
        $this->view->idEnderecoPulmao = $valores[0]['idPulmao'];
        $this->view->endPicking = $valores[0]['Picking'];
        $this->view->temLote = $temLote;
        $this->view->qtd = $arrayQtds;
    }

    public function selecionarProdutoAction()
    {
        $idOnda = $this->_getParam('idOnda');

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $OndaRessuprimentoRepo */
        $OndaRessuprimentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $valores = $OndaRessuprimentoRepo->getDadosOnda($idOnda);

        $codBarras = $OndaRessuprimentoRepo->getCodBarrasItensOnda($idOnda);

        $ondaOsEn = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs")->findOneBy(array('id'=>$idOnda));

        $temLote = false;
        $arrLotes = [];
        foreach ($valores as $item) {
            if ($item['Lote'] != \Wms\Domain\Entity\Produto\Lote::LND) {
                $temLote = true;
                $arrLotes[$item['Lote']] = false;
            }
            $arrayQtds[$item['Lote']] = $embalagemRepo->getQtdEmbalagensProduto($valores[0]['Codigo'], $valores[0]['Grade'], $item['Qtde']);
        };

        $this->view->codBarras = json_encode($codBarras);
        $this->view->lotes = json_encode($arrLotes);
        $this->view->produtos = $ondaOsEn->getProdutos();
        $this->view->idOnda = $idOnda;
        $this->view->codProduto = $valores[0]['Codigo'];
        $this->view->grade = $valores[0]['Grade'];
        $this->view->dscProduto = $valores[0]['Produto'];
        $this->view->endPulmao = $valores[0]['Pulmao'];
        $this->view->idEnderecoPulmao = $valores[0]['idPulmao'];
        $this->view->endPicking = $valores[0]['Picking'];
        $this->view->temLote = $temLote;
        $this->view->qtd = $arrayQtds;
    }

    public function finalizarAction()
    {
        $codigoBarrasUMA = $this->_getParam('codigoBarrasUma');
        $etiquetaProduto = $this->_getParam('etiquetaProduto');
        $qtd = $this->_getParam('qtd');
        $idOnda = $this->_getParam('idOnda');
        $urlRedirect = '/mobile/onda-ressuprimento/listar-ondas';
        $ondaOsEn = null;
        $controlaRetornoRessup = $this->getSystemParameterValue("CONTROLA_RETORNO_RESSUPRIMENTO");

        try {
            $this->getEntityManager()->beginTransaction();

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");
            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
            $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");

            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
            $ondaOsEn = $ondaOsRepo->findOneBy(array('id'=>$idOnda));

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $ondaOsEn->getEndereco();

            if ($ondaOsEn->getStatus()->getId() == \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_FINALIZADO)
                throw new \Exception("A onda neste endereço " . $enderecoEn->getDescricao() . ' já foi atendida!');

            $result = null;
            if ($codigoBarrasUMA)
            {
                $urlRedirect =  '/mobile/onda-ressuprimento/selecionar-uma/idOnda/'. $idOnda;
                $codigoBarrasUMA = ColetorUtil::retiraDigitoIdentificador($codigoBarrasUMA);

                $result = $estoqueRepo->getProdutoByUMA($codigoBarrasUMA, $enderecoEn->getId());
                if ($result == NULL) {
                    throw new \Exception("UMA $codigoBarrasUMA Não encontrada neste endereço");
                }
            }

            if ($etiquetaProduto)
            {
                $urlRedirect =  '/mobile/onda-ressuprimento/selecionar-produto/idOnda/' . $idOnda;
                $etiquetaProduto = ColetorUtil::adequaCodigoBarras($etiquetaProduto);

                $result = $estoqueRepo->getProdutoByCodBarrasAndEstoque($etiquetaProduto, $enderecoEn->getId());
                if ($result == NULL) {
                    throw new \Exception("Produto $etiquetaProduto não encontrado neste endereço");
                }
            }

            if ($result == null) {
                throw new \Exception("Ocorreu um erro tentando finalizar a OS");
            }

            $codProduto     = $result[0]['ID'];
            $grade          = $result[0]['GRADE'];
            $descricao      = $result[0]['DESCRICAO'];
            $enderecoOrigem = $result[0]['ENDERECO'];
            $qtd            = $result[0]['QTD'];
            $lote           = $result[0]['LOTE'];
            $produtosOnda   = $ondaOsEn->getProdutos();

            if (($codProduto != $produtosOnda[0]->getProduto()->getId()) || ($grade != $produtosOnda[0]->getProduto()->getGrade())){
                throw new \Exception("Produto diferente do indicado na onda");
            }

            if($controlaRetornoRessup == 'S') {
                $qtd_ressuprimento = $ondaRepo->getQtdProdutoRessuprimento($idOnda, $codProduto, $grade);
                $qtd_ressuprimento = 6;

                if (empty($qtd_ressuprimento)) {
                    throw new \Exception("Ressuprimento não encontrado.");
                }
            }

            // verifica existencia de residuo no endereço de pulmao
            if( ($controlaRetornoRessup == 'S') && (!empty($qtd_ressuprimento)) && ($qtd - $qtd_ressuprimento > 0)){
                $urlRedirect = '/mobile/onda-ressuprimento/retorno-ressuprimento/idOnda/' . $idOnda. '/codProduto/'.$codProduto.'/grade/'.$grade.'/qtd/'.$qtd_ressuprimento.'/lote/'.$lote.'/enderecoOrigem/'.$enderecoOrigem.'/descricao/'.$descricao;
                $this->_redirect($urlRedirect);
            }
            else
            {
                $ondaRepo->finalizaOnda($ondaOsEn);
                $this->getEntityManager()->commit();
                $urlRedirect = '/mobile/onda-ressuprimento/listar-ondas';

                $this->addFlashMessage("success", "Os Finalizada com sucesso");
            }

        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage("error","Falha finalizando a OS ". $ondaOsEn->getOs()->getId() ." - " .$e->getMessage() );
        }

        $this->_redirect($urlRedirect);
    }

    public function retornoRessuprimentoAction(){

        try {
            $this->getEntityManager()->beginTransaction();

            $idOnda         = $this->_getParam("idOnda");
            $codProduto     = $this->_getParam("codProduto");
            $grade          = $this->_getParam("grade");
            $lote           = $this->_getParam("lote");
            $descricao      = $this->_getParam("descricao");
            $enderecoOrigem = $this->_getParam("enderecoOrigem");
            $qtd            = $this->_getParam("qtd");

            $this->view->idOnda         = $idOnda;
            $this->view->codProduto     = $codProduto;
            $this->view->grade          = $grade;
            $this->view->lote           = $lote;
            $this->view->descricao      = $descricao;
            $this->view->enderecoOrigem = $enderecoOrigem;
            $this->view->qtd            = $qtd;

            if( isset($_POST['enderecoDestino']) && !empty($_POST['enderecoDestino']) && isset($_POST['nivel']) && !empty($_POST['nivel'])) {

                $em = $this->getEntityManager();
                $codigoBarras = ColetorUtil::retiraDigitoIdentificador($_POST['enderecoDestino']);
                $enderecoDestino = EnderecoUtil::formatar($codigoBarras, null, null, $_POST['nivel']);


                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

                /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                $enderecoOrigemEn  = $enderecoRepo->findOneBy(array('descricao' => $enderecoOrigem));
                $enderecoDestinoEn = $enderecoRepo->findOneBy(array('descricao' => $enderecoDestino));
                if (empty($enderecoDestinoEn)) {
                    throw new Exception("Endereço não encontrado");
                }

                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaOsRepo */
                $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");
                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
                $ondaOsEn = $ondaOsRepo->findOneBy(array('id'=>$idOnda));


                /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
                $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');
                $os = $ordemServicoRepo->saveRetornoRessuprimento();

                // Criar um registro na tabela RETORNO_RESSUPRIMENTO
                /** @var \Wms\Domain\Entity\Ressuprimento\RetornoRessuprimento $retornoRessuprimento */
                $retornoRessuprimento = $this->em->getRepository('wms:Ressuprimento\RetornoRessuprimento');
                $retornoRessuprimento->setOndaRessuprimentoOs($idOnda);
                $retornoRessuprimento->setCodOs($os->getId());
                $retornoRessuprimento->setDepositoEndereco($enderecoDestinoEn->getId());
                $retornoRessuprimento->setDataMovimentacao(new DateTime('now'));

                $this->getEntityManager()->persist($retornoRessuprimento);
                $produtoVolume = '';

                $volumeEn = $em->getRepository('wms:Produto\Volume')->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade));
                if (isset($volumeEn) && !empty($volumeEn)) {
                    $produtoVolume = $volumeEn->getId();
                }

                $produtoEmbalagem = '';
                $embalagemEn = $em->getRepository('wms:Produto\Embalagem')->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade));
                if (isset($embalagemEn) && !empty($embalagemEn)) {
                    $produtoEmbalagem = $embalagemEn->getId();
                }

                // Criar registros na tabela RETORNO_RESSUPRIMENTO_PRODUTO
                /** @var \Wms\Domain\Entity\Ressuprimento\RetornoRessuprimentoProduto $retornoRessuprimentoProduto */
                $retornoRessuprimentoProduto = $this->em->getRepository('wms:Ressuprimento\RetornoRessuprimentoProduto');
                $retornoRessuprimentoProduto->setRetornoRessuprimento($retornoRessuprimento);
                $retornoRessuprimentoProduto->setProduto($codProduto);
                $retornoRessuprimentoProduto->setGrade($grade);
                $retornoRessuprimentoProduto->setProdutoEmbalagem($produtoEmbalagem);
                $retornoRessuprimentoProduto->setProdutoVolume($produtoVolume);
                $retornoRessuprimentoProduto->setQtd($qtd);
                $retornoRessuprimentoProduto->setLote($lote);

                $this->getEntityManager()->persist($retornoRessuprimentoProduto);

                // ressuprimento em outro endereço
                if( $_POST['enderecoOrigem'] != $enderecoDestino ){
                    /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                    $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');

                    $produtoEn = $em->getRepository('wms:Produto')->findOneBy(array('id' => $codProduto, 'grade' => $grade));

                    //saida
                    $params['endereco']    = $enderecoOrigemEn; // buscar o codigo do endereço
                    $params['produto']     = $produtoEn;
                    $params['qtd']         = $qtd; // buscar valor correto
                    $params['grade']       = $grade;
                    $params['volume']      = $volumeEn;
                    $params['lote']        = $lote;
                    $params['embalagem']   = $produtoEmbalagem;
                    $params['tipo']        = 'R';
                    $params['dthEntrada']  = new DateTime('now');
                    $params['os']          = $os->getId();
                    //$params['uma']         = $uma;
                    //$params['validade']    = $validade;

                    $estoqueRepo->movimentaEstoque($params, false, true);

                    //entrada
                    $params['endereco']    = $enderecoDestinoEn;
                    $params['produto']     = $produtoEn;
                    $params['qtd']         = $qtd;
                    $params['volume']      = $volumeEn;
                    $params['lote']        = $lote;
                    $params['embalagem']   = $produtoEmbalagem;
                    $params['tipo']        = 'R';
                    $params['dthEntrada']  = new DateTime('now');
                    $params['os']          = $os->getId();

                    $estoqueRepo->movimentaEstoque($params, false, false);
                }

                // finalizar onda
                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
                $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");

                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
                $ondaOsEn = $ondaOsRepo->findOneBy(array('id'=>$idOnda));

                $ondaRepo->finalizaOnda($ondaOsEn);

                //$this->getEntityManager()->flush();
                //$this->getEntityManager()->commit();

                $urlRedirect = '/mobile/onda-ressuprimento/listar-ondas';
                $this->addFlashMessage("success", "Os Finalizada com sucesso");
                $this->_redirect($urlRedirect);
            }
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
        }
    }


    public function divergenciaAction (){
        $urlRedirect = '/mobile/onda-ressuprimento/listar-ondas';
        $idOnda = $this->_getParam('idOnda');

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
        $ondaOsEn = $this->getEntityManager()->getReference("wms:Ressuprimento\OndaRessuprimentoOs",$idOnda);
        $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id'=>\Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_DIVERGENTE));
        $ondaOsEn->setStatus($statusEn);
        $this->getEntityManager()->persist($ondaOsEn);

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $andamentoRepo->save($idOnda, \Wms\Domain\Entity\Ressuprimento\Andamento::STATUS_DIVERGENTE);

        $this->getEntityManager()->flush();

        $this->addFlashMessage("success","O.S. $idOnda enviada para analise de estoque");
        $this->_redirect($urlRedirect);
    }

  }