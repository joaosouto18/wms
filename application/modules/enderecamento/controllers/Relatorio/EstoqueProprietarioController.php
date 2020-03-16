<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\EstoqueReport,
    \Wms\Domain\Entity\Enderecamento\EstoqueProprietario;

class Enderecamento_Relatorio_EstoqueProprietarioController extends Action
{
    public function indexAction(){
        $form = new \Wms\Module\Armazenagem\Form\EstoqueProprietario\FiltroRelatorio();
        $form->init($this->getSystemParameterValue("UTILIZA_GRADE"));
        $values = $form->getParams();

        if (isset($values['buscar'])) {

        }
        elseif (isset($values['imprimir'])){
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueProprietarioRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\EstoqueProprietario');
            $result = $estoqueRepo->getHistoricoEstoqueProprietario($values['codPessoa'], $values['idProduto'], $values['grade']);
            $this->exportPDF($result, 'EstoqueProprietario', 'Relatório de Estoque Proprietário', 'P');
        }
        $this->view->form = $form;
    }

    public function getGerencialProprietarioAjaxAction()
    {
        $data = $this->getRequest()->getParams();
        $result = [];

        if ($data['tipoBusca'] === 'H') {
            $result = $this->em->getRepository(EstoqueProprietario::class)->getHistoricoProprietarioGerencial($data);
        } elseif ($data['tipoBusca'] === 'E') {
            $result = $this->em->getRepository(EstoqueProprietario::class)->getEstoqueProprietarioGerencial($data);
        }

        $this->_helper->json(['results' => $result]);
    }

    public function getListProprietariosAjaxAction()
    {
        $this->_helper->json(['proprietarios' => $this->em->getRepository(EstoqueProprietario::class)->getProprietarios()]);
    }

    public function exportAjaxAction()
    {
        $params = json_decode($this->getRequest()->getRawBody(),true);
        $title = ($params['tipoBusca'] === 'H') ? "Histórico de Movimentações" : "Estoque Gerencial";

        $headerMap = [
            'nomProp' => 'Proprietário',
            'codProduto' => 'Código',
            'dscProduto' => 'Produto',
            'tipoMov' => 'Tipo Mov.',
            'dthMov' => 'Data Mov.',
            'qtdMov' => 'Qtd Mov.',
            'qtdEstq' => 'Saldo Final',
            'qtdPend' => 'Pend. p/ Entrar'
        ];

        if ($params['destino'] == 'pdf')
            $this->exportPDF($params['list'], $title, 'Relatório Gerencial de Proprietário', 'L', $headerMap);
        if ($params['destino'] == 'csv')
            $this->exportCSV($params['list'], $title, true, $headerMap);
    }
}