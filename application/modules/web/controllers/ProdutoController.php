<?php

use Wms\Domain\Entity\Produto,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Core\Util\Converter,
    Wms\Module\Web\Form\Produto\Filtro as FiltroForm,
    Wms\Module\Web\Grid\Produto\DadoLogistico as DadoLogisticoGrid,
    Wms\Domain\Entity\Produto as ProdutoEntity;
use Wms\Module\Armazenagem\Printer\EtiquetaEndereco;

/**
 * Description of Web_ProdutoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ProdutoController extends Crud {

    public $entityName = 'Produto';

    public function indexAction() {

        $parametroProduto = $this->getSystemParameterValue('ID_INTEGRACAO_PRODUTOS');
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Importar Produtos ERP',
                    'cssClass' => 'btnSave',
                    'urlParams' => array(
                        'module' => 'importacao',
                        'controller' => 'gerenciamento',
                        'action' => 'index',
                        'id' => $parametroProduto,
                    ),
                    'tag' => 'a'
                )
            )
        ));

        //CADASTRAR NOVOS PRODUTOS TODA VEZ Q ENTRAR NA TELA DE DADOS LOGISTICOS
        if (isset($parametroProduto) && !empty($parametroProduto)) {
            $explodeIntegracoes = explode(',', $parametroProduto);

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntegracaoRepository */
            $acaoIntegracaoRepository = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            foreach ($explodeIntegracoes as $codIntegracao) {
                $acaoIntegracaoEntity = $acaoIntegracaoRepository->find($codIntegracao);
                $acaoIntegracaoRepository->processaAcao($acaoIntegracaoEntity);
            }
        }

        $form = new FiltroForm;

        $values = $form->getParams();

        if ($values) {
            $grid = new DadoLogisticoGrid;
            $this->view->grid = $grid->init($values)
                    ->render();

            $form->setSession($values)
                    ->populate($values);
        }

        $this->view->form = $form;
    }

    public function printCodBarProdutoAjaxAction() {
        $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PRODUTO");
        $txt = str_replace("\r","",str_replace("\n","",file_get_contents('codigos.txt')));
        $array = explode(";", $txt);
        $grade = 'UNICA';
        $gerarEtiqueta = null;
        switch ($modelo) {
            case 1:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 50));
                break;
            case 2:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 60));
                break;
            case 3:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(75, 45));
                break;
            case 4:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(113, 70));
                break;
            case 8:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(100, 35));
                break;
            case 14:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(115, 55));
                break;

        }
        $gerarEtiqueta->init(null, array( 'produtos' => $array, 'grade' => $grade), $modelo,\Wms\Domain\Entity\Recebimento::TARGET_IMPRESSAO_PRODUTO,true);
    }

    /**
     * Lista as normas de paletizacao com dados logisticos
     */
    public function listarNormaPorDadoLogisticoJsonAction() {
        $em = $this->getEntityManager();

        $params = $this->getRequest()->getParams();

        $dql = $em->createQueryBuilder()
                ->select('np.id, np.numLastro, np.numCamadas, np.numPeso, np.numNorma, np.isPadrao, 
                    u.id idUnitizador, u.descricao unitizador, e.id embalagem')
                ->from('wms:Produto\Embalagem', 'e')
                ->innerJoin('e.dadosLogisticos', 'dl')
                ->innerJoin('dl.normaPaletizacao', 'np')
                ->innerJoin('np.unitizador', 'u')
                ->where('e.codProduto = ?1')
                ->setParameter(1, $params['idProduto'])
                ->andWhere('e.grade = :grade')
                ->setParameter('grade', $params['grade']);

        $normasPaletizacao = array();

        // loop para agrupar normas repetidas, já que a bosta do oracle não faz
        foreach ($dql->getQuery()->getResult() as $row) {
            $normasPaletizacao[$row['id']] = array(
                'id' => $row['id'],
                'numLastro' => $row['numLastro'],
                'numCamadas' => $row['numCamadas'],
                'numPeso' => Converter::enToBr($row['numPeso'], 3),
                'numNorma' => $row['numNorma'],
                'isPadrao' => $row['isPadrao'],
                'idUnitizador' => $row['idUnitizador'],
                'unitizador' => $row['unitizador'],
                'embalagem' => $row['embalagem'],
                'acao' => 'alterar',
            );
        }

        $normasPaletizacao = array_values($normasPaletizacao);

        // busca unitizadores
        $unitizadores = $em->getRepository('wms:Armazenagem\Unitizador')->findAll();

        foreach ($normasPaletizacao as $key => $normaPaletizacao) {

            foreach ($unitizadores as $unitizador) {
                $normasPaletizacao[$key]['unitizadores'][] = array(
                    'id' => $unitizador->getId(),
                    'descricao' => $unitizador->getDescricao(),
                );
            }

            $dadosLogisticos = $em->getRepository('wms:Produto\DadoLogistico')
                    ->findBy(array('normaPaletizacao' => $normaPaletizacao['id'], 'embalagem' => $normaPaletizacao['embalagem']));

            foreach ($dadosLogisticos as $dadoLogistico) {

                $lblEmbalagem = $dadoLogistico->getEmbalagem()->getDescricao() . ' ( ' . $dadoLogistico->getEmbalagem()->getQuantidade() . ' )';
                $idNormaPaletizacao = ($dadoLogistico->getNormaPaletizacao()) ? $dadoLogistico->getNormaPaletizacao()->getId() : 0;

                $normasPaletizacao[$key]['dadosLogisticos'][] = array(
                    'id' => $dadoLogistico->getId(),
                    'idNormaPaletizacao' => $idNormaPaletizacao,
                    'idEmbalagem' => $dadoLogistico->getEmbalagem()->getId(),
                    'largura' => $dadoLogistico->getEmbalagem()->getLargura(),
                    'altura' => $dadoLogistico->getEmbalagem()->getAltura(),
                    'profundidade' => $dadoLogistico->getEmbalagem()->getProfundidade(),
                    'cubagem' => $dadoLogistico->getEmbalagem()->getCubagem(),
                    'peso' => $dadoLogistico->getEmbalagem()->getPeso(),
                    'normaPaletizacao' => $dadoLogistico->getNormaPaletizacao()->getId(),
                    'lblEmbalagem' => $lblEmbalagem,
                    'qtdEmbalagem' => $dadoLogistico->getEmbalagem()->getQuantidade(),
                    'acao' => 'alterar',
                );
            }
        }

        $this->_helper->json($normasPaletizacao, true);
    }

    /**
     * Edita um registro
     * @return void
     */
    public function editAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null,
                        'grade' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

//finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        /** @var \Wms\Module\Web\Form\Produto $form */
        $form = new $formClass;

        $params = $this->getRequest()->getParams();
        $entity = $this->repository->findOneBy(array('id' => $params['id'], 'grade' => $params['grade']));
        try {
            if (isset($params['embalagens'])) {
                $fator = $params['embalagem-fator'];
                foreach ($params['embalagens'] as $key => $value) {
                    $params['embalagens'][$key]['capacidadePicking'] = $fator * $params['embalagem']['capacidadePicking'];
                    $params['embalagens'][$key]['pontoReposicao'] = $fator * $params['embalagem']['pontoReposicao'];
                    $params['embalagens'][$key]['endereco'] = $params['embalagem']['endereco'];
                }
            }
            if (!isset($params['id']) || $params['id'] == null || !isset($params['grade']) || $params['grade'] == null)
                throw new \Exception('Codigo e Grade do produto devem ser fornecidos');

            /** @var ProdutoEntity $entity */
            if ($this->getRequest()->isPost()) {

                if (isset($params['embalagens'])) {
                    foreach (($params['embalagens']) as $embalagem) {
                        if (($embalagem['endereco']) != "" && ($embalagem['capacidadePicking'] == 0)) {
                            throw new \Exception("A definição da capacidade de picking é obrigatória para produtos separados no picking ");
                        }
                    }
                }
                if (isset($params['volumes'])) {
                    foreach (($params['volumes']) as $volume) {
                        if (($volume['endereco']) != "" && ($volume['capacidadePicking'] == 0)) {
                            throw new \Exception("A definição da capacidade de picking é obrigatória para produtos separados no picking ");
                        }
                    }
                }


                $linhaEn = $entity->getLinhaSeparacao();

                if (($linhaEn == NULL) || ($linhaEn->getId() != $params['produto']['idLinhaSeparacao'])) {
                    /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
                    $produtoRepo = $this->em->getRepository('wms:Produto');
                    $enGrades = $produtoRepo->findBy(array('id' => $params['id']));

                    /** @var \Wms\Domain\Entity\Armazenagem\LinhaSeparacaoRepository $linhaRepo */
                    $linhaRepo = $this->em->getRepository('wms:Armazenagem\LinhaSeparacao');
                    $linhaEn = $linhaRepo->findOneBy(array('id' => $params['produto']['idLinhaSeparacao']));

                    foreach ($enGrades as $enGrade) {
                        $enGrade->setLinhaSeparacao($linhaEn);
                        $this->_em->persist($enGrade);
                    }
                }

                $validade = strtoupper($params['produto']['validade']);
                if ($validade != 'S') {
                    $validade = 'N';
                }

                $entity->setValidade($validade);
                if ($validade == 'N' || $params['produto']['diasVidaUtil'] == null || empty($params['produto']['diasVidaUtil'])) {
                    $entity->setDiasVidaUtil(0);
                    $entity->setDiasVidaUtilMax(0);
                } else {
                    $entity->setDiasVidaUtil($params['produto']['diasVidaUtil']);
                    $entity->setDiasVidaUtilMax($params['produto']['diasVidaUtilMaximo']);
                }

                if ($params['produto']['pVariavel'] == 'N') {
                    $params['produto']['percTolerancia'] = null;
                    $params['produto']['toleranciaNominal'] = null;
                }

                $entity->setPercTolerancia($params['produto']['percTolerancia']);
                $entity->setToleranciaNominal($params['produto']['toleranciaNominal']);
                $entity->setIndFracionavel($params['produto']['indFracionavel']);
                $entity->setUnidadeFracao($params['produto']['unidFracao']);
                $paramsSave = $this->getRequest()->getParams();
                $paramsSave['produto']['possuiPesoVariavel'] = $paramsSave['produto']['pVariavel'];
                if (isset($paramsSave['embalagens'])) {
                    $fator = $paramsSave['embalagem-fator'];
                    $alturaReal = floatval(str_replace(',', '.', $paramsSave['embalagem']['altura'])) / floatval($fator);
                    $pesoReal = floatval(str_replace(',', '.', $paramsSave['embalagem']['peso'])) / floatval($fator);
                    $largura = floatval(str_replace(',', '.', $paramsSave['embalagem']['largura']));
                    $profundidade = floatval(str_replace(',', '.', $paramsSave['embalagem']['profundidade']));

                    foreach ($paramsSave['embalagens'] as $key => $value) {
                        if (isset($paramsSave['embalagens'][$key]['acao']) && $paramsSave['embalagens'][$key]['acao'] != 'excluir') {
                            $altura = \Wms\Math::multiplicar($alturaReal, $value['quantidade']);
                            $peso = \Wms\Math::multiplicar($pesoReal, $value['quantidade']);
                            $cubagem = \Wms\Math::multiplicar($altura, \Wms\Math::multiplicar($largura, $profundidade));
                            $paramsSave['embalagens'][$key]['capacidadePicking'] = $fator * $paramsSave['embalagem']['capacidadePicking'];
                            $paramsSave['embalagens'][$key]['pontoReposicao'] = $fator * $paramsSave['embalagem']['pontoReposicao'];
                            $paramsSave['embalagens'][$key]['endereco'] = $paramsSave['embalagem']['endereco'];
                            $paramsSave['embalagens'][$key]['altura'] = $altura; //number_format($altura, 3, ',', '');
                            $paramsSave['embalagens'][$key]['profundidade'] = $profundidade; // $paramsSave['embalagem']['profundidade'];
                            $paramsSave['embalagens'][$key]['cubagem'] = $cubagem; //number_format($cubagem, 4, ',', '');
                            $paramsSave['embalagens'][$key]['peso'] = $peso ; //number_format($peso, 3, ',', '');
                            $paramsSave['embalagens'][$key]['largura'] = $largura; //e$paramsSave['embalagem']['largura'];
                            if (empty($paramsSave['embalagens'][$key]['acao'])) {
                                $paramsSave['embalagens'][$key]['acao'] = 'alterar';
                            }
                        }
                    }
                }
                //var_dump($paramsSave);die;
                $result = $this->repository->save($entity, $paramsSave, true);
                if (is_string($result)) {
                    $this->addFlashMessage('error', $result);
                    $this->_redirect("/produto/edit/id/$params[id]/grade/$params[grade]");
                } else {
                    $this->em->flush();
                }

                $this->addFlashMessage('success', 'Produto alterado com sucesso.');
                $this->_redirect('/produto');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $form->setDefaultsFromEntity($entity); // pass values to form
        $fornecedorRefRepo = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');
        $this->view->codigosFornecedores = $fornecedorRefRepo->findBy(array('idProduto' => $entity->getIdProduto()));
        $repoEmbalagem = $this->_em->getRepository('wms:Produto\Embalagem');

        /** @var \Wms\Module\Web\Form\Subform\Produto\CodigoFornecedor $subFormCodForn */
        $subFormCodForn = $form->getSubForm('codigoFornecedor');

        /** @var Zend_Form_Element_Select $selectEmbalagem */
        $selectEmbalagem = $subFormCodForn->getElement('embalagem');

        $criterio = array(
            'codProduto' => $params['id'],
            'grade' => $params['grade']
        );

        $orderBy = array('isPadrao' => 'DESC', 'descricao' => 'ASC');

        $embalagens = $repoEmbalagem->findBy($criterio, $orderBy);
        $options = array();
        /** @var Produto\Embalagem $embalagem */
        foreach ($embalagens as $embalagem) {
            $options[$embalagem->getId()] = $embalagem->getDescricao() . "(" . $embalagem->getQuantidade() . ")";
        }

        $selectEmbalagem->setMultiOptions($options);
        $this->view->form = $form;
    }

    public function semCapacidadeAjaxAction() {
        $produtoRepo = $this->em->getRepository("wms:Produto");
        $produtosSemCapacidade = $produtoRepo->getProdutosEstoqueSemCapacidade();
        $this->exportPDF($produtosSemCapacidade, 'sem-capacidade.pdf', 'Produtos Sem Capacidade no Estoque', 'L');
    }

    public function codigoFornecedorAjaxAction() {
        $term = $this->getRequest()->getParam('term');
        /** @var $fornecedorRefRepo */
        $fornecedorRefRepo = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');
        $result = $fornecedorRefRepo->buscarFornecedorByNome($term);

        $this->_helper->json($result);
    }

    public function excluirCodFornecedorAjaxAction() {
        $id = $this->getRequest()->getParam('id');
        $fornecedorRefRepo = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');
        try {
            $fornEn = $fornecedorRefRepo->find($id);
            $this->_em->remove($fornEn);
            $this->_em->flush();
            $this->_helper->json(array('success'));
        } catch (Exception $e) {
            $this->_helper->json(array('msg' => $e->getMessage()));
        }
    }

    /**
     *
     */
    public function dadoLogisticoAjaxAction() {
        $params = $this->getRequest()->getParams();

        $em = $this->getEntityManager();
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $em->getRepository('wms:Produto');
        /** @var Produto\Andamento $andamentoRepository */
        $andamentoRepository = $em->getRepository("wms:Produto\Andamento");

        $this->view->id = $params['id'];
        $this->view->grade = $params['grade'];

        if (isset($params['clonar'])) {

            try {
                $usuarioId = \Zend_Auth::getInstance()->getIdentity()->getId();

                // migra dados logisticos
                if (!isset($params['gradeDe']))
                    throw new \Exception('Não há grades de origem para fazer a clonagem.');

                if (!isset($params['gradePara']))
                    throw new \Exception('Não há grades de destino para fazer a clonagem.');

                foreach ($params['gradePara'] as $gradePara) {
                    $produtoRepo->migrarDadoLogistico($params['id'], $params['gradeDe'], $gradePara, $usuarioId, $andamentoRepository);
                }

                $this->addFlashMessage('success', 'Dados logisticos migrados com sucesso.');
            } catch (\Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }

            $this->redirect('index', 'produto', null, array('id' => $params['id'], 'grade' => $params['grade']));
        }

        $this->view->produtos = $produtoRepo->buscarDadoLogistico($params['id']);
        $this->view->qtdProdutos = count($this->view->produtos);
        $this->view->descricao = $this->view->produtos[0][0]['descricao'];
    }

    /*
     * Exibe os dados logisticos do produto
     */

    public function viewProdutoAjaxAction() {
        $em = $this->getEntityManager();
        $params = $this->getRequest()->getParams();
        extract($params);

        if (($params['id'] == null) && ($params['grade'] == null))
            throw new \Exception('Codigo e Grade do produto devem ser fornecidos.');

        if ($params['idTipoComercializacao'] == null)
            throw new \Exception('Informe o tipo de comercialização do produto.');

        $produtoEntity = $this->repository->buscarDadosProduto(array('id' => $params['id'], 'grade' => $params['grade']));

        if ($produtoEntity == null) {
            throw new \Exception('Este produto não existe.');
        }

        $arrayEmbalagens = array();
        $normasPaletizacao = array();
        if ($params['idTipoComercializacao'] == ProdutoEntity::TIPO_UNITARIO) {
            $repoEmbalagem = $em->getRepository('wms:Produto\Embalagem');
            $embalagens = $repoEmbalagem->findBy(array('codProduto' => $params['id'], 'grade' => $params['grade']), array('isPadrao' => 'DESC', 'descricao' => 'ASC'));

            foreach ($embalagens as $embalagem) {
                $arrayEmbalagens[] = array(
                    'id' => $embalagem->getId(),
                    'descricao' => $embalagem->getDescricao(),
                    'quantidade' => $embalagem->getQuantidade(),
                    'isPadrao' => ($embalagem->getIsPadrao() == 'S') ? 'Sim' : 'Não',
                    'CBInterno' => ($embalagem->getCBInterno() == 'S') ? 'Sim' : 'Não',
                    'imprimirCB' => ($embalagem->getImprimirCB() == 'S') ? 'Sim' : 'Não',
                    'codigoBarras' => $embalagem->getCodigoBarras(),
                    'capacidadePicking' => $embalagem->getCapacidadePicking(),
                    'pontoReposicao' => $embalagem->getPontoReposicao(),
                    'largura' => $embalagem->getLargura(),
                    'altura' => $embalagem->getAltura(),
                    'profundidade' => $embalagem->getProfundidade(),
                    'cubagem' => $embalagem->getCubagem(),
                    'peso' => $embalagem->getPeso(),
                    'endereco' => ($embalagem->getEndereco()) ? $embalagem->getEndereco()->getDescricao() : '',
                );
            }

            $dql = $em->createQueryBuilder()
                    ->select('np.id, np.numLastro, np.numCamadas, np.numPeso, np.numNorma, np.isPadrao, 
                    u.id idUnitizador, u.descricao unitizador, e.id embalagem')
                    ->from('wms:Produto\Embalagem', 'e')
                    ->innerJoin('e.dadosLogisticos', 'dl')
                    ->innerJoin('dl.normaPaletizacao', 'np')
                    ->innerJoin('np.unitizador', 'u')
                    ->where('e.codProduto = ?1')
                    ->setParameter(1, $params['id'])
                    ->andWhere('e.grade = :grade')
                    ->setParameter('grade', $params['grade']);

            // loop para agrupar normas repetidas, já que a bosta do oracle não faz
            foreach ($dql->getQuery()->getResult() as $row) {
                $normasPaletizacao[$row['id']] = array(
                    'id' => $row['id'],
                    'numLastro' => $row['numLastro'],
                    'numCamadas' => $row['numCamadas'],
                    'numNorma' => $row['numNorma'],
                    'numPeso' => Converter::enToBr($row['numPeso'], 3),
                    'isPadrao' => ($row['isPadrao'] == 'S') ? 'Sim' : 'Não',
                    'idUnitizador' => $row['idUnitizador'],
                    'unitizador' => $row['unitizador'],
                    'embalagem' => $row['embalagem']
                );
            }

            $normasPaletizacao = array_values($normasPaletizacao);

            foreach ($normasPaletizacao as $key => $normaPaletizacao) {

                $dadosLogisticos = $em->getRepository('wms:Produto\DadoLogistico')
                        ->findBy(array('normaPaletizacao' => $normaPaletizacao['id'], 'embalagem' => $normaPaletizacao['embalagem']));

                foreach ($dadosLogisticos as $dadoLogistico) {

                    $idNormaPaletizacao = ($dadoLogistico->getNormaPaletizacao()) ? $dadoLogistico->getNormaPaletizacao()->getId() : 0;

                    $normasPaletizacao[$key]['dadosLogisticos'][] = array(
                        'id' => $dadoLogistico->getId(),
                        'idNormaPaletizacao' => $idNormaPaletizacao,
                        'idEmbalagem' => $dadoLogistico->getEmbalagem()->getId(),
                        'largura' => $dadoLogistico->getLargura(),
                        'altura' => $dadoLogistico->getAltura(),
                        'profundidade' => $dadoLogistico->getProfundidade(),
                        'cubagem' => $dadoLogistico->getCubagem(),
                        'peso' => $dadoLogistico->getPeso(),
                        'normaPaletizacao' => $dadoLogistico->getNormaPaletizacao()->getId(),
                        'dscEmbalagem' => $dadoLogistico->getEmbalagem()->getDescricao(),
                    );
                }
            }
        }


        $this->view->idProduto = $params['id'];
        $this->view->gradeProduto = $params['grade'];
        $this->view->produto = $produtoEntity[0];
        $this->view->produtoEmbalagens = $arrayEmbalagens;
        $this->view->embalagemNormasPaletizacao = $normasPaletizacao;

        $this->view->produtoUnitario = ProdutoEntity::TIPO_UNITARIO;
        $this->view->produtoComposto = ProdutoEntity::TIPO_COMPOSTO;
    }

    public function gerarEtiquetaAction() {
        $codProduto = $this->getRequest()->getParam('id');
        $grade = $this->getRequest()->getParam('grade');
        $idEmbalagens = $this->getRequest()->getParam('embalagens');
        $submit = $this->getRequest()->getParam("imprimir");

        $produtoRepo = $this->em->getRepository('wms:Produto');
        /** @var Produto $produtoEn */
        $produtoEn = $produtoRepo->findOneBy(['id' => $codProduto, 'grade' => $grade]);

        if ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO || !empty($submit)) {
            $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PRODUTO");
            $gerarEtiqueta = null;
            switch ($modelo) {
                case 1:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 50));
                    break;
                case 2:
                case 7:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 60));
                    break;
                case 3:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(75, 45));
                    break;
                case 4:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(113, 70));
                    break;
                case 5:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(60, 60));
                    break;
                case 6:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(120, 60));
                    break;
                case 8:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(100, 35));
                    break;
                case 14:
                    $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(115, 55));
                    break;
            }

            $gerarEtiqueta->init(null, array(
                'codProduto' => $codProduto,
                'grade' => $grade,
                'codProdutoEmbalagem' => (!empty($idEmbalagens)? implode(",", $idEmbalagens) : null)),
                $modelo, \Wms\Domain\Entity\Recebimento::TARGET_IMPRESSAO_PRODUTO);
        } else {
            /** @var Produto\Embalagem[] $embalagens */
            $embalagens =  $this->em->getRepository("wms:Produto\Embalagem")->findBy(['codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null]);
            $result = [];
            foreach ($embalagens as $embalagem) {
                if ($embalagem->getCodigoBarras() != null) {
                    $result[] = $embalagem;
                }
            }
            $this->view->embalagens = $result;
            $this->render('list-emb-etiqueta');
        }
    }

    public function verificarParametroCodigoBarrasAjaxAction() {
        $parametro = $this->getSystemParameterValue("ALTERAR_CODIGO_BARRAS");
        $this->_helper->json($parametro, true);
    }

    public function logAjaxAction() {
        $codProduto = $this->getRequest()->getParam('id');
        $grade = $this->getRequest()->getParam('grade');
        $orderBy = array('dataAndamento' => 'DESC');

        $andamentoRepo = $this->_em->getRepository('wms:Produto\Andamento');
        $produtoEn = $this->_em->getRepository('wms:Produto')->findOneBy(array('id' => $codProduto, 'grade' => $grade));
        $this->view->id = $codProduto;
        $this->view->grade = $grade;
        $this->view->produto = $produtoEn->getDescricao();
        ;
        $this->view->vetLog = $andamentoRepo->findBy(array('codProduto' => $codProduto, 'grade' => $grade), $orderBy);
    }

    /*
     * Verifica se ja existe o codigo de barras informado
     */

    public function verificarCodigoBarrasAjaxAction() {

        $codigoBarras = $this->getRequest()->getParam("codigoBarras");
        $idElemento = $this->getRequest()->getParam("idElemento");
        $tipoComercializacao = $this->getRequest()->getParam("tipoComercializacao");

        $arrayMensagen = array(
            'status' => 'success',
            'msg' => 'Sucesso!',
        );

        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('p.id idProduto, p.grade, NVL(pe.descricao, pv.descricao) dsc_elemento')
                ->from('wms:Produto', 'p')
                ->leftJoin('p.embalagens', 'pe')
                ->leftJoin('p.volumes', 'pv')
                ->where('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
                ->setParameter('codigoBarras', $codigoBarras);

        if ($tipoComercializacao == Produto::TIPO_UNITARIO) {
            $dql->andWhere("pe.id != :idElemento")
                ->setParameter('idElemento', $idElemento);
        } elseif ($tipoComercializacao == Produto::TIPO_COMPOSTO) {
            $dql->andWhere("pv.id != :idElemento")
                ->setParameter('idElemento', $idElemento);
        }

        $result = $dql->getQuery()->getResult();

        if (!empty($result)) {
            $arrItens = [];
            foreach ($result as $produto) {
                $arrItens[] = "<br /> - item $produto[idProduto] / $produto[grade] ($produto[dsc_elemento])";
            }
            $str = implode(", ", $arrItens);
            $arrayMensagen = array(
                'status' => 'error',
                'msg' => "Este código de barras ja foi cadastrado: $str."
            );

        }

        $this->_helper->json($arrayMensagen, true);
    }

    public function atualizaDadoLogisticoAjaxAction() {

        $this->_em->getRepository('wms:Produto')->getProdDadoLog();
        $this->_helper->json(array(), true);
    }

}
