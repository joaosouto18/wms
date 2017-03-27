<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page,
    Core\Util\Produto as ProdutoUtil;

class Enderecamento_MovimentacaoController extends Action
{

    public function indexAction()
    {
        $this->configurePage();
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $form = new \Wms\Module\Armazenagem\Form\Movimentacao\Cadastro();
        $form->init($utilizaGrade);
        $request = $this->getRequest();
        $data = $this->_getAllParams();
        $transferir = $this->_getParam('transferir');
        $quantidade = str_replace(',','.',$this->_getParam('quantidade'));

        //TRANSFERENCIA MANUAL
        if (isset($transferir) && !empty($transferir)) {
            $this->redirect('transferir', 'movimentacao', 'enderecamento', array('idProduto' => $data['idProduto'], 'grade' => $data['grade'],
                'embalagem' => $data['embalagem'], 'volumes' => $data['volumes'], 'rua' => $data['rua'], 'predio' => $data['predio'],
                'nivel' => $data['nivel'], 'apto' => $data['apto'], 'ruaDestino' => $data['ruaDestino'], 'predioDestino' => $data['predioDestino'],
                'nivelDestino' => $data['nivelDestino'], 'aptoDestino' => $data['aptoDestino'], 'validade' => $data['validade'], 'quantidade' => $quantidade));
        }

        if (isset($data['return'])) {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

            $idEndereco = $data['idEndereco'];
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('id'=>$idEndereco));
            $data['rua'] = $enderecoEn->getRua();
            $data['predio'] = $enderecoEn->getPredio();
            $data['nivel'] = $enderecoEn->getNivel();
            $data['apto'] = $enderecoEn->getApartamento();
            $form->populate($data);
        } else {
            if ($request->isPost() && empty($transferir)) {
                $this->redirect('movimentar', 'movimentacao', 'enderecamento', array(
                    'idProduto' => $data['idProduto'],
                    'grade' => $data['grade'],
                    'embalagem' => (isset($data['embalagem']) && !empty($data['embalagem']))? $data['embalagem'] : null,
                    'volumes' => (isset($data['volumes']) && !empty($data['volumes']))? $data['volumes'] : null,
                    'rua' => $data['rua'],
                    'predio' => $data['predio'],
                    'nivel' => $data['nivel'],
                    'apto' => $data['apto'],
                    'validade' => $data['validade'],
                    'quantidade' => $quantidade,
                    'idNormaPaletizacao' => $data['idNormaPaletizacao']));
            }
        }
        $this->view->form = $form;
    }


    public function movimentarAction()
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
        $EstoqueRepository   = $this->_em->getRepository('wms:Enderecamento\Estoque');
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
        $data = $this->_getAllParams();
        $form = new \Wms\Module\Armazenagem\Form\Movimentacao\Cadastro();
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $form->init($utilizaGrade);
        $request = $this->getRequest();

        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('rua' => $data['rua'], 'predio' => $data['predio'], 'nivel' => $data['nivel'], 'apartamento' => $data['apto']));
            if (empty($enderecoEn)){
                $this->addFlashMessage('error',"Endereço $data[rua].$data[predio].$data[nivel].$data[apto] não encontrado");
                $this->_redirect('/enderecamento/movimentacao');
            }

            /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
            $estoqueEn = $EstoqueRepository->findOneBy(array('depositoEndereco' => $enderecoEn->getId(),
                'codProduto' => $data['idProduto'], 'grade' => $data['grade']));

            //é uma entrada de estoque? Saída não precisa informar o unitizador
            $entradaEstoque = ($data['quantidade'] > 0);

            $unitizadorEn = null;
            $unitizadorEstoque = null;
            if ($estoqueEn != null) {
                $unitizadorEstoque = $estoqueEn->getUnitizador();
            }
            if (!isset($data['idNormaPaletizacao']) || ($data['idNormaPaletizacao'] == NULL && $unitizadorEstoque == NULL && $entradaEstoque)) {
                $this->addFlashMessage('error','É necessário informar o Unitizador!');
                $this->_redirect('/enderecamento/movimentacao');
            } else if (isset($data['idNormaPaletizacao']) && $data['idNormaPaletizacao'] != NULL && $entradaEstoque) {
                $idUnitizador = $data['idNormaPaletizacao'];
                $unitizadorRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador");
                $unitizadorEn = $unitizadorRepo->findOneBy(array('id'=>$idUnitizador));
            }

            $grade = trim($data['grade']);
            if ($data['grade'] == '')
                $data['grade'] = "UNICA";

            $idProduto = trim($data['idProduto']);
            $produtoEn = $this->getEntityManager()->getRepository("wms:Produto")->findOneBy(array('id'=>$idProduto, 'grade'=>$grade));

            if ($produtoEn == null) {
                $this->addFlashMessage('error',"Nenhum produto encontrado com o código '$idProduto' e grade '$grade'");
                $this->_redirect('/enderecamento/movimentacao');
            }

            $params = array();
            $params['produto'] = $produtoEn;
            $params['endereco'] = $enderecoEn;
            $params['qtd'] =  $data['quantidade'];
            $params['observacoes'] = 'Movimentação manual';
            $params['tipo'] = 'M';
            $params['unitizador'] = $unitizadorEn;

            $params['validade'] = null;
            if ($produtoEn->getValidade() == 'S' ) {
                if (isset($data['validade']) && !empty($data['validade'])) {
                    $params['validade'] = $data['validade'];
                } elseif (!empty($estoqueEn)) {
                    $validade = $estoqueEn->getValidade();
                    if (!empty($validade)) {
                        $params['validade'] = $validade->format('d/m/Y');
                    }
                }
            }
            if (isset($params['validade']) && !empty($params['validade'])) {
                $hoje = new DateTime();
                if (date_create_from_format('d/m/Y', $params['validade']) <= $hoje) {
                    $this->addFlashMessage('error',"Data de Validade deve ser maior que ". $hoje->format('d/m/Y'));
                    $this->_redirect('/enderecamento/movimentacao');
                }
            }

            if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                $embalagensEn = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findBy(array('codProduto'=>$idProduto,'grade'=>$grade),array('quantidade'=>'ASC'));
                if (count($embalagensEn) == 0) {
                    $this->addFlashMessage('error','Este produto não possui nenhuma embalagem cadastrada.');
                    $this->_redirect('/enderecamento/movimentacao');
                }
                $params['embalagem'] = $embalagensEn[0];

                $EstoqueRepository->movimentaEstoque($params, true, true);
            } else {
                if (isset($data['volumes']) && !empty($data['volumes'] )) {
                    $volumes = $this->getEntityManager()->getRepository ("wms:Produto\Volume")->getVolumesByNorma($data['volumes'],$idProduto,$grade);
                    if (count($volumes) <= 0) {
                        $this->addFlashMessage('error',"Não foi encontrado nenhum volume para o produto $idProduto - $grade no grupo de volumes selecionado. Nenhuma movimentação foi efetuada");
                        $this->_redirect('/enderecamento/movimentacao');
                    }
                    foreach ($volumes as $volume) {
                        $params['volume'] = $volume;
                        $EstoqueRepository->movimentaEstoque($params, true, true);
                    }
                } else {
                    $this->addFlashMessage('error','Selecione um grupo de volumes');
                    $this->_redirect('/enderecamento/movimentacao');
                }
            }

            $this->getEntityManager()->commit();

            $link = '/enderecamento/movimentacao/imprimir/endereco/'. $enderecoEn->getDescricao() .'/qtd/'.$data['quantidade'].'/idProduto/'.$data['idProduto'].'/grade/'.urlencode($data['grade']);
            if($request->isXmlHttpRequest()) {
                if ($data['quantidade'] > 0) {
                    echo $this->_helper->json(array('status' => 'success', 'msg' => 'Movimentação realizada com sucesso', 'link' => $link));
                } else {
                    echo $this->_helper->json(array('status' => 'success', 'msg' => 'Movimentação realizada com sucesso'));
                }
            } else {
                $msg = "Movimentação realizada com sucesso";
                if ($data['quantidade'] >0) {
                    $msg .= ' - <a href="'.$link.'" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir UMA</a>';
                }
                $this->addFlashMessage('success',$msg);

                $this->_redirect('/enderecamento/movimentacao/');
                $form->populate($data);
            }
        } catch(Exception $e) {
            $this->getEntityManager()->rollback();
            if($request->isXmlHttpRequest()) {
                echo $this->_helper->json(array('status' => 'error', 'msg' =>  $e->getMessage()));
            } else {
                $this->addFlashMessage('error', $e->getMessage());
                $form->populate($data);
            }
            $this->_redirect('/enderecamento/movimentacao');
        }

    }

    public function transferirAction()
    {
        $data = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

        try {
            $this->getEntityManager()->beginTransaction();
            $grade = trim($data['grade']);
            if ($grade == '')
                $grade = $data['grade'] = "UNICA";

            $idProduto = trim($data['idProduto']);
            /** @var \Wms\Domain\Entity\Produto $produtoEn */
            $produtoEn = $data['produto'] = $this->getEntityManager()->getRepository("wms:Produto")->findOneBy(array('id' => $idProduto, 'grade' => $grade));
            if (empty($produtoEn))
                throw new Exception("O código $idProduto está errado ou não foi cadastrado como produto");

            $data['embalagem'] = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findOneBy(array('codProduto' => $idProduto, 'grade' => $grade));

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('rua' => $data['rua'], 'predio' => $data['predio'], 'nivel' => $data['nivel'], 'apartamento' => $data['apto']));
            if (empty($enderecoEn))
                throw new \Exception("Endereço $data[rua].$data[predio].$data[nivel].$data[apto] de origem não foi encontrado");

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoDestinoEn = $enderecoRepo->findOneBy(array('rua' => $data['ruaDestino'], 'predio' => $data['predioDestino'], 'nivel' => $data['nivelDestino'], 'apartamento' => $data['aptoDestino']));
            if (empty($enderecoDestinoEn))
                throw new \Exception("Endereço $data[ruaDestino].$data[predioDestino].$data[nivelDestino].$data[aptoDestino] de destino não foi encontrado");

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoque');
            $verificaReservaSaida = $reservaEstoqueRepo->findBy(array('endereco' => $enderecoEn, 'tipoReserva' => 'S', 'atendida' => 'N'));

            if (count($verificaReservaSaida) > 0) {
                $this->addFlashMessage('error','Existe Reserva de Saída para esse endereço que ainda não foi atendida!');
                $this->_redirect('/enderecamento/movimentacao');
            }


            if (isset($data['embalagem']) && !empty($data['embalagem'])) {
                /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
                $estoqueEn = $estoqueRepo->findOneBy(array('codProduto' => $idProduto, 'grade' => $grade, 'depositoEndereco' => $enderecoEn));
                if (empty($estoqueEn)) {
                    $this->addFlashMessage('error','Não existe estoque deste produto neste endereço!');
                    $this->_redirect('/enderecamento/movimentacao');
                }

                //SAIDA DO ENDEREÇO DE ORIGEM
                $data['endereco'] = $enderecoEn;
                $data['qtd'] = $data['quantidade'] * -1;
                $data['observacoes'] = "Transferencia de Estoque - Destino: ".$enderecoDestinoEn->getDescricao();
                $estoqueRepo->movimentaEstoque($data);

                //ENTRADA NO ENDEREÇO DE DESTINO
                $data['endereco'] = $enderecoRepo->findOneBy(array('rua' => $data['ruaDestino'], 'predio' => $data['predioDestino'], 'nivel' => $data['nivelDestino'], 'apartamento' => $data['aptoDestino']));
                /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueDestino */
                $estoqueDestino = $estoqueRepo->findOneBy(array('codProduto' => $idProduto, 'grade' => $grade, 'depositoEndereco' => $data['endereco']));

                if (empty($estoqueDestino))
                    $data['uma'] = $estoqueEn->getUma();

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
                        $umaOrigem = null;
                        if (isset($estoqueEn) && !empty($estoqueEn)) {
                            $estoqueUma = $estoqueEn->getUma();
                            if (isset($estoqueUma) && !empty($estoqueUma)) {
                                $umaOrigem = $this->em->find('wms:Enderecamento\Palete', $estoqueEn->getUma());
                            }
                        }

                        $umaDestino = null;
                        if (isset($estoqueDestino) && !empty($estoqueDestino)) {
                            $estoqueDestinoUma = $estoqueDestino->getUma();
                            if (isset($estoqueDestinoUma) && !empty($estoqueDestinoUma)) {
                                $umaDestino = $this->em->find('wms:Enderecamento\Palete', $estoqueDestino->getUma());
                            }
                        }

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
                        $data['validade'] = $validade->format('d/m/Y');
                    }
                }

                $data['qtd'] = $data['quantidade'];
                $data['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoEn->getDescricao();
                $estoqueRepo->movimentaEstoque($data);
            } else if (isset($data['volumes']) && ($data['volumes'] != "")) {
                $volumes = $this->getEntityManager()->getRepository("wms:Produto\Volume")->getVolumesByNorma($data['volumes'],$idProduto,$grade);
                if (count($volumes) <= 0) {
                    $this->addFlashMessage('error',"Não foi encontrado nenhum volume para o produto $idProduto - $grade no grupo de volumes selecionado. Nenhuma movimentação foi efetuada");
                    $this->_redirect('/enderecamento/movimentacao');
                }
                foreach ($volumes as $volume) {
                    $data['endereco'] = $enderecoEn;
                    $data['qtd'] = $data['quantidade'] * -1;
                    $data['volume'] = $volume;
                    $data['observacoes'] = "Transferencia de Estoque - Destino: ".$enderecoDestinoEn->getDescricao();
                    $estoqueRepo->movimentaEstoque($data);
                    $data['endereco'] = $enderecoRepo->findOneBy(array('rua' => $data['ruaDestino'], 'predio' => $data['predioDestino'], 'nivel' => $data['nivelDestino'], 'apartamento' => $data['aptoDestino']));
                    $data['qtd'] = $data['quantidade'];
                    $data['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoEn->getDescricao();
                    $estoqueRepo->movimentaEstoque($data);
                }
            }
            $this->getEntityManager()->commit();
            $this->addFlashMessage('success','Endereço alterado com sucesso!');
            $this->_redirect('/enderecamento/movimentacao');

        } catch(Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/enderecamento/movimentacao');
        }
    }

    public function configurePage()
    {
        $buttons[] = array(
            'label' => 'Limpar',
            'cssClass' => 'button limparMovimentacao',
            'urlParams' => array(
                'module' => '',
                'controller' => '',
                'action' => '',
            ),
            'tag' => 'a'
        );
        $buttons[] = array(
            'label' => 'Exportar Saldo csv',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'movimentacao',
                'action' => 'saldo',
            ),
            'tag' => 'a'
        );
        $buttons[] = array(
            'label' => 'Endereços Disponíveis',
            'cssClass' => 'dialogAjax selecionar-endereco',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'endereco',
                'action' => 'filtrar',
                'origin' => 'movimentacao'
            ),
            'tag' => 'a'
        );
        Page::configure(array('buttons' => $buttons));
    }

    /**
     * Realiza o filtro de um produto trazendo as grades do mesmo
     */
    public function filtrarAction()
    {
        $codProduto = $this->_getParam('idproduto');
        if (!isset($codProduto) || empty($codProduto)) {
            echo $this->_helper->json(false);
        }
        /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
        $ProdutoRepository   = $this->em->getRepository('wms:Produto');
        $grades = $ProdutoRepository->buscaGradesProduto($codProduto);
        if ($grades != null) {
            echo $this->_helper->json($grades);
        }
        echo $this->_helper->json(false);
    }

    public function volumesAction() {
        $codProduto = $this->_getParam('idproduto');
        $grade = $this->_getParam('grade');
        $grade = trim($grade);
        $codProduto = trim($codProduto);
        if ($grade == "") {
            $grade = "UNICA";
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('np.id')
            ->from('wms:Produto\Volume','pv')
            ->leftJoin("pv.normaPaletizacao",'np')
            ->where('pv.codProduto = :codProduto')
            ->andWhere("pv.grade = '$grade'")
            ->setParameter('codProduto',ProdutoUtil::formatar($codProduto))
            ->distinct(true);

        $volumes = $queryBuilder->getQuery()->getArrayResult();

        $grupos = array();
        foreach($volumes as $key => $volume) {
            $prodVol = $this->getEntityManager()->getRepository("wms:Produto\Volume")->findBy(array('normaPaletizacao'=>$volume));
            $strVols = "";
            foreach ($prodVol as $vol) {
                if ($strVols != "") {$strVols.= "; ";}
                $strVols .= $vol->getDescricao();
            }

            $grupo = array();
            $grupo['cod'] = $volume['id'];
            $grupo['descricao'] = 'GRUPO ' . ($key +1) . " : " . $strVols;
            $grupos[] = $grupo;
        }

        if (count($grupos)>0){
            echo $this->_helper->json($grupos);
        }else {
            echo $this->_helper->json(false);
        }
    }

    public function getValidadeAction()
    {
        $codProduto = $this->_getParam('idProduto');
        $grade = $this->_getParam('grade');
        $grade = trim($grade);
        $codProduto = trim($codProduto);
        if ($grade == "") {
            $grade = "UNICA";
        }

        $produtoEn = $this->getEntityManager()->getRepository("wms:Produto")->findOneBy(array('id' => "$codProduto", 'grade' => "$grade"));

        if (isset($produtoEn)) {
            $validade = $produtoEn->getValidade();
            echo $this->_helper->json($validade);
        } else {
            return $this->_helper->json(false);
        }


    }


    /**
     * Traz o resumo de estoque pelo produto ou rua
     */
    public function listAction()
    {
        $params     = $this->_getAllParams();
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepo */
        $EstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $enderecos = $EstoqueRepo->getEstoqueAndVolumeByParams($params);
        /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
        $ProdutoRepository   = $this->_em->getRepository('wms:Produto');
        $produtoEn  = $ProdutoRepository->findOneBy(array('id' => ProdutoUtil::formatar($params['idProduto']), 'grade' => $params['grade']));
        $endPicking = $ProdutoRepository->getEnderecoPicking($produtoEn);

        $this->view->endPicking = $endPicking;
        $this->view->enderecos = $enderecos;
    }

    public function saldoAction()
    {

        $params = $this->_getAllParams();

        if ((isset($params['tipo'])) && ($params['tipo'] == 'C')) {
            /** @var \Wms\Domain\Entity\Enderecamento\VSaldoRepository $SaldoRepository */
            $SaldoCompletoRepository   = $this->_em->getRepository('wms:Enderecamento\VSaldoCompleto');
            $saldo = $SaldoCompletoRepository->saldo($this->_getAllParams());
        } else {
            /** @var \Wms\Domain\Entity\Enderecamento\VSaldoRepository $SaldoRepository */
            $SaldoRepository   = $this->_em->getRepository('wms:Enderecamento\VSaldo');
            $saldo = $SaldoRepository->saldo($this->_getAllParams());
        }

        $file = '';

        foreach($saldo as $produto) {
            $linha = $produto['codProduto'].';'.$produto['grade'].';'.$produto['dscLinhaSeparacao'].';'.$produto['qtd'].';'.$produto['dscEndereco'].';'.$produto['unitizador'].';'.$produto['descricao'].';'.$produto['volume'].';'.utf8_decode($produto['tipoComercializacao']).';'.$produto['quantidade'];
            $file .= $linha . PHP_EOL;
            unset($linha);
        }

        header('Content-Type: application/csv');
        header('Content-disposition: attachment; filename=saldo-estoque.csv');

        echo $file;
        exit;
    }

    public function imprimirAction() {
        $idProduto = $this->_getParam("idProduto");
        $grade = $this->_getParam("grade");
        $dscEndereco = $this->_getParam("endereco");
        $quantidade = $this->_getParam("qtd");

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
        $EstoqueRepository   = $this->_em->getRepository('wms:Enderecamento\Estoque');
        $EstoqueRepository->imprimeMovimentacaoAvulsa($idProduto ,$grade,$quantidade,$dscEndereco);
    }

    public function consultarAction() {
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo   = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoque');
        $reservas = $reservaEstoqueRepo->getResumoReservasNaoAtendidasByParams($this->_getAllParams());

        $this->view->reservas = $reservas;

        $idVolume = $this->_getParam('idVolume');
        $idProduto = $this->_getParam('idProduto');
        $grade = $this->_getParam('grade');
        $idEndereco = $this->_getParam('idEndereco');

        if ($idVolume == "0") {
            $this->view->volume = "PRODUTO UNITÁRIO";
        } else {
            $volumeEn = $this->getEntityManager()->getReference("wms:Produto\Volume",$idVolume);
            $this->view->volume = $volumeEn->getDescricao();
        }

        $produtoEn = $this->getEntityManager()->getRepository("wms:Produto")->findOneBy(array('id'=>$idProduto,'grade'=>$grade));
        $this->view->idProduto = $idProduto;
        $this->view->grade = $grade;
        $this->view->produto = $produtoEn->getDescricao();

        $enderecoEn = $this->getEntityManager()->getReference("wms:Deposito\Endereco",$idEndereco);
        $this->view->endereco = $enderecoEn->getDescricao();
    }

    public function consultarProdutoAction()
    {
        $idProduto = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $result = $produtoRepo->verificaSeEProdutoComposto($idProduto);

        echo $this->_helper->json($result);
    }

}