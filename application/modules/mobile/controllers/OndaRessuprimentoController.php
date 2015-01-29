<?php
use Wms\Controller\Action,
    Wms\Service\Recebimento as LeituraColetor;


class Mobile_OndaRessuprimentoController extends Action
{

    public function listarOndasAction()
    {
        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
        $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $ondas = $ondaRepo->getOndasEmAberto();

        $menu = array();
        foreach ($ondas as $onda) {
            $botao = array(
                'url' => '/mobile/onda-ressuprimento/selecionar-endereco/idOnda/'.$onda['OndaOsId'],
                'label' => 'OS:' . $onda['OS']. ' Onda:'.$onda['Onda'],
            );
            $menu[] = $botao;
        }
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function selecionarEnderecoAction(){
        $idOnda = $this->_getParam('idOnda');
        $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
        $ondaOsEn  = $ondaOsRepo->findOneBy(array('id'=>$idOnda));
        $codProduto = $ondaOsEn->getProduto()->getId();
        $grade = $ondaOsEn->getProduto()->getGrade();
        $dscProduto = $ondaOsEn->getProduto()->getDescricao();
        $endPulmao = $ondaOsEn->getEndereco()->getDescricao();
        $idEnderecoPulmao = $ondaOsEn->getEndereco()->getId();
        $qtd = $ondaOsEn->getQtd();

        $this->view->idOnda = $idOnda;
        $this->view->codProduto = $codProduto;
        $this->view->grade = $grade;
        $this->view->endPulmao = $endPulmao;
        $this->view->dscProduto = $dscProduto;
        $this->view->qtd = $qtd;
        $this->view->id = $qtd;
        $this->view->idEnderecoPulmao = $idEnderecoPulmao;
    }

    public function validarEnderecoAction()
    {
        $idOnda = $this->_getParam('idOnda');
        $idEnderecoPulmao = $this->_getParam('idEnderecoPulmao');

        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');

        if ($codigoBarras) {
          $LeituraColetor = new LeituraColetor();
          $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);
        }

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
        $result = $estoqueRepo->getProdutoByNivel($codigoBarras, $nivel, false);

        if ($result == NULL)
        {
            $this->addFlashMessage("error","Endereço selecionado está vazio");
            $this->_redirect('/mobile/onda-ressuprimento/selecionar-endereco/idOnda/'.$idOnda);
        }
        if ($result[0]['idEndereco'] != $idEnderecoPulmao) {
            $this->addFlashMessage("error","Endereço selecionado errado");
            $this->_redirect('/mobile/onda-ressuprimento/selecionar-endereco/idOnda/'.$idOnda);
        }

        if ($result[0]['uma']) {
            $this->_redirect('/mobile/onda-ressuprimento/selecionar-uma/idOnda/' . $idOnda);
        } else {
            $this->_redirect('/mobile/onda-ressuprimento/selecionar-produto/idOnda/' . $idOnda );
        }
    }

    public function selecionarUmaAction()
    {
        $idOnda = $this->_getParam('idOnda');
        $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
        $ondaOsEn  = $ondaOsRepo->findOneBy(array('id'=>$idOnda));
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo  = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        $codProduto = $ondaOsEn->getProduto()->getId();
        $grade = $ondaOsEn->getProduto()->getGrade();
        $dscProduto = $ondaOsEn->getProduto()->getDescricao();
        $endPulmao = $ondaOsEn->getEndereco()->getDescricao();
        $idEnderecoPulmao = $ondaOsEn->getEndereco()->getId();
        $qtd = $ondaOsEn->getQtd();

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoque $reservaEstoquePicking */
        $reservaEstoquePicking = $reservaEstoqueRepo->findReservaEstoque(null,$codProduto,$grade,$qtd,"E","O",$idOnda,$ondaOsEn->getOs()->getId());

        $this->view->idOnda = $idOnda;
        $this->view->codProduto = $codProduto;
        $this->view->grade = $grade;
        $this->view->endPulmao = $endPulmao;
        $this->view->endPicking = $reservaEstoquePicking->getEndereco()->getDescricao();
        $this->view->dscProduto = $dscProduto;
        $this->view->qtd = $qtd;
        $this->view->id = $qtd;
        $this->view->idEnderecoPulmao = $idEnderecoPulmao;
    }

