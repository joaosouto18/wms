<?php
use Wms\Controller\Action,
    \Wms\Util\Endereco as EnderecoUtil;

class Mobile_ConsultaEnderecoController extends Action
{
    public function indexAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        if (!empty($codigoBarras)) {
            try {
                $LeituraColetor = new \Wms\Service\Coletor();
                $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);

                $usaGrade = $this->getSystemParameterValue('UTILIZA_GRADE');

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
                $endereco = EnderecoUtil::formatar($codigoBarras);

                /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
                if (!isset($enderecoEn) || empty($enderecoEn)) {
                    throw new Exception("Endereço não encontrado");
                }

                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
                $itens = $estoqueRepo->findBy(array('depositoEndereco' => $enderecoEn));

                if (empty($itens)) {
                    throw new Exception("Não existe produto nesse endereço");
                }

                $result = array();
                /** @var \Wms\Domain\Entity\Enderecamento\Estoque $item */
                foreach ($itens as $item) {
                    $produtoEn = $item->getProduto();
                    $produto = array(
                        'produto' => $produtoEn->getId(),
                        'grade' => ($usaGrade === 'S') ? $produtoEn->getGrade(): '',
                        'desc' => $produtoEn->getDescricao()
                    );
                    $result[] = $produto;
                }

                $this->_helper->json(array('status' => 'ok', 'result' => $result));

            }catch (Exception $e){
                $this->_helper->json(array('status'=> 'exception', 'msg' => $e->getMessage()));
            }
        }
    }
}

