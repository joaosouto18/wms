<?php

use \Wms\Module\Web\Controller\Action\Crud,
    \Wms\Module\Web\Page;

/**
 * Description of Web_PerfilUsuarioController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_PerfilUsuarioController extends Crud
{

    protected $entityName = 'Acesso\Perfil';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
                ->select('pu')
                ->from('wms:Acesso\Perfil', 'pu')
                ->orderBy('pu.nome');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->addColumn(array(
                    'label' => 'Perfil',
                    'index' => 'nome',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'actionName' => 'delete',
                    'pkIndex' => 'id',
                    'cssClass' => 'del'
                ))
                ->setHasOrdering(true);

        $this->view->grid = $grid->build();
    }

    public function preDispatch()
    {
        parent::preDispatch();
    }

    /**
     *
     * @return type 
     */
    public function addAction()
    {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'onclick' => 'window.history.back()',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        $form = new $formClass;
        $params = $this->getRequest()->getParams();

        if ($this->getRequest()->isPost()) {
            $entity = new \Wms\Domain\Entity\Acesso\Perfil;
            $this->repository->save($entity, $params);
            $this->em->flush();
            $this->_helper->messenger('success', 'Registro inserido com sucesso');
            return $this->redirect('index');
        }

        $this->view->form = $form;
    }

    public function editAction()
    {

        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'onclick' => 'window.history.back()',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
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
            )
        ));

        //finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        $form = new $formClass;
        $params = $this->getRequest()->getParams();

        $id = $this->getRequest()->getParam('id');

        if ($id == null)
            throw new \Exception('Id must be provided for the edit action');

        $entity = $this->repository->findOneBy(array('id' => $id));

        if ($this->getRequest()->isPost() && $form->isValid($params)) {
            $this->repository->save($entity, $params);
            $this->em->flush();
            $this->_helper->messenger('success', 'Registro alterado com sucesso');
            return $this->redirect('index');
        }

        $form->setDefaultsFromEntity($entity); // pass values to form

        $this->view->form = $form;
    }

    /**
     *
     * @return type 
     */
    public function permissoesJsonAction()
    {
        $conn = $this->em->getConnection();
        $codPerfil = $this->getRequest()->getParam('codPerfil');
        $sql = "
            SELECT 
                'F' AS \"TYPE\",
                (MI.COD_MENU_ITEM * -1) AS \"KEY\",
                (MI.COD_PAI * -1) AS \"COD_PAI\", 
                MI.DSC_MENU_ITEM AS \"DESCRICAO\", 
                A.NOM_ACAO,
                (
                    SELECT 1
                    FROM PERFIL_USUARIO_RECURSO_ACAO PURA
                    WHERE PURA.COD_PERFIL_USUARIO = " . (int) $codPerfil . "
                    AND PURA.COD_RECURSO_ACAO = RA.COD_RECURSO_ACAO
                ) AS FLAG,
                CASE WHEN (MI.COD_RECURSO_ACAO = 0) THEN 0 ELSE 1 END \"CHECKBOX\"
            FROM MENU_ITEM MI
            LEFT JOIN RECURSO_ACAO RA 
                ON (RA.COD_RECURSO_ACAO = MI.COD_RECURSO_ACAO)
            LEFT JOIN RECURSO R
                ON (R.COD_RECURSO = RA.COD_RECURSO)
            LEFT JOIN ACAO A
                ON (A.COD_ACAO = RA.COD_ACAO)

            UNION ALL 

            SELECT 
                'P' AS \"TYPE\",
                RA2.COD_RECURSO_ACAO AS \"KEY\",
                (MI.COD_MENU_ITEM * -1) AS \"COD_PAI\", 
                RA2.DSC_RECURSO_ACAO AS \"DESCRICAO\", 
                A.NOM_ACAO,
                (
                    SELECT 1
                    FROM PERFIL_USUARIO_RECURSO_ACAO PURA
                    WHERE PURA.COD_PERFIL_USUARIO = " . (int) $codPerfil . "
                    AND PURA.COD_RECURSO_ACAO = RA2.COD_RECURSO_ACAO
                ) AS FLAG,
                1 AS \"CHECKBOX\"
            FROM MENU_ITEM MI
            LEFT JOIN RECURSO_ACAO RA 
                ON (RA.COD_RECURSO_ACAO = MI.COD_RECURSO_ACAO)
            LEFT JOIN RECURSO_ACAO RA2 
                ON (RA2.COD_RECURSO = RA.COD_RECURSO)
            LEFT JOIN RECURSO R
                ON (R.COD_RECURSO = RA2.COD_RECURSO)
            LEFT JOIN ACAO A
                ON (A.COD_ACAO = RA2.COD_ACAO)
            WHERE MI.COD_RECURSO_ACAO > 0
            ORDER BY COD_PAI ASC, DESCRICAO";

        $stmt = $conn->query($sql);
        $recursos = array();

        //Agrupa todos os recursos principais com suas acoes: Recurso => [acao, acao, ...]
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            //Evita atribuir o mesmo recurso mais de uma vez
            $recursos[$row['KEY']] = array(
                'id' => $row['KEY'],
                'key' => ($row['TYPE'] == 'P') ? $row['KEY'] : $row['TYPE'],
                'parent_id' => $row['COD_PAI'],
                'select' => ($row['FLAG'] == 1) ? true : false,
                'title' => $row['DESCRICAO'],
                'isFolder' => true,
                //'expand' => true,
                'acao' => false,
                'hideCheckbox' => false,
            );
        }

        //itere na matriz definindo elementos filhos e pais
        $iteraArvore = function ($arvore, $idPai = 0) use (&$iteraArvore) {
                    $nos = array();
                    foreach ($arvore as $no) {
                        if ($no['parent_id'] == $idPai) {
                            $filhos = $iteraArvore($arvore, $no['id']);

                            if (count($filhos) > 0) {
                                if (!isset($no['children'])) {
                                    $no['children'] = $filhos;
                                } else {
                                    if ($no['acao']) {
                                        $no['children'][] = $filhos;
                                    } else {
                                        $no['children'] = $filhos;
                                    }
                                }
                            }

                            $nos[] = $no;
                        }
                    }
                    return $nos;
                };

        $this->_helper->json($iteraArvore($recursos), true);
    }

}
