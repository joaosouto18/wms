<?php

use Wms\Controller\Action,
    \Wms\Util\Endereco as EnderecoUtil,
    \Wms\Util\Coletor as ColetorUtil;

class Mobile_ConsultaEnderecoController extends Action {

    public function indexAction() {
        $codigoBarras = $this->_getParam('codigoBarras');
        if (!empty($codigoBarras)) {
            try {
                $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarras);
                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
                $endereco = EnderecoUtil::formatar($codigoBarras);
                $result = $enderecoRepo->getProdutoPorEndereco($endereco);
                $this->_helper->json(array('status' => 'ok', 'result' => $result));
            } catch (Exception $e) {
                $this->_helper->json(array('status' => 'exception', 'msg' => $e->getMessage()));
            }
        }
    }

    public function consultarAction() {
        $codigoBarras = $this->_getParam('codigoBarras');
        if (!empty($codigoBarras)) {
            try {
                //$codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarras);
                //$endereco = EnderecoUtil::formatar($codigoBarras);

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $codigoBarras = ColetorUtil::adequaCodigoBarras($codigoBarras);

                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
                $result = $enderecoRepo->getEnderecosPorProduto($codigoBarras);

                $this->_helper->json(array('status' => 'ok', 'result' => $result));
            } catch (Exception $e) {
                $this->_helper->json(array('status' => 'exception', 'msg' => $e->getMessage()));
            }
        }
    }

}
