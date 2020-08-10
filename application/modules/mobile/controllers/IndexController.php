<?php
use Wms\Controller\Action,
    Wms\Util\Coletor as ColetorUtil;

class Mobile_IndexController  extends Action
{
    public function indexAction()
    {
        $menu = array(
            1 => array(
                'url' => '/mobile/ordem-servico/conferencia-recebimento',
                'label' => 'CONF. RECEBIMENTO',
            ),
            2 => array(
                'url' => '/mobile/ordem-servico/seleciona-filial',
                'label' => 'EXPEDIÇÃO',
            ),
            3 => array(
                'url' => '/mobile/armazenagem',
                'label' => 'ARMAZENAGEM',
            ),
            4 => array(
                'url' => '/mobile/ressuprimento',
                'label' => 'RESSUPRIMENTO',
            ),
            /*
            5 => array(
                'url' => '/mobile/ordem-servico/conferencia-inventario',
                'label' => 'INVENTÁRIO'
            ),
            */
            6 => array(
                'url' => '/mobile/consulta-produto',
                'label' => 'CONSULTA PRODUTO',
            ),
            7 => array(
                'url' => '/mobile/reentrega/recebimento',
                'label' => 'REENTREGA',
            ),
            8 => array(
                'url' => '/mobile/recebimento-transbordo/produtividade',
                'label' => 'PRODUTIVIDADE',
            ),
            9 => array(
                'url' => '/mobile/enderecamento/cadastro-produto-endereco',
                'label' => 'CADASTRO PRODUTO ENDERECO'
            ),
            10 => array(
                'url' => '/mobile/consulta-endereco',
                'label' => 'CONSULTA ENDEREÇO'
            ),
            11 => array(
                'url' => '/mobile/index/separacao-pulmao-doca-ajax',
                'label' => 'SEPARAÇÃO PULMÃO DOCA'
            ),
            12 => array(
                'url' => '/mobile/inventario-novo/listagem-inventarios',
                'label' => 'INVENTÁRIO'
            )

        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function sucessoAction()
    {
        $link = '<a href="' . $this->view->url(array('controller' => 'index', 'action' => 'buscar-recebimento')) . '" target="_self" class="btn">Voltar</a>';
        $this->view->link = $link;
    }

    public function separacaoPulmaoDocaAjaxAction(){
        $expRepository = $this->getEntityManager()->getRepository('wms:Expedicao');
        $this->view->expedicoes = $expRepository->getExpedicoesPD();
    }

    public function enderecosSeparacaoPdAjaxAction(){
        $this->view->idExpedicao = $this->_getParam('expedicao');
        $expRepository = $this->getEntityManager()->getRepository('wms:Expedicao');
        $enderecos = $expRepository->getEtiquetasPd($this->_getParam('expedicao'));
        if(empty($enderecos)){
            $this->addFlashMessage('info','Todas as etiquetas de pulmão doca da expedição '. $this->_getParam('expedicao') . ' já foram separadas');
            $this->_redirect('/mobile/index/separacao-pulmao-doca-ajax');
        }
        $this->view->enderecos = $enderecos;
    }

    public function getProdutosPdAjaxAction(){
        $expedicao = $this->_getParam('idExpedicao');
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');
        $this->view->idExpedicao = $expedicao;

        try {
            if ($codigoBarras) {
                $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarras);
            }
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $endereco = \Wms\Util\Endereco::formatar($codigoBarras);
            $vetEnd = explode('.',$endereco);
            if(empty($nivel)){
                $nivel = '00';
            }
            $newEnd = "$vetEnd[0].$vetEnd[1].$nivel.$vetEnd[3]";
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $newEnd));
            if (empty($enderecoEn)) {
                throw new Exception("Endereço não encontrado");
            }
            $this->view->codEndereco = $enderecoEn->getId();
            $etiquetaSeparacaoRepo = $this->em->getRepository("wms:Expedicao\EtiquetaSeparacao");
            $produtos = $etiquetaSeparacaoRepo->getProdutoByEtiqueta($enderecoEn->getId(), $expedicao);
            if(empty($produtos)){
                throw new Exception("Etiqueta não encontrada");
            }
            $this->view->produtos = $produtos;
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
            $this->_redirect('/mobile/index/enderecos-separacao-pd-ajax/expedicao/' . $expedicao);
        }
    }
    public function confirmarSeparcaoPdAjaxAction(){
        $expedicao = $this->_getParam('expedicao');
        $codEndereco = $this->_getParam('endereco');
        $etiquetaSeparacaoRepo = $this->em->getRepository("wms:Expedicao\EtiquetaSeparacao");
        $etiquetas = $etiquetaSeparacaoRepo->getProdutoByEtiqueta($codEndereco, $expedicao);
        foreach ($etiquetas as $etiqueta) {
            $etiquetaEn = $etiquetaSeparacaoRepo->find($etiqueta['COD_ETIQUETA_SEPARACAO']);
            $etiquetaEn->setDthSeparacao(new \DateTime());
            $etiquetaEn->setUsuarioSeparacao(\Zend_Auth::getInstance()->getIdentity()->getId());
        }
        $this->em->flush();
        $this->_redirect('/mobile/index/enderecos-separacao-pd-ajax/expedicao/' . $expedicao);
    }

}