<?php

use Wms\Domain\Entity\Produto\Embalagem,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Core\Grid;

/**
 * Description of Web_ProdutoEmbalagemController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ProdutoEmbalagemController extends Crud
{

    public $entityName = 'Produto\Embalagem';

    /**
     * Lista todos os embalagens cadastrados para uma determinada pessoa
     */
    public function listJsonAction()
    {
        $params = $this->getRequest()->getParams();
        $repoEmbalagem = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $embalagens = $repoEmbalagem->findBy(array('codProduto' => $params['idProduto'], 'grade' => $params['grade']), array('isPadrao' => 'DESC', 'descricao' => 'ASC'));
        $arrayEmbalagens = array();

        foreach ($embalagens as $embalagem) {

            $dataInativacao = "EMB. ATIVA";
            $checked = '';
            if (!is_null($embalagem->getDataInativacao())) {
                $dataInativacao = $embalagem->getDataInativacao();
                $checked = 'checked ';
                $dataInativacao = $dataInativacao->format('d/m/Y');
            }

            $arrayEmbalagens[] = array(
                'id' => $embalagem->getId(),
                'descricao' => $embalagem->getDescricao(),
                'quantidade' => $embalagem->getQuantidade(),
                'isPadrao' => $embalagem->getIsPadrao(),
                'lblIsPadrao' => ($embalagem->getIsPadrao() == 'S') ? 'SIM' : 'NÃO',
                'CBInterno' => $embalagem->getCBInterno(),
                'lblCBInterno' => ($embalagem->getCBInterno() == 'S') ? 'SIM' : 'NÃO',
                'imprimirCB' => $embalagem->getImprimirCB(),
                'lblImprimirCB' => ($embalagem->getImprimirCB() == 'S') ? 'SIM' : 'NÃO',
                'codigoBarras' => $embalagem->getCodigoBarras(),
                'endereco' => ($embalagem->getEndereco()) ? $embalagem->getEndereco()->getDescricao() : '',
                'embalado' => $embalagem->getEmbalado(),
                'capacidadePicking' => $embalagem->getCapacidadePicking(),
                'pontoReposicao' => $embalagem->getPontoReposicao(),
                'lblEmbalado' => ($embalagem->getEmbalado() == 'S') ? 'SIM' : 'NÃO',
                'ativarDesativar' => $checked,
                'dataInativacao' => $dataInativacao,
            );
        }

        $this->_helper->json($arrayEmbalagens, true);
    }

    public function verificarEstoqueReservaAjaxAction()
    {
        $id = $this->_getParam('id');
        list($status, $msg) = $this->repository->checkEstoqueReservaById($id);
        $this->_helper->json(array('status' => $status, 'msg' => $msg));
    }
}