<?php
use Wms\Module\Web\Controller\Action;

class Produtividade_CarregamentoController  extends Action
{

    public function indexAction()
    {
        $operadores = $this->_getParam('mass-id');
        $expedicao  = $this->_getParam('idExpedicao',$this->_getParam('id'));

        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo = $this->_em->getRepository('wms:Usuario');
        $this->view->operadores = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_CARREGAMENTO"));

        /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
        $this->view->equipe = $equipe = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');

        $btnPressionado = $this->_getParam('submit');
        $dthFinal = false;
        if ($btnPressionado == 'Finalizar') {
            $dthFinal = true;
        }

        if ($operadores && $expedicao) {

            /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
            $carregamentoRepo = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');
            try {
                $carregamentoRepo->vinculaOperadores($expedicao, $operadores, null, $dthFinal);
                $this->_helper->messenger('success', 'Equipe de Carregamento vinculada a expedição com sucesso');
                $this->redirect('index','index','expedicao');
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        $this->view->idExpedicao = $expedicao;
    }

}