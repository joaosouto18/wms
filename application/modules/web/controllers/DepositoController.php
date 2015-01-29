<?php

use \Wms\Domain\Entity\Deposito,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of Web_DepositoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_DepositoController extends Crud
{

    protected $entityName = 'Deposito';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
                ->select('d, f.id as idFilial, j.nomeFantasia')
                ->from('wms:Deposito', 'd')
                ->innerJoin('d.filial', 'f')
                ->innerJoin('f.juridica', 'j')
                ->orderBy('d.descricao');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('box-grid');
        $grid->addColumn(array(
                    'label' => 'Código do Depósito',
                    'index' => 'id',
                    'filter' => array(
                        'render' => array(
                            'type' => 'number',
                            'range' => true,
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Filial',
                    'index' => 'nomeFantasia',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Descrição do Depósito',
                    'index' => 'descricao',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Ativo',
                    'index' => 'isAtivo',
                    'render' => 'SimOrNao',
                    'filter' => array(
                        'render' => array(
                            'type' => 'select',
                            'attributes' => array(
                                'multiOptions' => array('SIM' => 'SIM', 'NÃO' => 'NÃO')
                            )
                        ),
                    ),
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->setHasOrdering(true);

        $desativar = new \Core\Grid\Action(array(
                    'label' => 'Desativar',
                    'actionName' => 'desativar',
                    'pkIndex' => 'id',
                    'cssClass' => 'confirm'
                ));

        $desativar->setCondition('\Wms\Module\Web\Grid\Condition::isAtivo');
        
        $ativar = new \Core\Grid\Action(array(
                    'label' => 'Ativar',
                    'actionName' => 'ativar',
                    'pkIndex' => 'id',
                    'cssClass' => 'confirm'
                ));

        $ativar->setCondition('\Wms\Module\Web\Grid\Condition::isInativo');
        
        $grid->addAction($ativar)
                ->addAction($desativar);

        $this->view->grid = $grid->build();
    }

    /**
     *
     * @return type 
     */
    public function addAction()
    {
        $filiais = $this->em->getRepository('wms:Filial')->findAll();

        if (count($filiais) > 0) {
            parent::addAction();
        } else {
            $this->addFlashMessage('error', 'Para cadastrar um depósito, é necessário que haja ao menos uma filial cadastrada');
            return $this->redirect('index');
        }
    }

    /**
     * Muda o depósito logado
     */
    public function mudarDepositoLogadoAction()
    {
        $sessao = new \Zend_Session_Namespace('deposito');
        $idDeposito = $this->request('id');

        if (null == $idDeposito) {
            throw new \Exception('ID de depósito inválido');
        }

        $deposito = $this->em->find('wms:Deposito', $idDeposito);

        if (null == $deposito) {
            throw new \Exception('Depósito inexistente');
        }

        $sessao->idDepositoLogado = $idDeposito;
        $sessao->codExterno = $deposito->getFilial()->getCodExterno();
        $this->_helper->redirector->setGotoUrl($_SERVER['HTTP_REFERER']);
    }

    /**
     * Ativa desativa um deposito
     * 
     * @param int $id
     * @param boolean $boolean
     * @return type
     * @throws \Exception 
     */
    public function ativaDesativa($id, $boolean) {
       try {
            if ($id == null)
                throw new \Exception('Id deve ser enviado para executar a ação');

            $deposito = $this->em->find('wms:Deposito', (int) $id);

            $deposito->setIsAtivo($boolean);
            $this->em->persist($deposito);
            $this->em->flush();

            $this->_helper->messenger('success', 'Registro alterado com sucesso');
            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            return $this->redirect('index');
        }   
    }

    /**
     *
     * @return type 
     */
    public function ativarAction()
    {
        $id = $this->getRequest()->getParam('id');
        $this->ativaDesativa($id, true);
    }
    
    /**
     *
     * @return type 
     */
    public function desativarAction()
    {
        $id = $this->getRequest()->getParam('id');
        $this->ativaDesativa($id, false);
    }

}
