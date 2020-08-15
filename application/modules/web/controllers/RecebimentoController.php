<?php

use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\Recebimento\Andamento,
    Wms\Domain\Entity\OrdemServico,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity,
    Wms\Domain\Entity\Produto as ProdutoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Report\Recebimento\DadosLogisticosProduto as RelatorioDadosLogisticosProduto,
    Wms\Module\Web\Form\OrdemServico as OrdemServicoForm,
    Wms\Module\Web\Form\Recebimento\ObservacaoAndamento as ObservacaoAndamentoForm,
    Wms\Module\Web\Form\Recebimento as RecebimentoForm,
    Wms\Module\Web\Form\Subform\FiltroRecebimentoMercadoria,
    Wms\Module\Web\Form\Subform\FiltroNotaFiscal as FiltroNotaFiscalForm,
    Wms\Module\Web\Grid\Recebimento as RecebimentoGrid,
    Wms\Module\Web\Grid\Recebimento\Andamento as AndamentoGrid,
    Wms\Module\Web\Grid\Recebimento\ModeloRecebimento as ModeloRecebimentoGrid,
    Wms\Module\Web\Form\Recebimento\ModeloRecebimento as ModeloRecebimentoForm,
    Wms\Domain\Entity\Recebimento\ModeloRecebimento as ModeloRecebimentoEn,
    Wms\Module\Web\Grid\Recebimento\Conferencia as ConferenciaGrid;

