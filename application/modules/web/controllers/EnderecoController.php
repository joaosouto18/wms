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
            try {
                extract($values);
                $WhereruaI = $WhereruaF = $WherePredioI = $WherePredioF = $WhereNivelI = $WhereNivelF = $WhereAptoI = $WhereAptoF = ' 1 = 1';
                if (!empty($inicialRua))
                    $WhereruaI = "e.rua >= :inicilaRua";
                if (!empty($finalRua))
                    $WhereruaF = "e.rua <= :finalRua";
                if (!empty($inicialPredio))
                    $WherePredioI = "e.predio >= :inicilaPredio";
                if (!empty($finalPredio))
                    $WherePredioF = "e.predio <= :finalPredio";
                if (!empty($inicialNivel))
                    $WhereNivelI = "e.nivel >= :inicilaNivel";
                if (!empty($finalNivel))
                    $WhereNivelF = "e.nivel <= :finalNivel";
                if (!empty($inicialApartamento))
                    $WhereAptoI = "e.apartamento >= :inicilaApartamento";
                if (!empty($finalApartamento))
                    $WhereAptoF = "e.apartamento <= :finalApartamento";
                $source = $this->em->createQueryBuilder()
                    ->select("e, c.descricao as dscCaracteristica, a.descricao areaArmazenagem, ea.descricao estruturaArmazenagem, te.descricao as dscTipoEndereco, e.inventarioBloqueado
                                    , CASE WHEN e.bloqueadaEntrada = 1 and e.bloqueadaSaida = 1 THEN 'Entrada/Saída' 
                                           WHEN e.bloqueadaEntrada = 1 and e.bloqueadaSaida = 0 THEN 'Entrada' 
                                           WHEN e.bloqueadaEntrada = 0 and e.bloqueadaSaida = 1 THEN 'Saída' 
                                           ELSE 'Nada' END bloqueada")
                    ->from('wms:Deposito\Endereco', 'e')
                    ->innerJoin('e.caracteristica', 'c')
                    ->innerJoin('e.areaArmazenagem', 'a')
                    ->innerJoin('e.estruturaArmazenagem', 'ea')
                    ->innerJoin('e.tipoEndereco', 'te')
                    ->where("e.deposito = :idDeposito
                        AND ($WhereruaI AND $WhereruaF) 
                        AND ($WherePredioI AND $WherePredioF) 
                        AND ($WhereNivelI AND $WhereNivelF) 
                        AND ($WhereAptoI AND $WhereAptoF)")
                    ->orderBy('e.descricao')
                    ->setParameter('idDeposito', $this->view->idDepositoLogado);
                if (!empty($inicialRua))
                    $source->setParameter('inicilaRua', $inicialRua);
                if (!empty($finalRua))
                    $source->setParameter('finalRua', $finalRua);
                if (!empty($inicialPredio))
                    $source->setParameter('inicilaPredio', $inicialPredio);
                if (!empty($finalPredio))
                    $source->setParameter('finalPredio', $finalPredio);
                if (!empty($inicialNivel))
                    $source->setParameter('inicilaNivel', $inicialNivel);
                if (!empty($finalNivel))
                    $source->setParameter('finalNivel', $finalNivel);
                if (!empty($inicialApartamento))
                    $source->setParameter('inicilaApartamento', $inicialApartamento);
                if (!empty($finalApartamento))
                    $source->setParameter('finalApartamento', $finalApartamento);

                if (!empty($lado)) {
                    if ($lado == "P")
                        $source->andWhere("MOD(e.predio,2) = 0");
                    if ($lado == "I")
                        $source->andWhere("MOD(e.predio,2) = 1");
                }

                if ($bloqueadaEntrada === "0")
                    $source->andWhere("e.bloqueadaEntrada = 0");
                if ($bloqueadaEntrada === "1")
                    $source->andWhere("e.bloqueadaEntrada = 1");
                if ($bloqueadaSaida === "0")
                    $source->andWhere("e.bloqueadaSaida = 0");
                if ($bloqueadaSaida === "1")
                    $source->andWhere("e.bloqueadaSaida = 1");

                if (!empty($status))
                    $source->andWhere("e.status = :status")
                        ->setParameter('status', $status);
                if (!empty($ativo))
                    $source->andWhere("e.ativo = :ativo")
                        ->setParameter('ativo', $ativo);
                if (!empty($idCaracteristica))
                    $source->andWhere("e.idCaracteristica = :caracteristica")
                        ->setParameter("caracteristica", $idCaracteristica);
                if (!empty($idEstruturaArmazenagem))
                    $source->andWhere("e.idEstruturaArmazenagem = :estrutArmaz")
                        ->setParameter("estrutArmaz", $idEstruturaArmazenagem);
                if (!empty($idTipoEndereco))
                    $source->andWhere("e.idTipoEndereco = :tipoEnd")
                        ->setParameter("tipoEnd", $idTipoEndereco);
                if (!empty($idAreaArmazenagem))
                    $source->andWhere("e.idAreaArmazenagem = :areaArm")
                        ->setParameter("areaArm", $idAreaArmazenagem);

                $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
                $grid->addMassAction('edit', 'Editar');
                $grid->addMassAction('mass-delete', 'Remover');
                $grid->addMassAction('bloquear?destino=E', 'Bloquear Entrada');
                $grid->addMassAction('desbloquear?destino=E', 'Desbloquear Entrada');
                $grid->addMassAction('bloquear?destino=S', 'Bloquear Saída');
                $grid->addMassAction('desbloquear?destino=S', 'Desbloquear Saída');
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
                        'label' => 'Bloqueado p/',
                        'index' => 'bloqueada'
                    ))
                    ->addColumn(array(
                        'label' => 'Disponibilidade',
                        'index' => 'ativo',
                        'render' => 'AtivoOrInativo'
                    ))
                    ->addAction(array(
                        'label' => 'Editar',
                        'actionName' => 'edit',
                        'pkIndex' => 'id',
                    ))
                    ->addAction(array(
                        'label' => 'Bloquear Entrada',
                        'actionName' => 'bloquear',
                        'pkIndex' => 'id',
                        'params' => ['destino' => 'E'],
                        'condition' => function ($row) {
                            return (empty($row['bloqueadaEntrada']) && !in_array($row['idCaracteristica'],[Endereco::PICKING, Endereco::PICKING_DINAMICO]));
                        }
                    ))
                    ->addAction(array(
                        'label' => 'Desbloquear Entrada',
                        'actionName' => 'desbloquear',
                        'pkIndex' => 'id',
                        'params' => ['destino' => 'E'],
                        'condition' => function ($row) {
                            return !empty($row['bloqueadaEntrada']);
                        }
                    ))
                    ->addAction(array(
                        'label' => 'Bloquear Saída',
                        'actionName' => 'bloquear',
                        'pkIndex' => 'id',
                        'params' => ['destino' => 'S'],
                        'condition' => function ($row) {
                            return (empty($row['bloqueadaSaida']) && !in_array($row['idCaracteristica'],[Endereco::PICKING, Endereco::PICKING_DINAMICO]));
                        }
                    ))
                    ->addAction(array(
                        'label' => 'Desbloquear Saída',
                        'actionName' => 'desbloquear',
                        'pkIndex' => 'id',
                        'params' => ['destino' => 'S'],
                        'condition' => function ($row) {
                            return !empty($row['bloqueadaSaida']);
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
            } catch (Exception $e) {
                $this->addFlashMessage("Error", $e->getMessage());
            }
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
                    $arrayParams = $this->getRequest()->getParams();
                    foreach ($massId as $id) {
                        if ($arrayParams['identificacao']['ativo'] == 'N')
                            $this->repository->validaInativacaoEndereco($id);

                        $arrayParams['identificacao']['id'] = $id;
                        $this->repository->save(null, $arrayParams);
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
        if (empty($massId)){
            $id = $this->_getParam('id');
            if (!empty($id)) $massId[] = $id;
        }
        $destino = $this->_getParam('destino');
        try {
            $this->em->beginTransaction();

            $check = $this->repository->validaEnderecosComReservas($massId);
            if (!empty($check)) {
                $str = implode(", ", $check);
                throw new Exception("Endereços com reservas pendentes não podem ser bloqueados: $str");
            }

            $check = $this->repository->validaEnderecosPicking($massId);
            if (!empty($check)) {
                $str = implode(", ", $check);
                throw new Exception("Endereços do tipo Picking ou Picking Dinâmico não podem ser bloqueados: $str");
            }
            foreach ($massId as $id) {
                /** @var Endereco $entity */
                $entity = $this->repository->findOneBy(array($this->pkField => $id));

                if ($destino == 'E') $entity->setBloqueadaEntrada(true);
                if ($destino == 'S') $entity->setBloqueadaSaida(true);
                $this->em->persist($entity);
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect('index');
    }

    /**
     *
     */
    public function desbloquearAction()
    {
        $massId = $this->_getParam('mass-id');
        if (empty($massId)){
            $id = $this->_getParam('id');
            if (!empty($id)) $massId[] = $id;
        }
        $destino = $this->_getParam('destino');
        try {
            foreach ($massId as $id) {
                /** @var Endereco $entity */
                $entity = $this->repository->findOneBy(array($this->pkField => $id));
                if ($destino == 'E') $entity->setBloqueadaEntrada(false);
                if ($destino == 'S') $entity->setBloqueadaSaida(false);
                $this->em->persist($entity);
            }
            $this->em->flush();

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect('index');
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

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect('index');
    }

    /**
     *
     */
    public function desativarAction()
    {
        $massId = $this->_getParam('mass-id');
        try {

            foreach ($massId as $id) {
                $this->repository->validaInativacaoEndereco($id);
                $entity = $this->repository->findOneBy(array($this->pkField => $id));
                $entity->setAtivo('N');
                $this->em->persist($entity);
            }
            $this->em->flush();

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect('index');
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

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect('index');
    }

    public function disponinativarAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id deve ser enviado pra desativar');

            $this->repository->validaInativacaoEndereco($id);

            $entity = $this->repository->findOneBy(array($this->pkField => $id));
            $entity->setAtivo('N');
            $this->em->persist($entity);
            $this->em->flush();

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        $this->redirect('index');
    }

    /*
     * Verifica se existe o endereco informado
     */
    public function verificarEnderecoAjaxAction()
    {
        $valores = $this->_getParam('valores');
        $endereco = $valores['endereco'];
        $codProduto = $valores['idProduto'];
        $grade = $valores['grade'];

        $enderecoFormatado = \Wms\Util\Endereco::formatar($endereco);
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $depositoEnderecoRepo */
        $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        /** @var Endereco $depositoEnderecoEn */
        $depositoEnderecoEn = $depositoEnderecoRepo->findOneBy(array('descricao' => $enderecoFormatado));

        $arrayMensagens = array( 'status' => 'success' );

        if (empty($depositoEnderecoEn)) {
            $arrayMensagens = array('status' => 'error', "msg" => "Endereço $endereco não encontrado!");
        } else{
            $test = $depositoEnderecoEn->liberadoPraSerPicking(true);
            if (is_string($test)) {
                $arrayMensagens = array('status' => 'error', "msg" => $test);
            } else if ($depositoEnderecoEn->getCaracteristica()->getId() == Endereco::PICKING || $depositoEnderecoEn->getCaracteristica()->getId() == Endereco::PICKING_DINAMICO) {
                if ($this->getSystemParameterValue('PERMITE_NPRODUTO_PICKING') == 'N') {
                    $produto = $depositoEnderecoRepo->getProdutoByEndereco($enderecoFormatado, true, true);
                    if (!empty($produto) && ($codProduto != $produto[0]['codProduto'] || $grade != $produto[0]['grade'])) {
                        $arrayMensagens = array('status' => 'error', "msg" => "Endereço $endereco já está vinculado ao produto: " . $produto[0]['codProduto'] . "<br />" . $produto[0]['descricao'] . "<br />Grade: " . $produto[0]['grade']);
                    }
                }
            }else{
                $arrayMensagens = array('status' => 'error', "msg" => "Endereço $endereco não é um endereço de picking.");
            }
        }

        $this->_helper->json($arrayMensagens);
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

        if ($modelo == 14) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(115, 55));
        } else if ($modelo == 16) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(120, 60));
        } else if (($modelo == 4) || ($modelo == 6) || $modelo == 13 || $modelo == 15) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(110, 60));
        } else if ($modelo == 13) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(100, 27));
        } else if ($modelo == 17) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(100, 35));
        } else{
            $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
        }
        $etiqueta->imprimir($enderecos, $modelo);
    }

    public function verificarEstoqueAjaxAction()
    {
        $endereco = $this->_getParam('enderecoAntigo');
        $grade = $this->_getParam('grade');
        $idProduto = $this->_getParam('produto');

        if (!empty($endereco)) {
            $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
            $depositoEnderecoEn = $depositoEnderecoRepo->findOneBy(array('descricao' => $endereco));
            $idDepositoEndereco = $depositoEnderecoEn->getId();

            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
            $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'codProduto' => $idProduto, 'grade' => "$grade"));

            //verificar se tem outra embalagem do mesmo produto cadastrado para esse piking e qual a ação tomada
            if (!empty($estoqueEn)) {
                if ($estoqueEn->getQtd() > 0) {
                    $arrayMensagens = array(
                        'status' => 'error',
                        'msg' => 'Não é possível apagar um produto com estoque no picking.',
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

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $endRepo */
        $endRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        $enderecos = $endRepo->findAll();

        /** @var \Wms\Domain\Entity\Deposito\Endereco $endereço */
        foreach ($enderecos as $endereco){
            $formatado = \Wms\Util\Endereco::formatar($endereco->getDescricao());
            $endereco->setDescricao($formatado);
            $this->_em->persist($endereco);
        }
        $this->_em->flush();
        $this->addFlashMessage('success', 'Endereços formatados');
        $baseUrl = new Zend_View_Helper_BaseUrl();
        $this->redirect($baseUrl->getBaseUrl());
    }
}