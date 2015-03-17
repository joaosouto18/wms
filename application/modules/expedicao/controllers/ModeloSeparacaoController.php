<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao\ModeloSeparacao as ModelosSeparacaoGrid,
    Wms\Module\Expedicao\Form\ModeloSeparacao as ModeloSeparacaoForm,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Page,
    Wms\Domain\Entity\Expedicao;

class Expedicao_ModeloSeparacaoController  extends  Crud
{
    protected $entityName = 'Expedicao\ModeloSeparacao';

    public function indexAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloRepository */
        $modeloRepository   = $this->em->getRepository('wms:Expedicao\ModeloSeparacao');

        $modelos = $modeloRepository->getModelos();

        $grid = new ModelosSeparacaoGrid();
        $this->view->grid = $grid->init($modelos)->render();
    }

    public function deleteAction()
    {
        try{
            $id = $this->_getParam('id');
            $modeloRepository = $this->em->getRepository('wms:Expedicao\ModeloSeparacao');
            $modeloSeparacao   = $modeloRepository->findOneBy(array('id'=>$id));

            $this->getEntityManager()->remove($modeloSeparacao);
            $this->getEntityManager()->flush();
            $this->addFlashMessage('success', 'Modelo de Separação excluido com sucesso' );
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage() );
        }
        $this->_redirect('/expedicao/modelo-separacao');
    }

    public function addAction()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                ),
            )
        ));

        $form = new ModeloSeparacaoForm();

        try {
            $params = $this->getRequest()->getParams();

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $entity = $this->montarModeloSeparacao($params['identificacao']);
                $this->em->persist($entity);
                $this->em->flush();
                $this->_helper->messenger('success', 'Modelo de Separação inserido com sucesso.');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }

    private function montarModeloSeparacao($params) {
        $entity = new Expedicao\ModeloSeparacao();
        $entity->setDescricao($params['descricao']);
        $entity->setUtilizaCaixaMaster($this->getBooleanValue($params['utilizaConversaoParaCaixaMaster']));
        $entity->setUtilizaEtiquetaMae($this->getBooleanValue($params['utilizaEtiquetaMae']));
        $entity->setUtilizaQuebraColetor($this->getBooleanValue($params['utilizaQuebraNaConferenciaDoColetor']));
        $entity->setQuebraPulmaDoca($params['quebraNoProcessoPulmaoDoca']);
        $entity->setTipoQuebraVolume($params['tipoDeQuebraNoVolume']);
        $entity->setTipoDefaultEmbalado($params['tipoDefaultDeEmbalados']);
        $entity->setTipoConferenciaEmbalado($params['tipoDeConferenciaParaEmbalados']);
        $entity->setTipoConferenciaNaoEmbalado($params['tipoDeConferenciaParaNaoEmbalados']);
        $entity->setTipoSeparacaoFracionado($params['tipoDeSeparacaoFracionados']);
        $entity->setTipoSeparacaoNaoFracionado($params['tipoDeSeparacaoNaoFracionados']);

        $entity->setTiposQuebraFracionado(array());
        $this->adicionarTipoQuebra($entity->getTiposQuebraFracionado(), $params['ruaFracionados']);
        $this->adicionarTipoQuebra($entity->getTiposQuebraFracionado(), $params['linhaDeSeparacaoFracionados']);
        $this->adicionarTipoQuebra($entity->getTiposQuebraFracionado(), $params['pracaFracionados']);
        $this->adicionarTipoQuebra($entity->getTiposQuebraFracionado(), $params['clienteFracionados']);

        $entity->setTiposQuebraNaoFracionado(array());
        $this->adicionarTipoQuebra($entity->getTiposQuebraNaoFracionado(), $params['ruaNaoFracionados']);
        $this->adicionarTipoQuebra($entity->getTiposQuebraNaoFracionado(), $params['linhaDeSeparacaoNaoFracionados']);
        $this->adicionarTipoQuebra($entity->getTiposQuebraNaoFracionado(), $params['pracaNaoFracionados']);
        $this->adicionarTipoQuebra($entity->getTiposQuebraNaoFracionado(), $params['clienteNaoFracionados']);

        return $entity;
    }

    private function adicionarTipoQuebra($attribute, $tipo) {
        if ($tipo) {
            array_push($attribute, $tipo);
        }
    }

    private function getBooleanValue($param) {
        return $param ? 'S' : 'N';
    }
}