/**
 * Description of Web_RecebimentoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_RecebimentoController extends \Wms\Controller\Action {

    protected $repository = 'Recebimento';

    public function indexAction() {
        $form = new FiltroRecebimentoMercadoria;

        $values = $form->getParams();
        $parametroNotasFiscais = $this->getSystemParameterValue('COD_INTEGRACAO_NOTAS_FISCAIS');

        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Importar Notas Fiscais ERP',
                    'cssClass' => 'btnSave',
                    'urlParams' => array(
                        'module' => 'importacao',
                        'controller' => 'gerenciamento',
                        'action' => 'index',
                        'id' => $parametroNotasFiscais
                    ),
                    'tag' => 'a'
                )
            )
        ));

        //Caso nao seja preenchido nenhum filtro preenche automaticamente com a data inicial de ontem e de hoje
        if (!$values) {

            $dataI1 = new \DateTime;
            $dataI1->modify('-1 day');
            $dataI2 = new \DateTime();

            $values = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI2->format('d/m/Y'),
                'dataFinal1' => '',
                'dataFinal2' => '',
                'uma' => '',
                'idRecebimento' => ''
            );
        } else {
            if ($values['idRecebimento'] || $values['uma']) {
                $values['dataInicial1'] = null;
                $values['dataInicial2'] = null;
                $values['dataFinal1'] = null;
                $values['dataFinal2'] = null;
            }
        }

        // grid
        $grid = new RecebimentoGrid;
        $this->view->grid = $grid->init($values)
                ->render();
        // form
        $this->view->form = $form->setSession($values)
                ->populate($values);
    }

    public function excluirNotaAction() {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $idNf = $this->_getParam('id');
            $nfRepo = $em->getRepository("wms:NotaFiscal");
            /** @var \Wms\Domain\Entity\NotaFiscal $nf */
            $nf = $nfRepo->findOneBy(array('id' => $idNf));
            if ($nf == null)
                throw new \Exception('Nota Fiscal não encontrado');

            $itensNf = $nf->getItens();
            foreach ($itensNf as $itemNf) {
                $em->remove($itemNf);
            }

            $em->remove($nf);
            $em->commit();
            $em->flush();

            $this->addFlashMessage('success', 'Nota Fiscal excluida com sucesso');
            $this->redirect('buscar-nota');
        } catch (\Exception $e) {
            $em->rollback();

            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('buscar-nota');
        }
    }

    /**
     * Iniciar Recebimento
     */
    public function iniciarAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
            )
        ));

        $params = $this->getRequest()->getParams();

        //edita o recebimento para o status iniciado com o box de origem para descarga  
        try {
            //Recupera o id do recebimento
            $idRecebimento = $params['id'];

            if (empty($idRecebimento))
                throw new \Exception('O recebimento não foi informado.');

            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

            /** @var \Wms\Domain\Entity\Recebimento $recebimentoEntity */
            $recebimentoEntity = $recebimentoRepo->find($idRecebimento);

            $sessao = new \Zend_Session_Namespace('deposito');
            $idDeposito = $sessao->idDepositoLogado;

            if ($recebimentoEntity->getDeposito()->getId() != $idDeposito) throw new Exception("Esse recebimento $idRecebimento não pertence à esse depósito");

            //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
            $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimentoEntity->getId()));

            if ($notaFiscalEntity)
                $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

            // status de recebimento
            $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimentoEntity);
            $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimentoEntity->getStatus()->getReferencia());

            $this->view->recebimento = $recebimentoEntity;

            $form = new RecebimentoForm;

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $recebimentoRepo->save($recebimentoEntity, $params);

                $this->_helper->messenger('success', 'Recebimento iniciado com sucesso');
                return $this->redirect('conferencia-cega', null, null, array('id' => $idRecebimento));
            }

            $form->setDefaultsFromEntity($recebimentoEntity);
            $this->view->form = $form;
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    /**
     * Cancelar Recebimento
     */
    public function deleteAction() {
        //Recupera o id do recebimento
        extract($this->getRequest()->getParams());

        try {
            if ($idRecebimento == null)
                throw new \Exception('ID inválido');

            $recebimento = $this->em->getReference('wms:Recebimento', $idRecebimento);

            if ($recebimento == null)
                throw new \Exception('Recebimento não encontrado');

            $this->em->getRepository('wms:Recebimento')->cancelar($recebimento, 'Recebimento Cancelado');

            $this->addFlashMessage('success', 'Recebimento cancelado com sucesso');
            $this->redirect('index');
        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    /**
     * Cancelar Recebimento
     */
    public function desfazerAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
            )
        ));

        //Recupera o id do recebimento
        extract($this->getRequest()->getParams());

        $recebimento = $this->em->find('wms:Recebimento', $id);
        $this->view->recebimento = $recebimento;

        // status de recebimento
        $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimento);
        $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimento->getStatus()->getReferencia());

        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimento->getId()));

        if ($notaFiscalEntity)
            $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

        $formObservacao = new ObservacaoAndamentoForm;
        $formObservacao->getElement('idRecebimento')->setValue($id);
        $formObservacao->getElement('btnSubmit')->setLabel('Desfazer Recebimento');
        $formObservacao->setAction($this->view->url(array('controller' => 'recebimento', 'action' => 'desfazer')));

        try {
            if ($this->getRequest()->isPost()) {

                if ($idRecebimento == null)
                    throw new \Exception('ID inválido');

                $recebimento = $this->em->getReference('wms:Recebimento', $idRecebimento);

                if ($recebimento == null)
                    throw new \Exception('Recebimento não encontrado');

                $cancelarPaletesParam = $this->getSystemParameterValue('CANCELA_PALETES_DESFAZER_RECEBIMENTO');
                if ($cancelarPaletesParam == "S") {
                    /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
                    $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");
                    $paletesEn = $paleteRepo->findBy(array('recebimento' => $idRecebimento));
                    foreach ($paletesEn as $paleteEn) {
                        $paleteRepo->cancelaPalete($paleteEn);
                    }
                }

                $this->em->getRepository('wms:Recebimento')->desfazer($recebimento, $_POST['descricao']);
                $this->addFlashMessage('success', 'Recebimento desfeito com sucesso');
                $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
        }

        $this->view->form = $formObservacao;
        // grid
        $grid = new AndamentoGrid;
        $this->view->grid = $grid->init(array('idRecebimento' => $id))
                ->render();
    }

    /**
     * Finalizar Recebimento
     */
    public function finalizarAction() {
        //Recupera o id do recebimento
        $idRecebimento = $this->getRequest()->getParam('id');

        $recebimento = $this->em->find('wms:Recebimento', $idRecebimento);

        if ($idRecebimento == null)
            throw new \Exception('ID Inválido');

        if ($recebimento == null)
            throw new \Exception('Recebimento inválido');

        //edita o recebimento para o status finalizado
        $result = $this->em->getRepository('wms:Recebimento')->finalizar($recebimento);

        if ($result['concluido'] == true) {
            $this->addFlashMessage('success', 'Recebimento finalizado com sucesso');
            $this->redirect('index');
        } else {
            throw $result['exception'];
        }
    }

    /**
     * Conferencia (salva o produto e a quantidade conferida do recebimento)
     */
    public function conferenciaAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index'
                    ),
                    'tag' => 'a'
                ),
            ),
        ));

        $temTransacao = false;
        try {
            //Recuperando o id da ordem servico
            $params = $this->getRequest()->getParams();
            $idOrdemServico = ($this->_hasParam('id')) ? $params['id'] : $params['idOrdemServico'];

            // repositories
            $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');
            $conferenciaRepo = $this->em->getRepository('wms:Recebimento\Conferencia');

            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

            /** @var \Wms\Domain\Entity\Pessoa\Fisica\ConferenteRepository $conferenteRepo */
            $conferenteRepo = $this->em->getRepository('wms:Pessoa\Fisica\Conferente');

            // checo se há conferencia cadastrada
            $conferenciaEntity = $conferenciaRepo->findOneBy(array('ordemServico' => $idOrdemServico));

            if ($conferenciaEntity)
                $this->redirect('divergencia', 'recebimento', null, array('id' => $idOrdemServico));

            $conferentes = $conferenteRepo->getIdValue();
            if (empty($conferentes))
                throw new Exception("Não há nenhum conferente cadastrado!");

            $ordemServicoEntity = $ordemServicoRepo->find($idOrdemServico);

            //recebimento
            $recebimentoEntity = $ordemServicoEntity->getRecebimento();
            $idRecebimento = $ordemServicoEntity->getRecebimento()->getId();

            // status de recebimento
            $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimentoEntity);
            $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimentoEntity->getStatus()->getReferencia());

            //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
            $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));

            if ($notaFiscalEntity)
                $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

            // view recebimento
            $this->view->recebimento = $recebimentoEntity;
            // conferente
            $this->view->conferentes = $conferentes;
            //produtos
            $itensConferir = $notaFiscalRepo->getItemConferencia($idRecebimento);

            $temFracionavel = false;
            $controlaValidade = false;
            $temLote = false;

            /** @var ProdutoEntity\NormaPaletizacaoRepository $normaRepo */
            $normaRepo = $this->em->getRepository('wms:Produto\NormaPaletizacao');
            foreach ($itensConferir as $key => $item) {
                if ($item['possui_validade'] == 'S') {
                    $controlaValidade = true;
                }
                if ($item['ind_controla_lote'] == 'S') {
                    $temLote = true;
                }
                if ($item['cod_tipo_comercializacao'] == \Wms\Domain\Entity\Produto::TIPO_UNITARIO) {
                    /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
                    $embalagemRepo = $this->em->getRepository('wms:Produto\Embalagem');

                    if ($item['ind_fracionavel'] == 'S') {
                        $temFracionavel = true;
                    }

                    $arrCriterio = [
                        'codProduto' => $item['codigo'],
                        'grade' => $item['grade'],
                        'dataInativacao' => null];

                    $result = $embalagemRepo->findBy($arrCriterio, array('quantidade' => 'ASC'));

                    if (empty($result))
                        throw new Exception("O produto $item[codigo] - $item[grade] não tem embalagen ativa");

                    $embalagens = array();
                    /** @var ProdutoEntity\Embalagem $embalagem */
                    foreach( $result as $embalagem ) {
                        $embalagens[] = [
                            "id" => $embalagem->getId(),
                            "isFracDefault" => $embalagem->isEmbFracionavelDefault(),
                            "dsc" => $embalagem->getDescricao()
                        ];
                    }

                    $itensConferir[$key]['embalagens'] = $embalagens;
                    $itensConferir[$key]['normas'] = $normaRepo->getNormasByProduto($item['codigo'],$item['grade']);
                } else {
                    //unidade Medida
                    $produtoRepo = $this->em->getRepository('wms:Produto');
                    $this->view->unMedida = $produtoRepo->getProdutoEmbalagem();
                }
            }

            $this->view->temFracionavel = $temFracionavel;
            $this->view->temLote = $temLote;
            $this->view->controlaValidade = $controlaValidade;
            $this->view->produtos = $itensConferir;

            //salvar produto e quantidade Conferencia
            if ($this->getRequest()->isPost()) {
                // checando quantidades
                $this->_em->beginTransaction();
                $temTransacao = true;

                $idConferente = $this->getRequest()->getParam('idPessoa');

                $arrMapGrade = json_decode($this->getRequest()->getParam('arrMapGrade'), true);
                $replaceKey = function ($arr, $arrayMap) {
                    foreach($arr as $id => $subArr) {
                        foreach ($subArr as $oldKey => $val) {
                            $arr[$id][$arrayMap[$oldKey]] = $val;
                            unset($arr[$id][$oldKey]);
                        }
                    }
                    return $arr;
                };

                $qtdConferidas = $this->getRequest()->getParam('qtdConferida');
                $qtdConferidas = $replaceKey($qtdConferidas, $arrMapGrade);

                $qtdUnidFracionavel = $this->getRequest()->getParam('qtdUnidFracionavel');
                $qtdUnidFracionavel = $replaceKey($qtdUnidFracionavel, $arrMapGrade);

                $embalagem = $this->getRequest()->getParam('embalagem');
                $embalagem = $replaceKey($embalagem, $arrMapGrade);

                $unMedida = $this->getRequest()->getParam('unMedida');
                $unMedida = $replaceKey($unMedida, $arrMapGrade);

                $dataValidade = $this->getRequest()->getParam('dataValidade');
                $dataValidade = $replaceKey($dataValidade, $arrMapGrade);

                $numPeso = $this->getRequest()->getParam('numPeso');
                $numPeso = $replaceKey($numPeso, $arrMapGrade);

                $normas = $this->getRequest()->getParam('norma');
                $normas = $replaceKey($normas, $arrMapGrade);
                // executa os dados da conferencia

                $recebimentoRepo->saveConferenciaCega($idRecebimento,$idOrdemServico,$qtdConferidas,$normas, $qtdUnidFracionavel,$embalagem, $unMedida, $dataValidade, $numPeso);
                $result = $recebimentoRepo->conferenciaColetor($idRecebimento, $idOrdemServico, $idConferente);

                if ($result['exception'] != null) {
                    throw $result['exception'];
                }
                if ($result['message'] != null) {
                    $this->addFlashMessage('success', $result['message']);
                }

                $this->em->flush();
                $this->em->commit();

                if ($result['concluido'] == true) {
                    $this->redirect('index');
                } else {
                    $this->redirect('divergencia', 'recebimento', null, array('id' => $idOrdemServico));
                }
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            if ($temTransacao) $this->em->rollback();
            $this->redirect('index');
        }
    }

    /**
     * Calcula valores cadastrados nas tabelas recebimento_embalagem e recebimento_volume
     * faz os devidos calculos e insere na tabela recebimento_conferencia 
     * Uma vez com os dados cadastrados redireciona para divergencia ou finalização
     * 
     */
    public function conferenciaColetorAjaxAction() {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);

        $this->em->beginTransaction();
        try {
            $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');

            $idOrdemServico = $this->getRequest()->getParam('idOrdemServico');
            $ordemServicoEntity = $ordemServicoRepo->find($idOrdemServico);
            $idRecebimento = $ordemServicoEntity->getRecebimento()->getId();

            /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $descargaRepo */
            $descargaRepo = $this->em->getRepository('wms:Recebimento\Descarga');
            if ($descargaRepo->realizarDescarga($idRecebimento) === true) {
                $this->redirect('index', 'descarga', 'produtividade', array('recebimento' => $idRecebimento, 'idOrdemServico' => $idOrdemServico));
            }

            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

            $result = $recebimentoRepo->conferenciaColetor($idRecebimento, $idOrdemServico);

            if ($result['exception'] != null) {
                throw $result['exception'];
            }

            if ($result['message'] != null) {
                $this->addFlashMessage('success', $result['message']);
            }

            $this->em->commit();

            if (!$result['concluido']) {
                $this->redirect('divergencia', 'recebimento', null, array('id' => $idOrdemServico));
            }
        } catch (Exception $e){
            $this->em->rollback();
            $this->addFlashMessage('error', $e->getMessage());
        }

        $this->redirect('index');
    }

    /**
     * 
     */
    public function divergenciaAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
            )
        ));

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);

        try {

            $params = $this->getRequest()->getParams();
            $idOrdemServico = $params['id'];

            // motivos de divergencia
            $motivosDivergencia = $this->em->getRepository('wms:Recebimento\Divergencia\Motivo')->getIdValue();

            $motivos[] = 'Selecione';
            foreach ($motivosDivergencia as $key => $motivo) {
                $motivos[$key] = $motivo;
            }
            $this->view->motivosDivergencia = $motivos;


            $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');
            $ordemServicoEntity = $ordemServicoRepo->find($idOrdemServico);
            //recebimento
            $recebimentoEntity = $ordemServicoEntity->getRecebimento();

            // status de recebimento
            $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimentoEntity);
            $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimentoEntity->getStatus()->getReferencia());

            $this->view->recebimento = $recebimentoEntity;
            //conferente
            $this->view->conferentes = $this->em->getRepository('wms:Pessoa\Fisica\Conferente')->getIdValue();

            //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
            $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimentoEntity->getId()));

            if ($notaFiscalEntity)
                $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

            /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
            $conferenciaRepo = $this->_em->getRepository('wms:Recebimento\Conferencia');
            $produtosDivergencia = $conferenciaRepo->getProdutoDivergencia($idOrdemServico);
            $this->view->lote = $conferenciaRepo->existeLoteRecebimento($recebimentoEntity->getId());
            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo = $this->_em->getRepository('wms:Produto');
            /** @var \Wms\Domain\Entity\Produto\PesoRepository $pesoRepo */
            $pesoProdutoRepo = $this->_em->getRepository('wms:Produto\Peso');

            $sumPesosRecebimentoProdutos = $conferenciaRepo->getSumPesoTotalRecebimentoProduto($recebimentoEntity->getId(), null, null, $ordemServicoEntity);

            //NAO EXIBE O BOTAO DE "Fechar Recebimento com Divergencia" CASO A DIVERGENCIA SEJA APENAS NO PESO
            $this->view->pesoDivergente = false;
            foreach ($sumPesosRecebimentoProdutos as $sumPesoRecebimento) {
                $produtoEn = $produtoRepo->findOneBy(array('id' => $sumPesoRecebimento['produto'], 'grade' => $sumPesoRecebimento['grade']));
                $tolerancia = str_replace(",", ".", $produtoEn->getToleranciaNominal());
                $pesoProduto = $pesoProdutoRepo->findOneBy(array('produto' => $sumPesoRecebimento['produto'], 'grade' => $sumPesoRecebimento['grade']));
                if (isset($pesoProduto) && !empty($pesoProduto)) {
                    $pesoUnitarioMargemS = (float) ($pesoProduto->getPeso() * $sumPesoRecebimento['qtdConferida']) + $tolerancia;
                    $pesoUnitarioMargemI = (float) ($pesoProduto->getPeso() * $sumPesoRecebimento['qtdConferida']) - $tolerancia;

                    if (!((float) $sumPesoRecebimento['numPeso'] <= $pesoUnitarioMargemS && (float) $sumPesoRecebimento['numPeso'] >= $pesoUnitarioMargemI)) {
                        $this->view->pesoDivergente = true;
                        break;
                    }
                }
            }

            $this->view->ordemServicoEntity = $ordemServicoEntity;

            // notas fiscais
            $notasFiscais = $recebimentoEntity->getNotasFiscais();

            for ($i = 0; $i < count($produtosDivergencia); $i++) {

                $produtosDivergencia[$i]['nfs'][] = 'Selecione';

                foreach ($notasFiscais as $notaFiscal) {
                    foreach ($notaFiscal->getItens() as $item) {

                        if (($produtosDivergencia[$i]['idProduto'] == $item->getProduto()->getId()) && ($produtosDivergencia[$i]['grade'] == $item->getGrade())) {
                            $produtosDivergencia[$i]['nfs'][$notaFiscal->getId()] = 'Nº ' . $notaFiscal->getNumero() . ' - Serie. ' . $notaFiscal->getSerie();
                        }
                    }
                }
            }
            $this->view->notasFiscais = $notasFiscais;

            $this->view->produtosConferencia = $produtosDivergencia;

            //salvar produto e quantidade Conferencia
            if ($this->getRequest()->isPost()) {

                $idRecebimento = $recebimentoEntity->getId();

                switch ($params['acaoFinalizacao']) {
                    case 'recontagem':

                        $this->em->beginTransaction();

                        try {
                            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
                            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
                            $checkOs = $recebimentoRepo->checarConferenciaComDivergencia($idRecebimento, false);

                            if ($checkOs['qtdConferencia'] > 0) {
                                $ordemServicoEntity->setDataFinal(new \DateTime());
                                $this->em->persist($ordemServicoEntity);
                                $ordemServicoRepo->save(new OrdemServicoEntity, array(
                                    'identificacao' => array(
                                        'idRecebimento' => $ordemServicoEntity->getRecebimento()->getId(),
                                        'idAtividade' => $ordemServicoEntity->getAtividade()->getId(),
                                        'formaConferencia' => $ordemServicoEntity->getFormaConferencia(),
                                        'idPessoa' => $ordemServicoEntity->getPessoa()->getId(),
                                    )
                                ));
                                $mensagem = 'Ordem de Serviço para Recontagem gerada com sucesso para o Recebimento Nº. ' . $idRecebimento . '. ';
                                $recebimentoEntity->addAndamento(false, false, 'Recontagem solicitada para o Recebimento.');
                                $this->em->persist($recebimentoEntity);
                            } else {
                                $mensagem = 'A Ordem de Serviço Nº ' . $checkOs['id'] . ' já está aberta para este recebimento';
                            }

                            $link = '<a href="' . $this->view->url(array('controller' => 'recebimento', 'action' => 'conferencia-cega-pdf', 'id' => $idRecebimento)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Conferência Cega</a>';

                            if ($ordemServicoEntity->getFormaConferencia() == OrdemServicoEntity::MANUAL)
                                $mensagem .= 'Clique para visualizar o ' . $link;

                            $this->addFlashMessage('success', $mensagem);

                            $this->em->commit();
                            $this->em->flush();
                        } catch (\Exception $e) {
                            $this->em->rollback();
                            $this->addFlashMessage('error', $e->getMessage());
                        }

                        $this->redirect('index');

                        break;
                    // divergencia
                    case 'divergencia':

                        $senhaDivergencia = $params['senhaDivergencia'];
                        $senhaAutorizacao = $this->em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => 3, 'constante' => 'SENHA_AUTORIZAR_DIVERGENCIA'));
                        $senhaAutorizacao = $senhaAutorizacao->getValor();

                        if ($senhaDivergencia != $senhaAutorizacao)
                            throw new \Exception('Senha de autorização de fechamento da divergencia está incorreta.');

                        // checando observacoes
                        $motivosDivergencia = $this->getRequest()->getParam('motivosDivergencia');
                        $notasFiscais = $this->getRequest()->getParam('notasFiscais');
                        $arrNotasEn = array();
                        
                        foreach ($motivosDivergencia as $key => $cod_motivo_divergencia) {

                            $recebimentoConferenciaEntity = $this->em->getReference('wms:Recebimento\Conferencia', $key);
                            $motivoDivergenciaEntity = $this->em->getReference('wms:Recebimento\Divergencia\Motivo', $cod_motivo_divergencia);
                            $arrNotasEn[] = $notaFiscalEntity = $this->em->find('wms:NotaFiscal', $notasFiscais[$key]);

                            $recebimentoConferenciaEntity->setMotivoDivergencia($motivoDivergenciaEntity)
                                    ->setNotaFiscal($notaFiscalEntity);

                            $this->em->persist($recebimentoConferenciaEntity);

                            $notaFiscalEntity->setDivergencia('S');
                            $this->em->persist($notaFiscalEntity);
                        }

                        $ordemServicoEntity->setDataFinal(new \DateTime());
                        $this->em->persist($ordemServicoEntity);
                        $this->em->flush();

                        $notasFiscaisEntities = $this->getEntityManager()->getRepository('wms:Notafiscal')->findBy(array('recebimento' => $recebimentoEntity));
                        $recebimentoErp = false;
                        foreach ($notasFiscaisEntities as $notaFiscalEntity) {
                            if (!is_null($notaFiscalEntity->getCodRecebimentoErp())) {
                                $recebimentoErp = true;
                                break;
                            }
                        }

                        //ATUALIZA O RECEBIMENTO NO ERP CASO O PARAMETRO SEJA 'S'
                        if ($this->getSystemParameterValue('UTILIZA_RECEBIMENTO_ERP') == 'S' && $recebimentoErp == true) {
                            $serviceIntegracao = new \Wms\Service\Integracao($this->getEntityManager(), array
                            (
                                'acao' => null,
                                'options' => null,
                                'tipoExecucao' => 'E'
                            ));
                            $serviceIntegracao->atualizaRecebimentoERP($idRecebimento);
                        }

                        //ATUALIZA O ESTOQUE DO ERP CASO O PARAMETRO SEJA 'S'
                        if ($this->getSystemParameterValue('LIBERA_ESTOQUE_ERP') == 'S') {
                            $serviceIntegracao = new \Wms\Service\Integracao($this->getEntityManager(), array
                            (
                                'acao' => null,
                                'options' => null,
                                'tipoExecucao' => 'E'
                            ));
                            $serviceIntegracao->atualizaEstoqueErp($idRecebimento, $this->getSystemParameterValue('WINTHOR_CODFILIAL_INTEGRACAO'));
                        }

                        //recebimento para o status finalizado
                        $result = $this->em->getRepository('wms:Recebimento')->finalizar($idRecebimento, true, $ordemServicoEntity);
                        if (isset($result['exception']) && !empty($result['exception'])) throw $result['exception'];
                        $this->addFlashMessage('success', 'Recebimento finalizado com divergencias.');

                        $this->redirect('index');
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    /**
     * Visualização de andamentos 
     */
    public function viewAndamentoAjaxAction() {
        $id = $this->getRequest()->getParam('id');

        $recebimento = $this->em->find('wms:Recebimento', $id);
        $this->view->recebimento = $recebimento;

        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimento->getId()));

        if ($notaFiscalEntity)
            $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

        // status de recebimento
        $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimento);
        $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimento->getStatus()->getReferencia());

        $source = $this->em->createQueryBuilder()
                ->select('a, p.nome', 's.sigla as tipoAndamento')
                ->from('wms:Recebimento\Andamento', 'a')
                ->leftJoin('a.usuario', 'u')
                ->leftJoin('u.pessoa', 'p')
                ->leftJoin('a.tipoAndamento', 's')
                ->where('a.recebimento = :idRecebimento')
                ->setParameter('idRecebimento', $id)
                ->orderBy('a.dataAndamento', 'desc');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setAttrib('caption', 'Histórico')
                ->addColumn(array(
                    'label' => 'Data Andamento',
                    'index' => 'dataAndamento',
                    'render' => 'DataTime'
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'tipoAndamento'
                ))
                ->addColumn(array(
                    'label' => 'Usuário',
                    'index' => 'nome'
                ))
                ->addColumn(array(
                    'label' => 'Observação',
                    'index' => 'dscObservacao'
                ))
                ->setShowExport(false);

        $this->view->grid = $grid->build();
    }

    /**
     * Cancelamento de Recebimento
     */
    public function viewCancelamentoAjaxAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index'
                    ),
                    'tag' => 'a'
                ),
            )
        ));

        $id = $this->getRequest()->getParam('id');

        $recebimento = $this->em->find('wms:Recebimento', $id);
        $this->view->recebimento = $recebimento;

        $formObservacao = new ObservacaoAndamentoForm;
        $formObservacao->getElement('idRecebimento')->setValue($id);
        $formObservacao->setAction($this->view->url(array('controller' => 'recebimento', 'action' => 'delete')));

        $source = $this->em->createQueryBuilder()
                ->select('a, p.nome', 's.sigla as tipoAndamento')
                ->from('wms:Recebimento\Andamento', 'a')
                ->leftJoin('a.usuario', 'u')
                ->leftJoin('u.pessoa', 'p')
                ->leftJoin('a.tipoAndamento', 's')
                ->where('a.recebimento = :idRecebimento')
                ->setParameter('idRecebimento', $id)
                ->orderBy('a.dataAndamento', 'desc');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setAttrib('caption', 'Histórico')
                ->addColumn(array(
                    'label' => 'Data do Andamento',
                    'index' => 'dataAndamento',
                    'render' => 'DataTime'
                ))
                ->addColumn(array(
                    'label' => 'Tipo do Andamento',
                    'index' => 'tipoAndamento'
                ))
                ->addColumn(array(
                    'label' => 'Usuário do Andamento',
                    'index' => 'nome'
                ))
                ->setShowExport(false);

        $this->view->form = $formObservacao;
        $this->view->grid = $grid->build();
    }

    /**
     * Nota Item
     */
    public function viewNotaItemAjaxAction() {

        $id = $this->getRequest()->getParam('id');

        $recebimento = $this->em->find('wms:Recebimento', $id);
        $this->view->recebimento = $recebimento;

        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimento->getId()));

        if ($notaFiscalEntity)
            $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

        // status de recebimento
        $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimento);
        $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimento->getStatus()->getReferencia());

        $emisCLI = Pessoa\Papel\EmissorInterface::EMISSOR_CLIENTE;
        $emisFOR = Pessoa\Papel\EmissorInterface::EMISSOR_FORNECEDOR;

        // busco notas fiscais
        $dql = $this->em->createQueryBuilder()
                ->select('nf.id, nf.numero, nf.serie, nf.dataEmissao, p.nome, s.id idStatus, s.sigla status')
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin("nf.tipo", 't')
                ->leftJoin('nf.cliente', 'c', 'WITH', "t.emissor = '$emisCLI'" )
                ->leftJoin('nf.fornecedor', 'f', 'WITH', "t.emissor = '$emisFOR'" )
                ->innerJoin(Pessoa::class, 'p', 'WITH', 'c.id = p OR f.id = p')
                ->innerJoin('nf.status', 's')
                ->where('nf.recebimento = :idRecebimento')
                ->setParameter('idRecebimento', $id)
                ->groupBy('nf.id, nf.numero, nf.serie, nf.dataEmissao, p.nome, s.id, s.sigla')
                ->orderBy('nf.id');

        $notasFiscais = $dql->getQuery()->execute();

        // loop nas notas
        foreach ($notasFiscais as $key => $notaFiscal) {

            //busco produtos da nota
            $dql = $this->em->createQueryBuilder()
                ->select('p.id, p.grade, SUM(nfi.quantidade) quantidade, p.descricao, p.possuiPesoVariavel, SUM(nfi.numPeso) as peso, nfil.lote as lote')
                ->from('wms:NotaFiscal\Item', 'nfi')
                ->leftJoin('wms:NotaFiscal\NotaFiscalItemLote', 'nfil','WITH','nfi.id = nfil.codNotaFiscalItem')
                ->leftJoin('wms:Produto\Lote', 'l','WITH','nfil.lote = l.descricao and (l.codProduto = nfi.codProduto and l.grade = nfi.grade)')
                ->innerJoin('nfi.produto', 'p')
                ->andWhere('nfi.notaFiscal = :idNotafiscal')
                ->setParameter('idNotafiscal', $notaFiscal['id'])
                ->groupBy('p.id, p.grade, p.descricao, p.possuiPesoVariavel, nfil.lote')
                ->orderBy('p.descricao');
            $itens = $dql->getQuery()->execute();

            $notasFiscais[$key]['itens'] = $itens;
        }
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        foreach ($notasFiscais as $key1 => $vetItens) {
            foreach ($vetItens['itens'] as $key => $value) {
                $vetEmbalagens = $embalagemRepo->getQtdEmbalagensProduto($value['id'], $value['grade'], $value['quantidade']);
                $embalagem = $value['quantidade'];
                if (is_array($vetEmbalagens)) {
                    $embalagem = implode(' + ',$vetEmbalagens);
                }
                $notasFiscais[$key1]['itens'][$key]['quantidade'] = $embalagem;
            }
        }
        $this->view->notasFiscais = $notasFiscais;
        $this->view->idStatusCancelado = NotaFiscalEntity::STATUS_CANCELADA;
    }

    /**
     * Ordem Serviço
     */
    public function viewOrdemServicoAjaxAction() {

        $id = $this->getRequest()->getParam('id');

        $recebimento = $this->em->find('wms:Recebimento', $id);
        $this->view->recebimento = $recebimento;

        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimento->getId()));

        if ($notaFiscalEntity)
            $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

        // status de recebimento
        $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimento);
        $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimento->getStatus()->getReferencia());

        if ($recebimento->getStatus()->getId() == RecebimentoEntity::STATUS_FINALIZADO) {
            $link = '<a href="' . $this->view->url(array('controller' => 'recebimento', 'action' => 'produtos-conferidos-pdf', 'id' => $id)) . '" target="_blank" class="btnAlert relProdutosConferidos"><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" />Gerar Relatório de Produtos Conferidos</a>';
            $this->view->gerarRelatorio = $link;
        }

        $source = $this->em->createQueryBuilder()
                ->select('os, r.id idRecebimento, p.nome, a.descricao as dscAtividade, s.id statusId, s.sigla status')
                ->from('wms:OrdemServico', 'os')
                ->join('os.recebimento', 'r')
                ->join('r.status', 's')
                ->leftJoin('os.atividade', 'a')
                ->leftJoin('os.pessoa', 'p')
                ->where('os.recebimento = :idRecebimento')
                ->setParameter('idRecebimento', $id)
                ->orderBy('os.id');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('recebimento-view-ordem-servico-ajax-grid')
                ->addColumn(array(
                    'label' => 'Ordem de Serviço',
                    'index' => 'id'
                ))
                ->addColumn(array(
                    'label' => 'Responsável',
                    'index' => 'nome'
                ))
                ->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'dscAtividade'
                ))
                ->addColumn(array(
                    'label' => 'Data Início',
                    'index' => 'dataInicial',
                    'render' => 'Data'
                ))
                ->addColumn(array(
                    'label' => 'Data Final',
                    'index' => 'dataFinal',
                    'render' => 'Data'
                ))
                ->addAction(array(
                    'label' => 'Digitação da Conferência Cega',
                    'actionName' => 'conferencia',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['dataFinal'] == null;
                    }
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferência',
                    'actionName' => 'view-conferencia',
                    'cssClass' => 'view-conferencia',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['dataFinal'] != null;
                    }
                ))
                ->addAction(array(
                    'label' => 'Gerar Relatório de Conferência Cega',
                    'actionName' => 'conferencia-cega-pdf',
                    'pkIndex' => 'idRecebimento',
                    'target' => 'blank',
                    'condition' => function ($row) {
                        return ( ($row['dataFinal'] == null) && ($row['statusId'] == RecebimentoEntity::STATUS_CONFERENCIA_CEGA || $row['statusId'] == RecebimentoEntity::STATUS_CONFERENCIA_COLETOR));
                    }
                ))
                ->setShowExport(false);

        $this->view->grid = $grid->build();
    }

    /**
     * Visualizar Conferencia
     */
    public function viewConferenciaAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index'
                    ),
                    'tag' => 'a'
                ),
            )
        ));

        $id = $this->getRequest()->getParam('id');

        $ordemServicoEntity = $this->em->find('wms:OrdemServico', $id);
        $this->view->ordemServico = $ordemServicoEntity;

        //recebimento
        $recebimento = $ordemServicoEntity->getRecebimento();
        $this->view->recebimento = $recebimento;

        //busca a placa de uma nota deste recebimento, pois os recebimentos sao feitos de apenas um veiculo, entao todas as notas sao do mesmo veiculo
        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimento->getId()));

        if ($notaFiscalEntity)
            $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

        // grid da conferencia
        $grid = new ConferenciaGrid;
        $this->view->grid = $grid->init(array('idOrdemServico' => $id))
                ->render();
    }

    /**
     * Relatorio de Conferencia Cega
     */
    public function conferenciaCegaAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar para Busca de Recebimentos',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index'
                    ),
                    'tag' => 'a'
                ),
            )
        ));

        $params = $this->getRequest()->getParams();

        $form = new OrdemServicoForm;


        try {
            //Recupera o id do recebimento
            $idRecebimento = $params['id'];

            if ($idRecebimento == null)
                throw new \Exception('Id must be provided for the edit action');

            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
            $recebimentoEntity = $recebimentoRepo->find($idRecebimento);

            // status de recebimento
            $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimentoEntity);
            $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimentoEntity->getStatus()->getReferencia());

            $this->view->recebimento = $recebimentoEntity;

            //verifica se existe produtos com impressão automática do código de barras
            $produtoRepo = $this->em->getRepository('wms:Produto');
            $produtoEntity = $produtoRepo->verificarProdutosImprimirCodigoBarras($idRecebimento);

            if ($produtoEntity == "S") {
                $link = '<a href="' . $this->view->url(array('controller' => 'recebimento', 'action' => 'gerar-etiqueta-pdf', 'id' => $idRecebimento)) . '" target="_blank" class="pdf dialogAjax"><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Etiquetas</a>';
                $this->addFlashMessage('success', 'Clique para imprimir etiquetas de Embalagem/Volumes dos Produtos ' . $link);
            }

            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {

                $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');
                $ordemServicoEntity = $ordemServicoRepo->findOneBy(array('recebimento' => $idRecebimento, 'atividade' => 1, 'dataFinal' => null));

                if ($ordemServicoEntity)
                    throw new \Exception('Já existe uma ordem de serviço de conferencia cega Nº. ' . $ordemServicoEntity->getId() . ' aberta para este recebimento.');

                // gerar
                $recebimentoRepo->executaIntegracaoBDEmRecebimentoERP($recebimentoEntity);
                $recebimentoEntity->addAndamento(RecebimentoEntity::STATUS_CONFERENCIA_CEGA, false, 'Conferência iniciada pelo WMS.');
                $recebimentoRepo->updateStatus($recebimentoEntity, RecebimentoEntity::STATUS_CONFERENCIA_CEGA);
                $ordemServicoRepo->save(new OrdemServicoEntity, array('identificacao' => array(
                        'idRecebimento' => $idRecebimento,
                        'idAtividade' => AtividadeEntity::CONFERIR_PRODUTO,
                        'formaConferencia' => OrdemServicoEntity::MANUAL,
                )));

                //verifica se existe produtos com impressão automática do código de barras
                $produtoRepo = $this->em->getRepository('wms:Produto');
                $produtoEntity = $produtoRepo->verificarProdutosImprimirCodigoBarras($idRecebimento);

                if ($produtoEntity == "S") {
                    $link = '<a href="' . $this->view->url(array('controller' => 'recebimento', 'action' => 'gerar-etiqueta-pdf', 'id' => $idRecebimento)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Etiquetas</a>';
                    $this->addFlashMessage('success', 'Clique para imprimir etiquetas de Embalagem/Volumes dos Produtos ' . $link);
                }

                $link = '<a href="' . $this->view->url(array('controller' => 'recebimento', 'action' => 'conferencia-cega-pdf', 'id' => $idRecebimento)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Conferência Cega</a>';
                $this->addFlashMessage('success', 'Ordem de Serviço gerada com sucesso para o Recebimento Nº. ' . $idRecebimento . '. Clique para visualizar o ' . $link);
                $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
        }

        $form->setDefault('idRecebimento', $idRecebimento);
        $this->view->form = $form;
    }

    /**
     * Relatorio de Conferencia Cega
     */
    public function conferenciaCegaPdfAction() {
        $idRecebimento = $this->getRequest()->getParam('id');

        $conferenciaCegaReport = new \Wms\Module\Web\Report\Recebimento\ConferenciaCega();

        $conferenciaCegaReport->init(array(
            'idRecebimento' => $idRecebimento,
        ));
    }

    /**
     * Relatorio de Produtos Conferidos
     */
    public function produtosConferidosPdfAction() {
        $idRecebimento = $this->getRequest()->getParam('id');

        $produtosConferidosReport = new \Wms\Module\Web\Report\Recebimento\ProdutosConferidos();

        $produtosConferidosReport->init(array(
            'idRecebimento' => $idRecebimento,
        ));
    }

    /**
     *
     * @return type 
     */
    public function gerarAction() {
        $values = $this->getRequest()->getParams();
        $notasFiscais = 0;
        try {
            if (isset($values['notasFiscais'])) {
                $notasFiscais = count($values['notasFiscais']);
            }
            if ($notasFiscais == 0)
                throw new \Exception('Por favor selecione alguma nota para gerar o recebimento');

            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
            $recebimentoId = $recebimentoRepo->gerar($values['notasFiscais']);

            $this->_helper->messenger('success', "Nota de Recebimento No. {$recebimentoId} gerada com sucesso!");

            //redirect
            $this->session = new \Zend_Session_Namespace("Wms\Module\Web\Form\Subform\FiltroRecebimentoMercadoria");
            $this->session->params = array('identificacao' => array('idRecebimento' => $recebimentoId));
            $this->redirect('iniciar', 'recebimento', null, array('id' => $recebimentoId));
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('buscar-nota');
        }
    }

    /**
     *
     * @return type 
     */
    public function buscarNotaAction() {
        $filtroNotaFiscalForm = new FiltroNotaFiscalForm;
        $this->view->form = $filtroNotaFiscalForm;

        //INTEGRAR NOTAS FISCAIS NO MOMENTO Q ENTRAR NA TELA DE GERAR RECEBIMENTO
        $codAcaoIntegracao = $this->getSystemParameterValue('COD_INTEGRACAO_NOTAS_FISCAIS_TELA_ENTR');

        if (isset($codAcaoIntegracao) && !empty($codAcaoIntegracao)) {
            $explodeIntegracoes = explode(',',$codAcaoIntegracao);

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntegracaoRepository */
            $acaoIntegracaoRepository = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            foreach ($explodeIntegracoes as $codIntegracao) {
                $acaoIntegracaoEntity = $acaoIntegracaoRepository->find($codIntegracao);
                $acaoIntegracaoRepository->processaAcao($acaoIntegracaoEntity,null,'E','P',null, \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::DATA_ESPECIFICA);
            }
        }

        $params = $filtroNotaFiscalForm->getParams();
        if (!$params) {
            $dataI1 = new \DateTime;
            $dataI2 = new \DateTime;
            $params = array(
                'dataEntradaInicial' => $dataI1->format('d/m/Y'),
                'dataEntradaFinal' => $dataI2->format('d/m/Y'),
                'idEmissor' => '',
                'numero' => '',
                'serie' => ''
            );
            $filtroNotaFiscalForm->populate($params);
        }

        if ($params) {
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
            $resultSet = $notaFiscalRepo->search($params);

            $data = array();

            foreach ($resultSet as $key => $row) {

                $dataEntrada = ($row[0]->getDataEntrada()) ? $row[0]->getDataEntrada()->format('d/m/Y') : '00/00/0000';

                $data[$key]['id'] = $row[0]->getId();
                $data[$key]['numero'] = $row[0]->getNumero();
                $data[$key]['serie'] = $row[0]->getSerie();
                $data[$key]['placa'] = $row[0]->getPlaca();
                $data[$key]['dataEntrada'] = $dataEntrada;
                $data[$key]['emissor'] = substr($row['emissor'], 0, 45);
                $data[$key]['status'] = $row[0]->getStatus()->getSigla();
                $vetEmbalagens = $notaFiscalRepo->getTotalPorEmbalagemNota($row[0]->getId());
                $data[$key]['qtdProdutoMaior'] = (int) $vetEmbalagens[0]['QTDMAIOR'];
                $data[$key]['qtdProdutoMenor'] = (int) $vetEmbalagens[0]['QTDMENOR'];
            }
            if (count($data) == 0)
                $this->_helper->messenger('info', 'Nenhuma nota fiscal encontrada.');

            $filtroNotaFiscalForm->populate($params);

            $this->view->notasFiscais = $data;
        }
    }

    /**
     * Relatorio de Produtos Sem Dados Logisticos
     */
    public function produtosSemDadosLogisticosPdfAction() {
        $idRecebimento = $this->getRequest()->getParam('id');

        $produtosDadosLogisticosReport = new RelatorioDadosLogisticosProduto();

        $produtosDadosLogisticosReport->init(array(
            'idRecebimento' => $idRecebimento,
            'indDadosLogisticos' => 'N'
        ));
    }

    /**
     * Gera etiqueta para os Produtos com impressão automática do código de barras
     */
    public function gerarEtiquetaPdfAction() {
        $idRecebimento = $this->getRequest()->getParam('id');
        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento');

        $produtos = $recebimentoRepo->getProdutosImprimirByRecebimento($idRecebimento);

        $this->view->idRecebimento = $idRecebimento;
        $this->view->produtos = $produtos;
    }

    public function imprimirProdutoAjaxAction () {

        $params = $this->getRequest()->getParams();

        $arrProdutos = array();
        foreach ($params['produtos'] as $key => $prodGrade) {
            list($codProduto, $grade) = explode('*-*', $prodGrade);
            $arg = ['codProduto' => $codProduto, 'grade' => $grade];
            if (isset($params['emb'][$codProduto][$grade])) {
                $arg['emb'] = $params['emb'][$codProduto][$grade];
            }
            $arrProdutos[] = $arg;
        }

        if ($params['tipo'] == 'recebimento') {
            $modelo = "recebimento";
        } else {
            $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PRODUTO");
        }

        $target = $this->getSystemParameterValue("IMPRESSAO_PRODUTO_RECEBIMENTO");

        switch ($modelo) {
            case 2:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 60));
                break;
            case 3:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(50, 30));
                break;
            case 4:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(113, 70));
                break;
            case 5:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(120, 70));
                break;
            case "recebimento":
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(50, 28));
		        $gerarEtiqueta->SetAutoPageBreak(false);
                break;
            case 8:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(100, 35));
                break;
            default:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 50));
                break;
        }

        $gerarEtiqueta->init(array('idRecebimento' => $params['id']), null, $modelo, $target, false, $arrProdutos);

    }
    
    public function __call($methodName, $args) {
        parent::__call($methodName, $args);
    }

    public function forcarCorrecaoAction() {
        $idRecebimento = $this->getRequest()->getParam('id');

        $repository = $this->em->getRepository('wms:OrdemServico');
        $result = $repository->forcarCorrecao($idRecebimento);
        $idOS = $result[0][2];

        if ($result[0][1] == 2) {
            $data = new \DateTime;
            $repository->atualizarDataFinal($idOS, $data);
            $this->_helper->messenger('info', 'A Ordem de Serviço foi finalizada com sucesso.');
            $this->redirect('index', 'recebimento', null);
        } else {
            $this->_helper->messenger('info', 'Essa correção não pode ser usada, pois existe apenas uma Ordem de Serviço.');
            $this->redirect('index', 'recebimento', null);
        }
    }

    public function modeloRecebimentoAction() {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Adicionar novo',
                    'cssClass' => 'btnAdd',
                    'urlParams' => array(
                        'action' => 'add'
                    ),
                    'tag' => 'a'
                )
            )
        ));

        /** @var \Wms\Domain\Entity\Recebimento\ModeloRecebimentoRepository $modeloRecebimentoRepo */
        $modeloRecebimentoRepo = $this->em->getRepository('wms:Recebimento\ModeloRecebimento');

        $modelos = $modeloRecebimentoRepo->getModelosRecebimento();

        $grid = new ModeloRecebimentoGrid();
        $this->view->grid = $grid->init($modelos)->render();
    }

    public function addAction() {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'modelo-recebimento',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                ),
            )
        ));

        $form = new ModeloRecebimentoForm();

        try {
            $params = $this->getRequest()->getParams();

            /** @var \Wms\Domain\Entity\Recebimento\ModeloRecebimentoRepository $modeloRecebimentoRepo */
            $modeloRecebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento\ModeloRecebimento');

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $modeloRecebimentoEn = new ModeloRecebimentoEn();
                $modeloRecebimentoRepo->save($modeloRecebimentoEn, $params['cadastro']);

                $this->addFlashMessage('success', 'Modelo de Recebimento cadastrado com sucesso.');
                $this->_redirect('/recebimento/modelo-recebimento');
            }
            //$form->setDefaultsFromEntity($entity); // pass values to form
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }

    public function deleteModeloAction() {
        try {
            $params = $this->getRequest()->getParams();

            /** @var \Wms\Domain\Entity\Recebimento\ModeloRecebimentoRepository $modeloRecebimentoRepo */
            $modeloRecebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento\ModeloRecebimento');
            $modeloRecebimentoEn = $modeloRecebimentoRepo->findOneBy(array('id' => $params['id']));

            $this->_em->remove($modeloRecebimentoEn);
            $this->_em->flush();

            $this->addFlashMessage('success', 'Modelo de Recebimento excluido com sucesso.');
            $this->_redirect('/recebimento/modelo-recebimento');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function editAction() {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'modelo-recebimento',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                ),
            )
        ));

        $form = new ModeloRecebimentoForm();

        try {
            $params = $this->getRequest()->getParams();

            /** @var \Wms\Domain\Entity\Recebimento\ModeloRecebimentoRepository $modeloRecebimentoRepo */
            $modeloRecebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento\ModeloRecebimento');
            $modeloRecebimentoEn = $modeloRecebimentoRepo->findOneBy(array('id' => $params['id']));

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $modeloRecebimentoRepo->save($modeloRecebimentoEn, $params['cadastro']);

                $this->addFlashMessage('success', 'Modelo de Recebimento cadastrado com sucesso.');
                $this->_redirect('/recebimento/modelo-recebimento');
            }
            $form->setDefaultsFromEntity($modeloRecebimentoEn);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }

    public function parametrosAjaxAction() {
        $form = new RecebimentoForm\ParametrosRecebimento();
        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento');
        $idRecebimento = $this->_getParam('id');
        $recebimentoEn = $recebimentoRepo->findOneBy(array('id' => $idRecebimento));
        $form->setDefaultsFromEntity($recebimentoEn);
        $this->view->form = $form;
    }

    public function salvaParametrosAjaxAction() {
        $params = $this->_getAllParams();
        $form = new RecebimentoForm\ParametrosRecebimento();

        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento');
        try {
            $idRecebimento = $this->_getParam('id');
            $recebimentoEn = $recebimentoRepo->findOneBy(array('id' => $idRecebimento));

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

                $idModelo = $params['recebimento']['modelo'];
                $modeloEn = null;
                if ($idModelo != "") {
                    $modeloEn = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo')->findOneBy(array('id' => $idModelo));
                }
                $recebimentoEn->setModeloEnderecamento($modeloEn);
                $this->getEntityManager()->persist($recebimentoEn);
                $this->getEntityManager()->flush();

                $this->addFlashMessage('success', 'Modelo de Endereçamento alterado com sucesso.');
                $this->_redirect('/recebimento');
            }
            $this->view->form = $form;
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function usuarioRecebimentoPdfAction() {
        $idRecebimento = $this->_getParam('id', 0);
        /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $recebimentoDescargaRepo */
        $recebimentoDescargaRepo = $this->getEntityManager()->getRepository('wms:Recebimento\Descarga');
        $recebimentoDescarga = $recebimentoDescargaRepo->getInfosDescarga($idRecebimento);

        $this->exportPDF($recebimentoDescarga, 'usuario_descarga_' . $idRecebimento, 'Usuários Descarga Recebimento ' . $idRecebimento, 'P');
    }

    public function checkShelflifeAjaxAction() {
        $request = $this->_request->getPost();
        $produtos = json_decode($request['data']);

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->em->getRepository('wms:Produto');
        $result = array();
        foreach ($produtos as $produto) {
            $key = "Produto: " . $produto->id . " Grade: " . $produto->grade;
            $result[$key] = $produtoRepo->checkShelfLifeProduto($produto, $produto->data);
        }

        $this->_helper->json(array('result' => $result));
    }

    public function checkSenhaAutorizacaoAjaxAction() {
        $senha = $this->getRequest()->getParam('senha');
        $senhaAutorizacao = $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA');
        $result = false;
        if ($senhaAutorizacao === $senha) {
            $result = true;
        }
        $this->_helper->json(array("result" => $result));
    }

    public function visualizarRecebimentosBloqueadosAction()
    {
        $grid = new RecebimentoGrid\RecebimentoBloqueado();
        /** @var \Wms\Domain\Entity\Usuario $user */
        $user = $this->em->find("wms:Usuario", \Zend_Auth::getInstance()->getIdentity()->getId());
        $this->view->grid = $grid->init($user)->render();
    }

    public function liberarRecusarRecebimentosAjaxAction()
    {
        extract($this->_getAllParams());

        try {
            $recebimentoEntity = $this->getEntityManager()->getReference('wms:Recebimento',$codRecebimento);
            $recebimentoEntity
                ->addAndamento(false, false, $observacao, $codProduto, $grade, $dataValidade, (int)$diasVidaUtil, $qtdBloqueada);

            $this->getEntityManager()->persist($recebimentoEntity);

            if ($liberar == true) {
                if ($codRecebEmbalagem) {
                    $recebimentoEmbalagemEntity = $this->getEntityManager()->getReference('wms:Recebimento\Embalagem', $codRecebEmbalagem);
                    $recebimentoEmbalagemEntity->setQtdConferida($recebimentoEmbalagemEntity->getQtdBloqueada());
                    $recebimentoEmbalagemEntity->setQtdBloqueada(0);

                    $this->getEntityManager()->persist($recebimentoEmbalagemEntity);
                } else if ($codRecebVolume) {
                    $recebimentoVolumeEntity = $this->getEntityManager()->getReference('wms:Recebimento\Volume', $codRecebVolume);
                    $recebimentoVolumeEntity->setQtdConferida($recebimentoVolumeEntity->getQtdBloqueada());
                    $recebimentoVolumeEntity->setQtdBloqueada(0);

                    $this->getEntityManager()->persist($recebimentoVolumeEntity);
                }
            } else {
                if ($codRecebEmbalagem) {
                    $recebimentoEmbalagemEntity = $this->getEntityManager()->getReference('wms:Recebimento\Embalagem', $codRecebEmbalagem);
                    $recebimentoEmbalagemEntity->setQtdBloqueada(0);

                    $this->getEntityManager()->persist($recebimentoEmbalagemEntity);
                } else if ($codRecebVolume) {
                    $recebimentoVolumeEntity = $this->getEntityManager()->getReference('wms:Recebimento\Volume', $codRecebVolume);
                    $recebimentoVolumeEntity->setQtdBloqueada(0);

                    $this->getEntityManager()->persist($recebimentoVolumeEntity);
                }
            }

            $this->getEntityManager()->flush();

            $this->addFlashMessage('success', $observacao);
            $this->_redirect('/recebimento/visualizar-recebimentos-bloqueados');

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }
}
