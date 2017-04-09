<?php

use \Wms\Domain\Entity\Deposito\Endereco,
    \Wms\Module\Web\Controller\Action\Crud,
    \Wms\Module\Armazenagem\Printer\EtiquetaEndereco,
    \Wms\Module\Web\Page;

/**
 * Description of Web_EnderecoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_EnderecoController extends Crud
{

    protected $entityName = 'Deposito\Endereco';

    public function indexAction()
    {
        $form = new Wms\Module\Web\Form\Deposito\Endereco\Filtro;
        $form->setAttrib('class', 'filtro')
            ->setAttrib('method', 'post');

        if ($values = $form->getParams()) {

            extract($values['identificacao']);

            $source = $this->em->createQueryBuilder()
                ->select("e, c.descricao as dscCaracteristica, a.descricao areaArmazenagem, ea.descricao estruturaArmazenagem, te.descricao as dscTipoEndereco, e.inventarioBloqueado")
                ->from('wms:Deposito\Endereco', 'e')
                ->innerJoin('e.caracteristica', 'c')
                ->innerJoin('e.areaArmazenagem', 'a')
                ->innerJoin('e.estruturaArmazenagem', 'ea')
                ->innerJoin('e.tipoEndereco', 'te')
                ->where("e.deposito = :idDeposito
                        AND (e.rua BETWEEN :inicilaRua AND :finalRua) 
                        AND (e.predio BETWEEN :inicilaPredio AND :finalPredio) 
                        AND (e.nivel BETWEEN :inicilaNivel AND :finalNivel) 
                        AND (e.apartamento BETWEEN :inicilaApartamento AND :finalApartamento)")
                ->orderBy('e.descricao')
                ->setParameter('idDeposito', $this->view->idDepositoLogado)
                ->setParameter('inicilaRua', $inicialRua)
                ->setParameter('finalRua', $finalRua)
                ->setParameter('inicilaPredio', $inicialPredio)
                ->setParameter('finalPredio', $finalPredio)
                ->setParameter('inicilaNivel', $inicialNivel)
                ->setParameter('finalNivel', $finalNivel)
                ->setParameter('inicilaApartamento', $inicialApartamento)
                ->setParameter('finalApartamento', $finalApartamento);

            if (!empty($lado)) {
                if ($lado == "P")
                    $source->andWhere("MOD(e.predio,2) = 0");
                if ($lado == "I")
                    $source->andWhere("MOD(e.predio,2) = 1");
            }
            if (!empty($situacao))
                $source->andWhere("e.situacao = :situacao")
                    ->setParameter('situacao', $situacao);
            if (!empty($status))
                $source->andWhere("e.status = :status")
                    ->setParameter('status', $status);
            if (!empty($idCaracteristica))
                $source->andWhere("e.idCaracteristica = ?1")
                    ->setParameter(1, $idCaracteristica);
            if (!empty($idEstruturaArmazenagem))
                $source->andWhere("e.idEstruturaArmazenagem = ?2")
                    ->setParameter(2, $idEstruturaArmazenagem);
            if (!empty($idTipoEndereco))
                $source->andWhere("e.idTipoEndereco = ?4")
                    ->setParameter(4, $idTipoEndereco);
            if (!empty($idAreaArmazenagem))
                $source->andWhere("e.idAreaArmazenagem = ?3")
                    ->setParameter(3, $idAreaArmazenagem);

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addMassAction('edit', 'Editar');
            $grid->addMassAction('mass-delete', 'Remover');
            $grid->addMassAction('bloquear', 'Bloquear');
            $grid->addMassAction('desbloquear', 'Desbloquear');
            $grid->addMassAction('ativar', 'Ativar');
            $grid->addMassAction('desativar', 'Desativar');
            $grid->addColumn(array(
                'label' => 'Endereço',
                'index' => 'descricao'
            ))
                ->addColumn(array(
                    'label' => 'Área de Armazenagem',
                    'index' => 'areaArmazenagem'
                ))
                ->addColumn(array(
                    'label' => 'Característica',
                    'index' => 'dscCaracteristica'
                ))
                ->addColumn(array(
                    'label' => 'Estrutura Armazenagem',
                    'index' => 'estruturaArmazenagem'
                ))
                ->addColumn(array(
                    'label' => 'Tipo Endereço',
                    'index' => 'dscTipoEndereco'
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'status',
                    'render' => 'OcupadoOrDisponivel'
                ))
                ->addColumn(array(
                    'label' => 'Situação',
                    'index' => 'situacao',
                    'render' => 'BloqueadoOrDesbloqueado'
                ))
                ->addColumn(array(
                    'label' => 'Disponibilidade',
                    'index' => 'ativo',
                    'render' => 'AtivoOrInativo'
                ))
                ->addcolumn(array(
                    'label' => 'En Bloqueado',
                    'index' => 'inventarioBloqueado',
                    'render' => 'SimOrNao'
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'actionName' => 'edit',
                    'pkIndex' => 'id',
                ))
                ->addAction(array(
                    'label' => 'Bloquear',
                    'actionName' => 'bloquear',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['situacao'] == 'D';
                    }
                ))
                ->addAction(array(
                    'label' => 'Desbloquear',
                    'actionName' => 'desbloquear',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['situacao'] == 'B';
                    }
                ))
                ->addAction(array(
                    'label' => 'Status Ativar',
                    'actionName' => 'ativar',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['status'] == 'O';
                    }
                ))
                ->addAction(array(
                    'label' => 'Status Desativar',
                    'actionName' => 'desativar',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['status'] == 'D';
                    }
                ))
                ->addAction(array(
                    'label' => 'Disponibilidade Ativar',
                    'actionName' => 'disponativar',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['ativo'] == 'N';
                    }
                ))
                ->addAction(array(
                    'label' => 'Disponibilidade Inativar',
                    'actionName' => 'disponinativar',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['ativo'] == 'S';
                    }
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'actionName' => 'delete',
                    'pkIndex' => 'id',
                    'cssClass' => 'del'
                ))
                ->addAction(array(
                    'label' => 'Etiqueta modelo 1',
                    'actionName' => 'imprimir',
                    'pkIndex' => 'descricao',
                    'cssClass' => 'pdf',
                    'params' => array('modelo' => '1')
                ))
                ->addAction(array(
                    'label' => 'Etiqueta modelo 2',
                    'actionName' => 'imprimir',
                    'pkIndex' => 'descricao',
                    'cssClass' => 'pdf',
                    'params' => array('modelo' => '2')
                ));

            $this->view->grid = $grid->build();
            $form->setSession($values)
                ->populate($values);
        }

        $this->view->form = $form;
    }

    /**
     * Edita um registro
     * @return void
     */
    public function editAction()
    {



        //finds the form class from the entity name
        $form = new Wms\Module\Web\Form\Deposito\Endereco;
        //bloqueio elementos na edicao
        $elements = $form->getSubForm('identificacao')->getElements();
        $elements['inicialRua']->setAttrib('readonly', 'readonly');
        $elements['finalRua']->setAttrib('readonly', 'readonly');
        $elements['inicialPredio']->setAttrib('readonly', 'readonly');
        $elements['finalPredio']->setAttrib('readonly', 'readonly');
        $elements['inicialNivel']->setAttrib('readonly', 'readonly');
        $elements['finalNivel']->setAttrib('readonly', 'readonly');
        $elements['inicialApartamento']->setAttrib('readonly', 'readonly');
        $elements['finalApartamento']->setAttrib('readonly', 'readonly');
        $elements['lado']->setAttrib('disabled', 'disabled');

        $hasId = false;

        $id = $this->_getParam('id');
        if (!empty($id)){
            $hasId = true;
        }
        $parms = $this->getRequest()->getPost();
        if (isset($parms['identificacao']) && empty($id)){
            $id = $parms['identificacao']['id'];
        }
        $massId = $this->_getParam('mass-id');
        if (!empty($id) && empty($massId)){
            $massId = explode('-', $id);
        }

        try {
            if (!empty($massId)) {
                if ($this->getRequest()->isPost() && $form->isValid($parms)) {
                    foreach ($massId as $id) {
                        $entity = $this->repository->findOneBy(array($this->pkField => $id));
                        $this->repository->save($entity, $this->getRequest()->getParams());
                        $this->em->persist($entity);
                    }
                    $this->em->flush();
                    $this->_helper->messenger('success', 'Registros alterados com sucesso');
                    $this->redirect('index');
                }
                $form->setMassDefaultsFromEntity($massId, $this->repository);
                //array('id' => implode('-',$massId))
            } else {
                throw new Exception('Selecione ao menos um endereço');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('index');
        }
        $this->view->form = $form;

        //adding default buttons to the page

        $arr = array(
            array(
                'label' => 'Voltar',
                'cssClass' => 'btnBack',
                'urlParams' => array(
                    'action' => 'index',
                    'id' => null
                ),
                'tag' => 'a'
            ),
            array(
                'label' => 'Adicionar novo',
                'cssClass' => 'btnAdd',
                'urlParams' => array(
                    'action' => 'add'
                ),
                'tag' => 'a'
            ),
            array(
                'label' => 'Excluir',
                'cssClass' => 'btnDelete',
                'urlParams' => array(
                    'action' => 'delete'
                ),
                'tag' => 'a'
            ),
            array(
                'label' => 'Salvar',
                'cssClass' => 'btnSave'
            )
        );

        if ($hasId == false) {
            unset($arr[2]);
        }

        Page::configure(array(
            'buttons' => $arr
        ));
    }

    /**
     *
     */
    public function bloquearAction()
    {
        $massId = $this->_getParam('mass-id');
        try {
            foreach ($massId as $id) {
                $entity = $this->repository->findOneBy(array($this->pkField => $id));
                $entity->setSituacao('B');
                $this->em->persist($entity);
            }
            $this->em->flush();

            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    /**
     *
     */
    public function desbloquearAction()
    {
        $massId = $this->_getParam('mass-id');
        try {
            foreach ($massId as $id) {
                $entity = $this->repository->findOneBy(array($this->pkField => $id));
                $entity->setSituacao('D');
                $this->em->persist($entity);
            }
            $this->em->flush();

            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    /**
     *
     */
    public function listarExistentesJsonAction()
    {
        $params = $this->getRequest()->getParams();
        extract($params['identificacao']);

        $dql = $this->em->createQueryBuilder()
            ->select("e.id, e.descricao as endereco,
                          c.descricao as dscCaracteristica, a.descricao areaArmazenagem, ea.descricao estruturaArmazenagem")
            ->from('wms:Deposito\Endereco', 'e')
            ->innerJoin('e.caracteristica', 'c')
            ->innerJoin('e.areaArmazenagem', 'a')
            ->innerJoin('e.estruturaArmazenagem', 'ea')
            ->where("e.deposito = :idDeposito
                        AND (e.rua BETWEEN :inicilaRua AND :finalRua) 
                        AND (e.predio BETWEEN :inicilaPredio AND :finalPredio) 
                        AND (e.nivel BETWEEN :inicilaNivel AND :finalNivel) 
                        AND (e.apartamento BETWEEN :inicilaApartamento AND :finalApartamento)")
            ->orderBy('endereco')
            ->setParameter('idDeposito', $this->view->idDepositoLogado)
            ->setParameter('inicilaRua', $inicialRua)
            ->setParameter('finalRua', $finalRua)
            ->setParameter('inicilaPredio', $inicialPredio)
            ->setParameter('finalPredio', $finalPredio)
            ->setParameter('inicilaNivel', $inicialNivel)
            ->setParameter('finalNivel', $finalNivel)
            ->setParameter('inicilaApartamento', $inicialApartamento)
            ->setParameter('finalApartamento', $finalApartamento);

        if (!empty($lado)) {
            if ($lado == "P")
                $dql->andWhere("MOD(e.predio,2) = 0");
            if ($lado == "I")
                $dql->andWhere("MOD(e.predio,2) = 1");
        }

        $enderecos = $dql->getQuery()->execute();
        $arrayEnderecos = array();

        foreach ($enderecos as $endereco) {
            $arrayEnderecos[] = array(
                'id' => $endereco['id'],
                'endereco' => $endereco['endereco'],
                'dscCaracteristica' => $endereco['dscCaracteristica'],
                'areaArmazenagem' => $endereco['areaArmazenagem'],
                'estruturaArmazenagem' => $endereco['estruturaArmazenagem'],
                'acao' => 'null',
            );
        }

        $this->_helper->json($arrayEnderecos, true);
    }

    /**
     *
     */
    public function ativarAction()
    {
        $massId = $this->_getParam('mass-id');

        try {
        foreach ($massId as $id) {
            $entity = $this->repository->findOneBy(array($this->pkField => $id));
            $entity->setAtivo('S');
            $this->em->persist($entity);
        }
        $this->em->flush();

        return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    /**
     *
     */
    public function desativarAction()
    {
        $massId = $this->_getParam('mass-id');
        try {

            foreach ($massId as $id) {
                $entity = $this->repository->findOneBy(array($this->pkField => $id));
                $entity->setAtivo('N');
                $this->em->persist($entity);
            }
            $this->em->flush();

            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function disponativarAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id deve ser enviado pra desativar');

            $entity = $this->repository->findOneBy(array($this->pkField => $id));
            $entity->setAtivo('S');
            $this->em->persist($entity);
            $this->em->flush();

            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function disponinativarAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id deve ser enviado pra desativar');

            $entity = $this->repository->findOneBy(array($this->pkField => $id));
            $entity->setAtivo('N');
            $this->em->persist($entity);
            $this->em->flush();

            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    /*
     * Verifica se existe o endereco informado
     */
    public function verificarEnderecoAjaxAction()
    {

        $em = $this->getEntityManager();
        $params = $this->getRequest()->getParams();
        extract($params);

        $arrayMensagens = array(
            'status' => 'success',
            'msg' => 'Sucesso!',
        );

        try {
            $enderecoFormatado = \Wms\Util\Endereco::formatar($endereco);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
            $enderecoEn = $enderecoRepo->findBy(array('descricao' => $enderecoFormatado));

            if (empty($enderecoEn)) {
                throw new \Exception('Este Endereço não existe.');
            }
        } catch (\Exception $e) {
            $arrayMensagens = array(
                'status' => 'error',
                'msg' => $e->getMessage(),
            );
        }

        $this->_helper->json($arrayMensagens, true);
    }

    public function imprimirAction() {

        $numEtiqueta = $this->_getParam("descricao");
        $idProduto = $this->_getParam("id");
        $grade = $this->_getParam("grade");
        $alocados = $this->_getParam("alocado");
        $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PICKING");

        if ($modelo == NULL) {
            $modelo = "1";
        }

        if ($numEtiqueta != NULL) {
            $enderecos = array(array('DESCRICAO' => $numEtiqueta));
        } else {
            $em = $this->getEntityManager();
            $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');

            if ($alocados != NULL) {
                $enderecos = $enderecoRepo->getEnderecosAlocados();
            } else {
                if (($idProduto != NULL) && ($grade != NULL)) {
                    $enderecos = $enderecoRepo->getEnderecoByProduto($idProduto, $grade);
                } else {
                    $enderecos = $enderecoRepo->getPicking();
                }
            }
        }

        if ($modelo == 4) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(110, 60));
        } else {
            $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
        }
        $etiqueta->imprimir($enderecos, $modelo);
    }

    public function verificarEstoqueAjaxAction()
    {
        $endereco = $this->_getParam('enderecoAntigo');
        $grade = $this->_getParam('grade');
        $idProduto = $this->_getParam('produto');

        if (isset($endereco) && !empty($endereco)) {
            $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
            $depositoEnderecoEn = $depositoEnderecoRepo->findOneBy(array('descricao' => $endereco));
            $idDepositoEndereco = $depositoEnderecoEn->getId();

            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
            $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'codProduto' => $idProduto, 'grade' => "$grade"));

            if (isset($estoqueEn)) {
                if ($estoqueEn->getQtd() > 0) {
                    $arrayMensagens = array(
                        'status' => 'error',
                        'msg' => 'Não é possível apagar/alterar um endereço com estoque no picking.',
                    );
                } else {
                    $arrayMensagens = array(
                        'status' => 'success',
                        'msg' => 'sucesso',
                    );
                }
            } else {
                $arrayMensagens = array(
                    'status' => 'success',
                    'msg' => 'sucesso',
                );
            }

        } else {
            $arrayMensagens = array(
                'status' => 'success',
                'msg' => 'sucesso',
            );
        }
        $this->_helper->json($arrayMensagens, true);
    }

    public function corrigirEnderecoAjaxAction()
    {
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $endRepo */
        $endRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        $endereços = $endRepo->findAll();

        /** @var \Wms\Domain\Entity\Deposito\Endereco $endereço */
        foreach ($endereços as $endereço){
            $formatado = \Wms\Util\Endereco::formatar($endereço->getDescricao());
            $endereço->setDescricao($formatado);
            $this->_em->persist($endereço);
        }
        $this->_em->flush();
        $this->addFlashMessage('success', 'Endereços formatados');
        $baseUrl = new Zend_View_Helper_BaseUrl();
        $this->redirect($baseUrl->getBaseUrl());
    }
}