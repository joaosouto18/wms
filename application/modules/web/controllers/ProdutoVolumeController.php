<?php

use Wms\Domain\Entity\Produto\Volume,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Core\Grid,
    Core\Util\Converter;

/**
 * Description of Web_ProdutoVolumeController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ProdutoVolumeController extends Crud
{

    public $entityName = 'Produto\Volume';

    /**
     * Lista as normas de paletizacao cadastradas para produtos
     */
    public function listNormaPaletizacaoJsonAction()
    {
        $em = $this->getEntityManager();

        $params = $this->getRequest()->getParams();

        $dql = $em->createQueryBuilder()
            ->select('np.id, np.numLastro, np.numCamadas, np.numPeso, np.numNorma, np.isPadrao, 
                    u.id idUnitizador, u.descricao unitizador')
            ->from('wms:Produto\Volume', 'v')
            ->innerJoin('v.normaPaletizacao', 'np')
            ->innerJoin('np.unitizador', 'u')
            ->where('v.codProduto = ?1')
            ->setParameter(1, $params['idProduto'])
            ->andWhere('v.grade = :grade')
            ->setParameter('grade', $params['grade']);

        $normasPaletizacao = array();

        // loop para agrupar normas repetidas, já que a bosta do oracle não faz
        foreach ($dql->getQuery()->getResult() as $row) {
            $normasPaletizacao[$row['id']] = array(
                'id' => $row['id'],
                'numLastro' => $row['numLastro'],
                'numCamadas' => $row['numCamadas'],
                'numPeso' => Converter::enToBr($row['numPeso'], 3),
                'numNorma' => $row['numNorma'],
                'isPadrao' => $row['isPadrao'],
                'idUnitizador' => $row['idUnitizador'],
                'unitizador' => $row['unitizador'],
                'acao' => 'alterar',
            );
        }

        $normasPaletizacao = array_values($normasPaletizacao);

        // busca unitizadores
        $unitizadores = $em->getRepository('wms:Armazenagem\Unitizador')->findAll();

        foreach ($normasPaletizacao as $key => $normaPaletizacao) {

            foreach ($unitizadores as $unitizador) {
                $normasPaletizacao[$key]['unitizadores'][] = array(
                    'id' => $unitizador->getId(),
                    'descricao' => $unitizador->getDescricao(),
                );
            }

            $volumes = $em->getRepository('wms:Produto\Volume')
                ->findBy(array('normaPaletizacao' => $normaPaletizacao['id']), array('codigoSequencial' => 'ASC'));

            foreach ($volumes as $volume) {

                $dataInativacao = "VOL. ATIVO";
                $checked = '';
                if (!is_null($volume->getDataInativacao())) {
                    $dataInativacao = $volume->getDataInativacao();
                    $checked = 'checked ';
                    $dataInativacao = $dataInativacao->format('d/m/Y');
                }

                $idNormaPaletizacao = ($volume->getNormaPaletizacao()) ? $volume->getNormaPaletizacao()->getId() : 0;

                $normasPaletizacao[$key]['volumes'][] = array(
                    'id' => $volume->getId(),
                    'codigoSequencial' => $volume->getCodigoSequencial(),
                    'idNormaPaletizacao' => $idNormaPaletizacao,
                    'largura' => $volume->getLargura(),
                    'altura' => $volume->getAltura(),
                    'profundidade' => $volume->getProfundidade(),
                    'cubagem' => $volume->getCubagem(),
                    'peso' => $volume->getPeso(),
                    'descricao' => $volume->getDescricao(),
                    'normaPaletizacao' => $volume->getNormaPaletizacao()->getId(),
                    'CBInterno' => $volume->getCBInterno(),
                    'capacidadePicking' => $volume->getCapacidadePicking(),
                    'pontoReposicao' => $volume->getPontoReposicao(),
                    'lblCBInterno' => ($volume->getCBInterno() == 'S') ? 'SIM' : 'NÃO',
                    'imprimirCB' => $volume->getImprimirCB(),
                    'lblImprimirCB' => ($volume->getImprimirCB() == 'S') ? 'SIM' : 'NÃO',
                    'codigoBarras' => $volume->getCodigoBarras(),
                    'endereco' => ($volume->getEndereco()) ? $volume->getEndereco()->getDescricao() : '',
                    'acao' => 'alterar',
                    'ativarDesativar' => $checked,
                    'dataInativacao' => $dataInativacao,
                );
            }
        }

        $this->_helper->json($normasPaletizacao, true);
    }

    /**
     * Lista todos os volumes cadastrados para uma determinada pessoa
     */
    public function listJsonAction()
    {
        $params = $this->getRequest()->getParams();
        $repo = $this->getEntityManager()->getRepository('wms:Produto\Volume');
        $volumes = $repo->findBy(array('produto' => $params['idProduto']), array('codigoSequencial' => 'ASC'));
        $arrayVolumes = array();

        foreach ($volumes as $volume) {

            $dataInativacao = "VOL. ATIVO";
            $checked = '';
            if (!is_null($volume->getDataInativacao())) {
                $dataInativacao = $volume->getDataInativacao();
                $checked = 'checked ';
                $dataInativacao = $dataInativacao->format('d/m/Y');
            }

            $arrayVolumes[] = array(
                'id' => $volume->getId(),
                'codigoSequencial' => $volume->getCodigoSequencial(),
                'largura' => $volume->getLargura(),
                'altura' => $volume->getAltura(),
                'profundidade' => $volume->getProfundidade(),
                'cubagem' => $volume->getCubagem(),
                'peso' => $volume->getPeso(),
                'descricao' => $volume->getDescricao(),
                'normaPaletizacao' => $volume->getNormaPaletizacao()->getId(),
                'ativarDesativar' => $checked,
                'dataInativacao' => $dataInativacao,
            );
        }
        $this->_helper->json($arrayVolumes, true);
    }

    /**
     *
     */
    public function detalhesAjaxAction()
    {
        // reset layout
        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout('ajax');

        //parametros
        $params = $this->getRequest()->getParams();
        $repo = $this->getEntityManager()->getRepository('wms:Produto\Volume');
        //view
        $this->view->volume = $repo->findOneBy(array('id' => $params['id']));
    }

}