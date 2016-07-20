<?php

use Wms\Module\Web\Controller\Action\Crud;
use Wms\Module\Web\Page;
use Wms\Module\Web\Form\Subform\Pessoa\Papel\FiltroUsuario as FiltroUsuarioForm;

/**
 * Description of Web_UsuarioController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_UsuarioController extends Crud
{

    protected $entityName = 'Usuario';
    protected $pkField = 'pessoa';

    public function indexAction()
    {
        $form = new FiltroUsuarioForm;

        if ($values = $form->getParams()) {
            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('distinct pf.id, u.login, u.isAtivo, pf.nome, u.isSenhaProvisoria')
                    ->from('wms:Usuario', 'u')
                    ->innerJoin('u.pessoa', 'pf')
                    ->innerJoin('u.depositos', 'd')
                    ->innerJoin('u.perfis', 'p')
                    ->orderBy('pf.nome');
            // caso tenha deposito e perfis verifico condicoes
            if (!empty($isAtivo)) {
                $source->andWhere("u.isAtivo = :isAtivo")
                        ->setParameter('isAtivo', $isAtivo);
            }
            if (!empty($idPerfil))
                $source->andWhere("p.id = " . $idPerfil);
            if (!empty($idDeposito))
                $source->andWhere("d.id = " . $idDeposito);

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addColumn(array(
                        'label' => 'Nome Usuario',
                        'index' => 'nome',
                    ))
                    ->addColumn(array(
                        'label' => 'Login',
                        'index' => 'login',
                    ))
                    ->addColumn(array(
                        'label' => 'Senha provisória',
                        'index' => 'isSenhaProvisoria',
                        'render' => 'SimOrNao',
                    ))
                    ->addColumn(array(
                        'label' => 'Ativo',
                        'index' => 'isAtivo',
                        'render' => 'SimOrNao',
                    ))
                    ->addAction(array(
                        'label' => 'Editar',
                        'actionName' => 'edit',
                        'pkIndex' => 'id'
                    ))
                    ->addAction(array(
                        'label' => 'Desativar',
                        'actionName' => 'desativar',
                        'pkIndex' => 'id',
                        'cssClass' => 'del',
                        'condition' => function ($row) {
                            return $row['isAtivo'] == 'S';
                        }
                    ))
                    ->addAction(array(
                        'label' => 'Resetar senha',
                        'actionName' => 'resetar-senha',
                        'pkIndex' => 'id',
                        'cssClass' => 'edit confirm',
                        'title' => 'Deseja mesmo resetar a senha deste usuário?'
                    ))
                    ->addAction(array(
                        'label' => 'Imprimir Código de Barras',
                        'actionName' => 'imprimir',
                        'pkIndex' => 'id',
                        'title' => 'Imprime o codigo de barras deste usuário'
                    ));

            $grid->setHasOrdering(true);
            $grid->addMassAction('mass-delete', 'Remover');
            
            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

    /**
     * Adiciona um usuário
     * @return void 
     */
    public function addAction()
    {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
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
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $formClass = '\\Wms\Module\Web\Form\\' . $this->entityName;
        $form = new $formClass;

        try {
            $params = $this->getRequest()->getParams();

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $entity = new Wms\Domain\Entity\Usuario;
                $this->repository->save($entity, $params);
                $this->em->flush();
                $this->_helper->messenger('success', 'Usuário inserido com sucesso. A senha provisória é o login em minúsculo.');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        
        $this->view->form = $form;
    }

    /**
     * 
     */
    public function resetarSenhaAction()
    {
        $id = $this->getRequest()->getParam('id');

        try {
            if ($id == null)
                throw new \Exception('ID Inválido');

            $usuario = $this->em->find('wms:Usuario', $id);

            if ($usuario == null)
                throw new \Exception('Usuário inválido');

            $usuario->resetarSenha();
            $this->em->persist($usuario);
            $this->em->flush();

            $this->addFlashMessage('success', 'Senha resetada com sucesso. A senha provisória é o login em minúsculo.');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->redirect('index');
    }

    /**
     * Força o usuário a mudar sua senha provisória
     */
    public function mudarSenhaProvisoriaAction()
    {

        $this->view->title = 'Mudança de senha provisória';

        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Mudar senha',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        $form = new Wms\Module\Web\Form\Usuario\SenhaProvisoria();

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {

            $params = $this->getRequest()->getParams();
            extract($params);

            $auth = \Zend_Auth::getInstance();
            $usuarioSessao = $auth->getStorage()->read();
            $usuario = $this->em->find('wms:Usuario', $usuarioSessao->getId());
            $usuario->setSenhaReal($senha);

            $this->em->persist($usuario);
            $this->em->flush();

            //faz o logout
            \Wms\Service\Auth::logout();

            $this->_helper->messenger('success', 'Sua senha foi modificada com sucesso. Faça o login novamente para iniciar uma nova sessão.');
            $this->_helper->redirector('login', 'auth');
        }

        $this->view->form = $form;
    }

    /**
     * Alteração de senha do usuário
     */
    public function alterarSenhaAction()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Mudar senha',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        $form = new \Wms\Module\Web\Form\Usuario\AlterarSenha();

        try {
            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {

                $params = $this->getRequest()->getParams();
                extract($params);

                $auth = \Zend_Auth::getInstance();
                //usuario que está logado
                $usuarioSessao = $auth->getStorage()->read();
                // buscando usuário logado no banco
                $usuario = $this->em->find('wms:Usuario', $usuarioSessao->getId());
                $senhaBanco = $usuario->getSenha();
                $senhaAtual = $usuario->criptografaSenha($senhaAtual);

                //verifica se a senha atual está correta
                if ($senhaAtual != $senhaBanco)
                    throw new Exception('A senha informada não confere com a senha cadastrada');

                $usuario->setSenha($senha);

                $this->em->persist($usuario);
                $this->em->flush();
                $this->_helper->messenger('success', 'Sua senha foi modificada com sucesso');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }

    /**
     * Desativa usuario
     */
    public function desativarAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            $usuario = $this->em->find('wms:Usuario', $id);

            $repoUsuario = $this->em->getRepository('wms:Usuario');
            $repoUsuario->desativar($usuario);

            $this->em->flush();
            $this->_helper->messenger('success', 'Usuário desativado com sucesso');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        return $this->redirect('index');
    }

    public function imprimirAction () {
        $id = $this->getRequest()->getParam('id');

        $usuario = $this->em->find('wms:Usuario', $id);
        $nomeUsuario = $usuario->getLogin();
        $dscUsuario = $usuario->getPessoa()->getNome();

        $etiquetaUsuario = new \Wms\Module\Web\Report\Usuario("P", 'mm', array(110, 60));
        $result = $etiquetaUsuario->init($nomeUsuario,$dscUsuario);
    }

}
