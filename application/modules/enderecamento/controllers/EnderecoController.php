<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_EnderecoController extends Action
{
    public function filtrarAction()
    {

        $grade     = $this->_getParam('grade');
		$idProduto = $this->_getParam('codigo');
        $idRecebimento  = $this->getRequest()->getParam('id');

        if(($grade != null) && ($idProduto != null) && ($idRecebimento != null))
        {
          $paleteRepo    = $this->em->getRepository("wms:Enderecamento\Palete");
          $norma = $paleteRepo->getImprimeNorma($idRecebimento, $idProduto, $grade);
        }

		$produtoRepo = $this->em->getRepository("wms:Produto");
		$produtoEn = $produtoRepo->findOneBy(array('id'=>$idProduto, 'grade'=>$grade));

		$picking = $produtoRepo->getEnderecoPicking($produtoEn);

        $form = new Wms\Module\Armazenagem\Form\Endereco\Filtro();
        $form->setAttrib('class', 'filtro-enderecamento-palete')
              ->setAttrib('method', 'post');
		$form->setPicking($picking);
        if (isset($norma)){
          $form->setUnitizador($norma);
        }

        $origin = $this->_getParam('origin');
        if ($origin != NULL) {
            $this->_setParam('origin',$origin);
            $form->setOrigin($origin);
            $this->view->origin = $origin;
        }

    if ($values = $form->getParams()) {

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $enderecos = $enderecoRepo->getEnderecoesDisponivesByParam($values['identificacao']);
            $this->view->enderecos = $enderecos;
            $this->view->parametros = true;
            $this->view->origin = $values['identificacao']['origin'];
        }

        $this->view->form = $form;
    }

    public function enderecarAction()
    {
        $idPaletes = $this->_getParam("umas");
        $enderecos = $this->_getParam("enderecos");
        $arrayPaletes = explode(',',$idPaletes);
        unset($arrayPaletes[0]);

        $paletes = array();
        foreach ($arrayPaletes as $palete) {
            if (is_numeric($palete)) {
                $paletes[] = $palete;
            }
        }

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo   = $this->em->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");

        $contador = 0;

        foreach($enderecos as $key => $endereco) {
            if ($paletes[$contador]) {
                $idPalete   = $paletes[$contador];
                $idEndereco = $key;

                /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
                $paleteEn = $paleteRepo->find($idPalete);
                $larguraPalete = $paleteEn->getUnitizador()->getLargura(false)* 100;
                $idRecebimento = $paleteEn->getRecebimento()->getId();

                $produtosEn = $paleteEn->getProdutos();
                $codProduto = $produtosEn[0]->getCodProduto();
                $grade      = $produtosEn[0]->getGrade();

                if($enderecoRepo->verificaBloqueioInventario($idEndereco)) {
                    $this->addFlashMessage('error',"Endereço(s) bloqueado(s) por inventário");
                    $this->_redirect("/enderecamento/palete/index/id/$idRecebimento/codigo/$codProduto/grade/" . urlencode($grade));
                    return false;
                }

                $tipoEstruturaArmazenamento = $enderecoRepo->getTipoArmazenamentoByEndereco($idEndereco);

                if ($tipoEstruturaArmazenamento[0]['COD_TIPO_EST_ARMAZ'] == Wms\Domain\Entity\Armazenagem\Estrutura\Tipo::BLOCADO) {
                    foreach ($paletes as $palete) {
                        $paleteRepo->alocaEnderecoPaleteByBlocado($palete, $idEndereco);
                    }

                } elseif ($idPalete != 'on') {
                    $permiteEnderecar = $enderecoRepo->getValidaTamanhoEndereco($idEndereco,$larguraPalete);

                    if ($permiteEnderecar == false) {
                        $this->getEntityManager()->flush();
                        $this->addFlashMessage('error',"Não foram realizados todos endereçamentos. O palete $idPalete não cabe no endereço selecionado");
                        $this->_redirect("/enderecamento/palete/index/id/$idRecebimento/codigo/$codProduto/grade/" . urlencode($grade));
                    }
                    $paleteRepo->alocaEnderecoPalete($idPalete,$idEndereco);
                }
            }
            $contador++;
        }

        $this->em->flush();

        $this->_redirect("/enderecamento/palete/index/id/$idRecebimento/codigo/$codProduto/grade/" . urlencode($grade));
    }

}