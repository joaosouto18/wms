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

    /*
     * Verifica se ja existe o codigo de barras informado
     */

    public function verificarCodigoBarrasAjaxAction()
    {

        $params = $this->getRequest()->getParams();
        extract($params);

        $arrayMensagens = array(
            'status' => 'success',
            'msg' => 'Sucesso!',
        );
        try {
            if (($idProduto == null) || ($grade == null)) {
                throw new \Exception('Codigo e Grade do produto devem ser fornecidos');
            }

            $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('nfi.id, nfi.grade, nfi.quantidade, p.id idProduto, p.descricao,
                       NVL(pv.codigoBarras, pe.codigoBarras) codigoBarras,
                       NVL(pe.descricao, \'\') descricaoEmbalagem,
                       NVL(pv.descricao, \'\') descricaoVolume')
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin('nf.itens', 'nfi')
                ->innerJoin('nfi.produto', 'p')
                ->leftJoin('p.embalagens', 'pe')
                ->leftJoin('p.volumes', 'pv')
                ->andWhere('p.grade = nfi.grade')
                ->andWhere('(pe.grade = p.grade OR pv.grade = p.grade)')
                ->andWhere('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
                ->setParameter('codigoBarras', $codigoBarras);

            $produto = $dql->getQuery()->getResult();

            if ($produto) {
                throw new \Exception('Este código de barras ja foi cadastrado no produto ' . $produto[0]['idProduto'] . ' grade ' . $produto[0]['grade'] . '.');
            }
        } catch (\Exception $e) {
            $arrayMensagens = array(
                'status' => 'error',
                'msg' => $e->getMessage(),
            );
        }

        $this->_helper->json($arrayMensagens, true);
    }

    public function verificarEstoqueReservaAjaxAction()
    {
        $id = $this->_getParam('id');
        list($status, $msg) = $this->repository->checkEstoqueReservaById($id);
        $this->_helper->json(array('status' => $status, 'msg' => $msg));
    }
}