    public function selecionarProdutoAction()
    {
        $idOnda = $this->_getParam('idOnda');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo  = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");
        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
        $ondaOsEn  = $ondaOsRepo->findOneBy(array('id'=>$idOnda));

        $codProduto = $ondaOsEn->getProduto()->getId();
        $grade = $ondaOsEn->getProduto()->getGrade();
        $dscProduto = $ondaOsEn->getProduto()->getDescricao();
        $endPulmao = $ondaOsEn->getEndereco()->getDescricao();
        $idEnderecoPulmao = $ondaOsEn->getEndereco()->getId();
        $qtd = $ondaOsEn->getQtd();

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoque $reservaEstoquePicking */
        $reservaEstoquePicking = $reservaEstoqueRepo->findReservaEstoque(null,$codProduto,$grade,$qtd,"E","O",$idOnda,$ondaOsEn->getOs()->getId());

        $this->view->idOnda = $idOnda;
        $this->view->codProduto = $codProduto;
        $this->view->grade = $grade;
        $this->view->endPulmao = $endPulmao;
        $this->view->endPicking = $reservaEstoquePicking->getEndereco()->getDescricao();
        $this->view->dscProduto = $dscProduto;
        $this->view->qtd = $qtd;
        $this->view->id = $qtd;
        $this->view->idEnderecoPulmao = $idEnderecoPulmao;
    }

    public function finalizarAction()
    {
        $codigoBarrasUMA = $this->_getParam('codigoBarrasUma');
        $etiquetaProduto = $this->_getParam('etiquetaProduto');
        $idOnda = $this->_getParam('idOnda');

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
        $ondaOsRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs");
        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
        $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
        $ondaOsEn  = $ondaOsRepo->findOneBy(array('id'=>$idOnda));
        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $ondaOsEn->getEndereco();

        if ($codigoBarrasUMA)
        {
            $LeituraColetor = new LeituraColetor();
            $codigoBarrasUMA = $LeituraColetor->retiraDigitoIdentificador($codigoBarrasUMA);

            $result = $estoqueRepo->getProdutoByUMA($codigoBarrasUMA, $estoqueEn->getId());
            if ($result == NULL) {
                $this->addFlashMessage("error","UMA $codigoBarrasUMA Não encontrada neste endereço");
                $this->_redirect('/mobile/onda-ressuprimento/selecionar-uma/onda/'. $idOnda );
            }
        }

        if ($etiquetaProduto)
        {
            $LeituraColetor = new LeituraColetor();
            $etiquetaProduto = $LeituraColetor->analisarCodigoBarras($etiquetaProduto);

            $result = $estoqueRepo->getProdutoByCodBarrasAndEstoque($etiquetaProduto, $estoqueEn->getId());
            if ($result == NULL) {
                $this->addFlashMessage("error","Produto $etiquetaProduto não encontrado neste endereço");
                $this->_redirect('/mobile/onda-ressuprimento/selecionar-produto/onda/' . $idOnda );
            }
        }

        $codProduto = $result[0]['id'];
        $grade = $result[0]['grade'];

        if (($codProduto != $ondaOsEn->getProduto()->getId()) || ($grade != $ondaOsEn->getProduto()->getGrade())){
            $this->addFlashMessage("error","Produto diferente do indicado na onda");
            if ($codigoBarrasUMA) {
                $this->_redirect('/mobile/onda-ressuprimento/selecionar-uma/onda/'. $idOnda );
            }else {
                $this->_redirect('/mobile/onda-ressuprimento/selecionar-produto/onda/' . $idOnda );
            }
        }

        try {
            $ondaRepo->finalizaOnda($ondaOsEn);
            $this->addFlashMessage("success","Os Finalizada comsucesso");
        } catch(\Exception $e) {
            $this->addFlashMessage("error","Falha finalizando os $idOnda - " .$e->getMessage() );
        }
        $this->_redirect('/mobile/onda-ressuprimento/listar-ondas' );
    }
  }