<?php

use Wms\Controller\Action,
    \Wms\Util\Endereco as EnderecoUtil;

class Mobile_ConsultaEnderecoController extends Action {

    public function indexAction() {
        $codigoBarras = $this->_getParam('codigoBarras');
        if (!empty($codigoBarras)) {
            try {
                $LeituraColetor = new \Wms\Service\Coletor();
                $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);
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

}
