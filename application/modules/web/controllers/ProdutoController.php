<?php

use Wms\Domain\Entity\Produto,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Core\Util\Converter,
    Wms\Module\Web\Form\Produto\Filtro as FiltroForm,
    Wms\Module\Web\Grid\Produto\DadoLogistico as DadoLogisticoGrid,
    Wms\Domain\Entity\Produto as ProdutoEntity;

/**
 * Description of Web_ProdutoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ProdutoController extends Crud {

    public $entityName = 'Produto';

    public function indexAction() {
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

                $lblEmbalagem = $dadoLogistico->getEmbalagem()->getDescricao();
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
                    'lblEmbalagem' => $lblEmbalagem,
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

        try {
            $params = $this->getRequest()->getParams();

            if (($params['id'] == null) && ($params['grade'] == null))
                throw new \Exception('Codigo e Grade do produto devem ser fornecidos');

            $entity = $this->repository->findOneBy(array('id' => $params['id'], 'grade' => $params['grade']));

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

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
                } else {
                    $entity->setDiasVidaUtil($params['produto']['diasVidaUtil']);
                }

                if ($params['produto']['pVariavel'] == 'N') {
                    $params['produto']['percTolerancia'] = null;
                    $params['produto']['toleranciaNominal'] = null;
                }

                $entity->setPossuiPesoVariavel($params['produto']['pVariavel']);
                $entity->setPercTolerancia($params['produto']['percTolerancia']);
                $entity->setToleranciaNominal($params['produto']['toleranciaNominal']);

                $result = $this->repository->save($entity, $this->getRequest()->getParams(), true);
                if (is_string($result)){
                    $this->addFlashMessage('error', $result);
                    $this->_redirect('/produto/edit/id/'.$params['id'].'/grade/'.$params['grade']);
                } else {
                    $this->em->flush();
                }

                $andamentoRepo  = $this->_em->getRepository('wms:Produto\Andamento');
                $andamentoRepo->save($params['id'], $params['grade'], false, 'Produto alterado com sucesso.');

                $this->addFlashMessage('success', 'Produto alterado com sucesso.');
                $this->_redirect('/produto');

            }
            $form->setDefaultsFromEntity($entity); // pass values to form
            $fornecedorRefRepo  = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');
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
            foreach ($embalagens as $embalagem){
                $options[$embalagem->getId()] = $embalagem->getDescricao() . "(" . $embalagem->getQuantidade() . ")";
            }

            $selectEmbalagem->setMultiOptions($options);

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->view->form = $form;
    }

    public function codigoFornecedorAjaxAction()
    {
        $term = $this->getRequest()->getParam('term');
        /** @var $fornecedorRefRepo */
        $fornecedorRefRepo  = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');
        $result = $fornecedorRefRepo->buscarFornecedorByNome($term);

        $this->_helper->json($result);
    }

    public function excluirCodFornecedorAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');
        $fornecedorRefRepo  = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');
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
        $produtoRepo = $em->getRepository('wms:Produto');

        $this->view->id = $params['id'];
        $this->view->grade = $params['grade'];

        if (isset($params['clonar'])) {

            try {

                // migra dados logisticos
                if (!isset($params['gradeDe']))
                    throw new \Exception('Não há grades de origem para fazer a clonagem.');

                if (!isset($params['gradePara']))
                    throw new \Exception('Não há grades de destino para fazer a clonagem.');

                foreach ($params['gradePara'] as $gradePara) {
                    $produtoRepo->migrarDadoLogistico($params['id'], $params['gradeDe'], $gradePara);
                }

                $this->_helper->messenger('success', 'Dados logisticos migrados com sucesso.');
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
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

    public function gerarEtiquetaPdfAction() {
        $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PRODUTO");
        $codProduto = $this->getRequest()->getParam('id');
        $grade = $this->getRequest()->getParam('grade');
        if ($this->getSystemParameterValue("MODELO_ETIQUETA_PRODUTO") == 1) {
            $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 50));
        } else {
            $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 60));
        }
        $result = $gerarEtiqueta->init(null,array(
            'codProduto' => $codProduto,
            'grade'      => $grade),
            $modelo);
    }

    public function verificarParametroCodigoBarrasAjaxAction()
    {
        $parametro = $this->getSystemParameterValue("ALTERAR_CODIGO_BARRAS");
        $this->_helper->json($parametro, true);
    }

}
