<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Enderecamento_Relatorio_PickingController extends Action
{
    public function indexAction() {
        $this->configurePage();

        /** @var \Wms\Domain\Entity\Enderecamento\RelatorioPickingRepository $relatorioRepo */
        $relatorioRepo = $this->em->getRepository("wms:Enderecamento\RelatorioPicking");

        $removerId = $this->_getParam('remover');
        if ($removerId) {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $removerId));
            $relatorioEn = $relatorioRepo->findOneBy(array('depositoEndereco' => $enderecoEn));
            $this->getEntityManager()->remove($relatorioEn);
            $this->getEntityManager()->flush();
        }

        $enderecosSelecionados = $relatorioRepo->getSelecionados();
        $this->view->pickings = $enderecosSelecionados;

    }

    public function configurePage()
    {
	
        $buttons[] = array(
            'label' => 'Selecionar Pickings',
            'cssClass' => 'button imprimir dialogAjax',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'relatorio_picking',
                'action' => 'list',
            ),
            'tag' => 'a'
        );
	
        $buttons[] = array(
            'label' => 'Limpar Selecionados',
            'cssClass' => 'button imprimir',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'relatorio_picking',
                'action' => 'limpar',
            ),
            'tag' => 'a'
        );

        $buttons[] = array(
            'label' => 'Imprimir Relatório',
            'cssClass' => 'button imprimir',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'relatorio_picking',
                'action' => 'imprimir',
            ),
            'tag' => 'a'
        );
		Page::configure(array('buttons' => $buttons));
    }

    public function imprimirAction()
    {
        ini_set('max_execution_time', 3000);
        /** @var \Wms\Domain\Entity\Enderecamento\RelatorioPickingRepository $relatorioRepo */
        $relatorioRepo = $this->em->getRepository("wms:Enderecamento\RelatorioPicking");
        $result = $relatorioRepo->getDescricaoSelecionadosOrdenado();

        if (count($result) <= 0) {
            $this->addFlashMessage("error","Nenhum endereço selecionado para ser abastecido");
            $this->_redirect("/enderecamento/relatorio_picking");
        }

        $relatorio = new \Wms\Module\Enderecamento\Report\AbastecimentoPicking();
        $relatorio->imprimir($result);
    }

    public function limparAction() {
        /** @var \Wms\Domain\Entity\Enderecamento\RelatorioPickingRepository $relatorioRepo */
        $relatorioRepo = $this->em->getRepository("wms:Enderecamento\RelatorioPicking");
        $relatorioRepo->clearSelecionados();
        $this->_redirect("/enderecamento/relatorio_picking");
    }

	public function listAction()
	{
	//INSERT INTO RECURSO_ACAO (COD_RECURSO_ACAO, COD_RECURSO, COD_ACAO, DSC_RECURSO_ACAO) VALUES (SQ_RECURSO_ACAO_01.NEXTVAL, (SELECT COD_RECURSO FROM RECURSO WHERE NOM_RECURSO = 'enderecamento:relatorio_picking'), (SELECT COD_ACAO FROM ACAO WHERE NOM_ACAO = 'list'), 'Listar');
	
		$params = $this->_getAllParams();
		if (isset($params['busca'])){
			
			$enderecos = $this->_getParam('endereco');
			foreach ($enderecos as $endereco){
				$enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
				$enderecoEn = $enderecoRepo->findOneBy(array('id'=>$endereco));
				
				$contagem = new \Wms\Domain\Entity\Enderecamento\RelatorioPicking();
				$contagem->setDepositoEndereco($enderecoEn);
				$this->em->persist($contagem);
			}
			$this->em->flush();
			$this->_redirect("/enderecamento/relatorio_picking");
		}
				
		$paramsSaldo = array ('pulmao' => false, 'picking' => true);
        $saldoRepo = $this->em->getRepository("wms:Enderecamento\VSaldo");
		$enderecos = $saldoRepo->saldo($paramsSaldo);
		
		$this->view->enderecos = $enderecos;

		
	}
	
}