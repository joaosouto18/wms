<?php
use Wms\Controller\Action,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Util\Coletor as ColetorUtil;

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
        $lotesEsperados = $this->_getParam('lotesEsperados');
        $idOnda = $this->_getParam('idOnda');
        $urlRedirect = '/mobile/onda-ressuprimento/listar-ondas';
        $ondaOsEn = null;

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

            $codProduto = $result[0]['ID'];
            $grade = $result[0]['GRADE'];
            $produtosOnda = $ondaOsEn->getProdutos();

            if (($codProduto != $produtosOnda[0]->getProduto()->getId()) || ($grade != $produtosOnda[0]->getProduto()->getGrade())){
                throw new \Exception("Produto diferente do indicado na onda");
            }

            $ondaRepo->finalizaOnda($ondaOsEn);

            $this->getEntityManager()->commit();
            $urlRedirect = '/mobile/onda-ressuprimento/listar-ondas';

            $this->addFlashMessage("success","Os Finalizada com sucesso");

        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage("error","Falha finalizando a OS ". $ondaOsEn->getOs()->getId() ." - " .$e->getMessage() );
        }

        $this->_redirect($urlRedirect);
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