<?php
use Wms\Module\Web\Controller\Action;

class Produtividade_CarregamentoController  extends Action
{

    public function indexAction()
    {
        $operadores = $this->_getParam('mass-id');
        $expedicao  = $this->_getParam('expedicao',$this->_getParam('id'));

        if ($operadores && $expedicao) {

            /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
            $carregamentoRepo = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');
            try {
                $carregamentoRepo->vinculaOperadores($expedicao, $operadores);
                $this->_helper->messenger('success', 'Equipe de Carregamento vinculada a expedição com sucesso');
                $this->redirect('index','index','expedicao');
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        $this->view->expedicao = $expedicao;
        $this->gridCarregamento();
    }

    private function gridCarregamento()
    {
        $source = $this->em->createQueryBuilder()
            ->select('distinct pf.id, u.login, u.isAtivo, pf.nome, u.isSenhaProvisoria')
            ->from('wms:Usuario', 'u')
            ->innerJoin('u.pessoa', 'pf')
            ->innerJoin('u.depositos', 'd')
            ->innerJoin('u.perfis', 'p')
            ->orderBy('pf.nome')
            ->andWhere("p.nome = 'EQP.CARREGAMENTO'");

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
            'label' => 'Ativo',
            'index' => 'isAtivo',
            'render' => 'SimOrNao',
        ));
        $grid->setShowExport(false);
        $grid->addMassAction('index', 'Vincular a Expedicao');

        $this->view->grid = $grid->build();
    }

}