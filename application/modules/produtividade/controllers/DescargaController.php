<?php
use Wms\Module\Web\Controller\Action;

class Produtividade_DescargaController  extends Action
{

    public function indexAction()
    {
        $operadores = $this->_getParam('mass-id');
        $recebimento       = $this->_getParam('recebimento',$this->_getParam('id'));
        $osId               = $this->_getParam('idOrdemServico');

        if ($operadores && $recebimento) {

            /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $descargaRepo */
            $descargaRepo = $this->em->getRepository('wms:Recebimento\Descarga');
            try {
                $descargaRepo->vinculaOperadores($recebimento, $operadores);
                $this->_helper->messenger('success', 'Operadores vinculados ao recebimento com sucesso');
                if (!empty($osId)) {
                    $this->redirect('conferencia-coletor-ajax','recebimento','web',array('idOrdemServico' => $osId));
                } else {
                    $this->redirect('index','recebimento','web');
                }
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        $this->view->recebimento = $recebimento;
        $this->gridDescarga();
    }

    private function gridDescarga()
    {
        $source = $this->em->createQueryBuilder()
            ->select('distinct pf.id, u.login, u.isAtivo, pf.nome, u.isSenhaProvisoria')
            ->from('wms:Usuario', 'u')
            ->innerJoin('u.pessoa', 'pf')
            ->innerJoin('u.depositos', 'd')
            ->innerJoin('u.perfis', 'p')
            ->orderBy('pf.nome')
            ->andWhere("p.nome = 'DESCARREGADOR RECEBI'");

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
        $grid->addMassAction('index', 'Vincular ao Recebimento');

        $this->view->grid = $grid->build();
    }

}