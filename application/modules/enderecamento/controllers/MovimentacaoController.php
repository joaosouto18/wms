<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Enderecamento_MovimentacaoController extends Action
{

    public function indexAction()
    {
		$this->configurePage();
        $form = new \Wms\Module\Armazenagem\Form\Movimentacao\Cadastro();	
		
        $request = $this->getRequest();
        $data = $this->_getAllParams();
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

        if (isset($data['return'])) {
            $idEndereco = $data['idEndereco'];
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('id'=>$idEndereco));
            $data['rua'] = $enderecoEn->getRua();
            $data['predio'] = $enderecoEn->getPredio();
            $data['nivel'] = $enderecoEn->getNivel();
            $data['apto'] = $enderecoEn->getApartamento();
            $form->populate($data);
        } else {
            if ($request->isPost()) {
                try {
                    $result = $enderecoRepo->getEndereco($data['rua'], $data['predio'], $data['nivel'], $data['apto']);
                    if ($result == null) {
                        throw new Exception("Endereço não encontrado.");
                    }

                   // if ($data['grade'])

                    $auth = \Zend_Auth::getInstance();
                    $usuarioSessao = $auth->getIdentity();

                    /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
                    $EstoqueRepository   = $this->_em->getRepository('wms:Enderecamento\Estoque');

                    if ($data['idNormaPaletizacao'] != NULL) {
                        $idUnitizador = $data['idNormaPaletizacao'];
                        $unitizadorRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador");
                        $unitizadorEn = $unitizadorRepo->findOneBy(array('id'=>$idUnitizador));
                        $larguraUnitizador = $unitizadorEn->getLargura(false) * 100;
                        $permiteArmazenar = $enderecoRepo->getValidaTamanhoEndereco($result[0]['id'],$larguraUnitizador);
                        if ($permiteArmazenar == false) {
                            throw new Exception("Este palete não cabe no endereço informado.");
                        }
                    }

                    if ($data['grade'] == '')
                        $data['grade'] = "UNICA";

                    $EstoqueRepository->movimentaEstoque(
                        $data['idProduto'],
                        $data['grade'],
                        $result[0]['id'],
                        $data['quantidade'],
                        $usuarioSessao->getId(),
                        '',
                        'S',
                        null,
                        $data['idNormaPaletizacao']
                    );

                    $link = '/enderecamento/movimentacao/imprimir/endereco/'.$result[0]['descricao'] .'/qtd/'.$data['quantidade'].'/idProduto/'.$data['idProduto'].'/grade/'.urlencode($data['grade']);
                    if($request->isXmlHttpRequest()) {
                        if ($data['quantidade'] > 0) {
                           echo $this->_helper->json(array('status' => 'success', 'msg' => 'Movimentação realizada com sucesso', 'link' => $link));
                        } else {
                            echo $this->_helper->json(array('status' => 'success', 'msg' => 'Movimentação realizada com sucesso'));
                        }
                    } else {
                        $this->addFlashMessage('success','Movimentação realizada com sucesso');
                        $form->populate($data);
                    }

                } catch(Exception $e) {
                    if($request->isXmlHttpRequest()) {
                        echo $this->_helper->json(array('status' => 'error', 'msg' =>  $e->getMessage()));
                    } else {
                        $this->addFlashMessage('error', $e->getMessage());
                        $form->populate($data);
                    }
                }
            }
        }
        $this->view->form = $form;
    }

    public function configurePage()
    {
        $buttons[] = array(
            'label' => 'Exportar Saldo csv',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'movimentacao',
                'action' => 'saldo',
            ),
            'tag' => 'a'
        );
        $buttons[] = array(
            'label' => 'Endereços Disponíveis',
            'cssClass' => 'dialogAjax selecionar-endereco',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'endereco',
                'action' => 'filtrar',
                'origin' => 'movimentacao'
            ),
            'tag' => 'a'
        );
        Page::configure(array('buttons' => $buttons));
    }

    /**
     * Realiza o filtro de um produto trazendo as grades do mesmo
     */
    public function filtrarAction()
    {
        $codProduto = $this->_getParam('idproduto');
        /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
        $ProdutoRepository   = $this->em->getRepository('wms:Produto');
        $grades = $ProdutoRepository->buscaGradesProduto($codProduto);
        if ($grades != null) {
            echo $this->_helper->json($grades);
        }
        echo $this->_helper->json(false);
    }

    /**
     * Traz o resumo de estoque pelo produto ou rua
     */
    public function listAction()
    {
        $params     = $this->_getAllParams();
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
        $EstoqueRepository   = $this->em->getRepository('wms:Enderecamento\Estoque');
        $endsPulmao = $EstoqueRepository->getEstoquePulmao($params);

         /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
        $ProdutoRepository   = $this->_em->getRepository('wms:Produto');
        $produtoEn = $ProdutoRepository->findOneBy(array('id' => $params['idProduto'], 'grade' => $params['grade']));

        $this->view->enderecoPicking = $ProdutoRepository->getEnderecoPicking($produtoEn);

        $this->view->endsPulmao = $endsPulmao;
    }

    public function saldoAction()
    {

		$params = $this->_getAllParams();
		
		if ((isset($params['tipo'])) && ($params['tipo'] == 'C')) {
			/** @var \Wms\Domain\Entity\Enderecamento\VSaldoRepository $SaldoRepository */
			$SaldoCompletoRepository   = $this->_em->getRepository('wms:Enderecamento\VSaldoCompleto');
			$saldo = $SaldoCompletoRepository->saldo($this->_getAllParams());
		} else {
			/** @var \Wms\Domain\Entity\Enderecamento\VSaldoRepository $SaldoRepository */
			$SaldoRepository   = $this->_em->getRepository('wms:Enderecamento\VSaldo');
			$saldo = $SaldoRepository->saldo($this->_getAllParams());
		}
		
        $file = '';

        foreach($saldo as $produto) {
            $linha = $produto['codProduto'].';'.$produto['grade'].';'.$produto['dscLinhaSeparacao'].';'.$produto['qtd'].';'.$produto['dscEndereco'].';'.$produto['unitizador'].';'.$produto['descricao'];
            $file .= $linha . PHP_EOL;
            unset($linha);
        }

        header('Content-Type: application/csv');
        header('Content-disposition: attachment; filename=saldo-estoque.csv');

        echo $file;
        exit;
    }

    public function imprimirAction() {
        $idProduto = $this->_getParam("idProduto");
        $grade = $this->_getParam("grade");
        $dscEndereco = $this->_getParam("endereco");
        $quantidade = $this->_getParam("qtd");

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
        $EstoqueRepository   = $this->_em->getRepository('wms:Enderecamento\Estoque');
        $EstoqueRepository->imprimeMovimentacaoAvulsa($idProduto ,$grade,$quantidade,$dscEndereco);
    }

} 