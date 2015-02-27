<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Enderecamento\Produtos as ProdutosGrid,
    Wms\Module\Web\Page;

class Enderecamento_ProdutoController extends Action
{
    /**
     * Exibe produtos de um recebimento para endereçamento
     */
    public function indexAction()
    {
        $idRecebimento   = $this->getRequest()->getParam('id');
        $codRecebimento  = $this->getRequest()->getParam('COD_RECEBIMENTO');

        if (isset($codRecebimento)) {
            $idRecebimento = $codRecebimento;
            $this->_redirect('enderecamento/produto/index/id/'.$idRecebimento);
        }

        $buttons[] =  array(
            'label' => 'Voltar para Busca de Recebimentos',
            'cssClass' => 'btnBack',
            'urlParams' => array(
                'module' => 'recebimento',
                'controller' => 'index',
                'action' => 'index'
            ),
            'tag' => 'a'
        );

        Page::configure(array('buttons' => $buttons));

        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo    = $this->em->getRepository('wms:Recebimento');
        $recebimento = $recebimentoRepo->find($idRecebimento);

        $this->view->recebimento = $recebimento;

        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $recebimento->getId()));

        if ($notaFiscalEntity)
            $this->view->placaVeiculo = $notaFiscalEntity->getPlaca();

        $recebimentoStatus = $this->em->getRepository('wms:Recebimento')->buscarStatusSteps($recebimento);
        $this->view->recebimentoStatus = $this->view->steps($recebimentoStatus, $recebimento->getStatus()->getReferencia());

        $Grid = new ProdutosGrid();
        $this->view->grid = $Grid->init($idRecebimento, $recebimento->getStatus())
            ->render();
    }

    public function alterarNormaAction(){

        $idRecebimento = $this->_getParam("id");
        $codProduto    = $this->_getParam("codigo");
        $grade         = $this->_getParam("grade");

        $result = $this->getEntityManager()->getRepository("wms:Produto")->getNormaPaletizacaoPadrao($codProduto, $grade);

        $msg = "Confirma a troca da norma de paletização usada no recebimento deste produto para a norma da unidade abaixo?";
        $botaoConfirma = true;
        $tabela = true;

        if ($result['unidade'] == "") {
            $msg = "Este produto não possui embalagem padrão de recebimento cadastrada";
            $botaoConfirma = false;
            $tabela = false;
        }
        if ($result['qtdNorma'] == 0) {
            $msg = "A embalagem padrão de recebimento está com a norma cadastrada como 0";
            $botaoConfirma = false;
            $tabela = true;
        }
        if ($result['idNorma'] == NULL) {
            $msg = "A emabalagem padrão de recebimento (" . $result['unidade'] . ") não possui norma de paletização cadastrada";
            $botaoConfirma = false;
            $tabela = false;
        }

        $this->view->msg = $msg;
        $this->view->botaoConfirma = $botaoConfirma;
        $this->view->tabela = $tabela;

        $this->view->unidade = $result['unidade'];
        $this->view->unitizador = $result['unitizador'];
        $this->view->qtdNorma = $result['qtdNorma'];
        $this->view->dscProduto = $result['dscProduto'];
        $this->view->lastro = $result['lastro'];
        $this->view->camadas = $result['camadas'];

        $this->view->idRecebimento = $idRecebimento;
        $this->view->codProduto = $codProduto;
        $this->view->grade = $grade;
    }

    public function listAction() {
        $idRecebimento = $this->_getParam("id");
        $codProduto    = $this->_getParam("codigo");
        $grade         = $this->_getParam("grade");

        $grid = new \Wms\Module\Web\Grid\Enderecamento\Andamento();
        $this->view->grid = $grid->init($idRecebimento,$codProduto,$grade)->render();;
    }

    public function confirmarAlteracaoAction() {
        $idRecebimento = $this->_getParam("id");
        $codProduto    = $this->_getParam("codigo");
        $grade         = $this->_getParam("grade");

        $recebimentoRepo = $this->getEntityManager()->getRepository("wms:Recebimento");
		$conferenciaRepo = $this->getEntityManager()->getRepository("wms:Recebimento\Conferencia");
		
        $result = $this->getEntityManager()->getRepository("wms:Produto")->getNormaPaletizacaoPadrao($codProduto, $grade);
        $idNorma = $result['idNorma'];

        if ($idNorma == NULL) {
            $this->addFlashMessage('error',"O Produto $codProduto, grade $grade não possuí norma de paletização");
            $this->_redirect('enderecamento/produto/index/id/'.$idRecebimento);
        }

        /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimento $recebimentoEn */
        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento\VQtdRecebimento")->findOneBy(array('codRecebimento' => $idRecebimento, 'codProduto'=>$codProduto, 'grade'=>$grade));
		$conferenciaEn = $conferenciaRepo->findOneBy(array('recebimento'=> $idRecebimento,'codProduto'=>$codProduto,'grade'=>$grade));
				
        if (($recebimentoEn == NULL) && ($conferenciaEn == NULL)){
            $this->addFlashMessage('error',"Nenhuma quantidade conferida para o produto $codProduto, grade $grade");
            $this->_redirect('enderecamento/produto/index/id/'.$idRecebimento);
        }
		
        try {
            /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimento $recebimentoEn */

			if ($recebimentoEn == null) {
				$idOs = $conferenciaRepo->getLastOsConferencia($idRecebimento,$codProduto,$grade);
				$idNormaAntiga = 'Nenhuma Norma';
				$qtdNormaAntiga = 0; 
			} else {
				$normaAntigaEn = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id'=>$recebimentoEn->getCodNormaPaletizacao()));
				if ($normaAntigaEn == null) {
					$idNormaAntiga = "";
					$qtdNormaAntiga = "SEM NORMA ANTIGA";
				} else {
					$idNormaAntiga = $normaAntigaEn->getId();
					$qtdNormaAntiga = $normaAntigaEn->getNumNorma();
				}

				$idOs = $recebimentoEn->getCodOs();
			}
            			
            $recebimentoRepo->alteraNormaPaletizacaoRecebimento($idRecebimento,$codProduto,$grade,$idOs, $idNorma);

            /** @var \Wms\Domain\Entity\Enderecamento\AndamentoRepository $andamentoRepo */
            $andamentoRepo  = $this->_em->getRepository('wms:Enderecamento\Andamento');
            $msg = "Norma de paletização trocada com sucesso para a da unidade " . $result['unidade'] ." (" . $result['unitizador'] . ")  | Norma: ". $idNormaAntiga . "(" .  $qtdNormaAntiga . ") -> " . $result['idNorma'] . "(" . $result['qtdNorma'] . ") ";
            $andamentoRepo->save($msg, $idRecebimento, $codProduto, $grade);

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo  = $this->_em->getRepository('wms:Enderecamento\Palete');
            $paleteRepo->deletaPaletesRecebidos($idRecebimento,$codProduto, $grade);
            $this->addFlashMessage('success',"Norma de paletização para o produto $codProduto, grade $grade alterada com sucesso neste recebimento");
			$this->_redirect('enderecamento/palete/index/id/'.$idRecebimento . '/codigo/'. $codProduto . '/grade/'. $grade);
        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
			$this->_redirect('enderecamento/produto/index/id/'.$idRecebimento);
        }
        
    }

} 