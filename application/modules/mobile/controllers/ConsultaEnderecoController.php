<?php
use Wms\Controller\Action,
    \Wms\Util\Endereco as EnderecoUtil,
    Wms\Domain\Entity\Expedicao;

class Mobile_ConsultaEnderecoController extends Action
{
    public function indexAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        if (isset($codigoBarras) && !empty($codigoBarras)) {
            $LeituraColetor = new \Wms\Service\Coletor();
            $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $endereco = EnderecoUtil::formatar($codigoBarras);
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
            if (!isset($enderecoEn) || empty($enderecoEn)) {
                $produtos = array(0 => array('COD_PRODUTO' => 'Endereço não encontrado', 'DSC_PRODUTO' => ''));
                $this->_helper->json(array('produtos' => $produtos));
            }

            /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
            $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
            $embalagemEntities = $embalagemRepo->findBy(array('endereco' => $enderecoEn));

            if (!isset($embalagemEntities) || empty($embalagemEntities)){
                $produtos = array(0 => array('COD_PRODUTO' => 'Não Existe produto nesse endereço', 'DSC_PRODUTO' => ''));
                $this->_helper->json(array('produtos' => $produtos));
            }

            foreach ($embalagemEntities as $key => $embalagemEn) {
                $codProduto[$key] = $embalagemEn->getCodProduto();
            }
            $codProdutos = implode(',',$codProduto);

            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
            $produtos = $produtoRepo->getProdutos($codProdutos);

            $this->_helper->json(array('produtos' => $produtos));

        }

    }

}

