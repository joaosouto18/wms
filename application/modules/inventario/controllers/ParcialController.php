<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page,
    Wms\Module\Inventario\Form\Produto as FiltroProdutoForm,
    Wms\Module\Web\Form\Deposito\Endereco\Filtro as FiltroEnderecoForm,
    Wms\Module\Inventario\Grid\Produto as ProdutosGrid,
    Wms\Grid\Endereco as EnderecoGrid;

class Inventario_ParcialController extends Action
{

    public function init()
    {
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Filtrar por Produto',
                    'cssClass' => '',
                    'urlParams' => array(
                        'module' => 'inventario',
                        'controller' => 'parcial',
                        'action' => 'produto'
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Filtrar por Endereço',
                    'cssClass' => '',
                    'urlParams' => array(
                        'module' => 'inventario',
                        'controller' => 'parcial',
                        'action' => 'endereco'
                    ),
                    'tag' => 'a'
                )
            )
        ));
        parent::init();
    }

    public function indexAction()
    {}

    public function produtoAction()
    {
        $form = new FiltroProdutoForm();
        $idInventario = $this->_getParam('hiddenId', $this->_getParam('id'));
        $form->removeElement('fabricante');

        $values = $form->getParams();

        if ($values) {

            if (isset($values['mass-id']) && count($values['mass-id']) > 0 ) {
                /** @var \Wms\Domain\Entity\InventarioRepository $InventarioRepo */
                $InventarioRepo = $this->_em->getRepository('wms:Inventario');
                $enInventario   = $InventarioRepo->save();
                $InventarioRepo->vinculaEnderecos($values['mass-id'], $enInventario->getId());
                $this->_helper->messenger('success', 'Endereços vinculados com sucesso ao inventário:'.$idInventario);
                return $this->redirect('index','index','inventario');
            }

            $grid = new ProdutosGrid();

            $grid->setHiddenId($idInventario);
            $grid->init($values)->render();
            $pager = $grid->getPager();
            $pager->setMaxPerPage(30000);
            $grid->setPager($pager);
            $this->view->grid = $grid->render();

            $form->setSession($values)
                ->populate($values);
        }

        $this->view->form = $form;
    }

    public function enderecoAction()
    {
        $form = new FiltroEnderecoForm();
        $idInventario = $this->_getParam('hiddenId', $this->_getParam('id'));
        $values = $form->getParams();

        if ($values) {
            /** @var \Wms\Domain\Entity\InventarioRepository $InventarioRepo */
            $InventarioRepo = $this->_em->getRepository('wms:Inventario');

            if (isset($values['mass-id']) && count($values['mass-id']) > 0 ) {

                if (empty($idInventario)) {
                    $enInventario   = $InventarioRepo->save();
                    $idInventario   = $enInventario->getId();
                }
                $InventarioRepo->vinculaEnderecos($values['mass-id'], $idInventario);
                $this->_helper->messenger('success', 'Endereços vinculados com sucesso ao inventário:'.$idInventario);
                return $this->redirect('index','index','inventario');
            }

            $values['idInventario'] = $idInventario;
            $grid = new EnderecoGrid();
            $grid->setHiddenId($idInventario);
            $grid->init($values)->render();
            $pager = $grid->getPager();
            $pager->setMaxPerPage(30000);
            $grid->setPager($pager);
            $this->view->grid = $grid->render();
            $form->setSession($values)
                ->populate($values);
        }

        $this->view->IdInventario = $idInventario;
        $this->view->form = $form;
    }

    public function manualAction()
    {
        $this->view ->form = $form = new \Wms\Module\Inventario\Form\Manual();

        $params = $this->_getAllParams();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

            try {
                $params['codProduto'] = $params['id'];
                $params['numContagem'] = 0;

                $depositoEnderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
                $params['codDepositoEndereco'] = $depositoEnderecoRepo->findOneBy(array('descricao' => $params['codDepositoEndereco']))->getId();

                /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
                $inventarioEndRepo = $this->getEntityManager()->getRepository('wms:Inventario\Endereco');
                $inventarioEndEn = $inventarioEndRepo->save($params);
                $params['idInventarioEnd'] = $inventarioEndEn->getId();

                /** @var \Wms\Domain\Entity\Produto\VolumeRepository $prodVolumeRepo */
                $prodVolumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');
                /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $prodEmbalagemRepo */
                $prodEmbalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

                $produtoVolumeEn = $prodVolumeRepo->findOneBy(array('codProduto' => $params['codProduto'], 'grade' => $params['grade']));
                $produtoEmbalagemEn = $prodEmbalagemRepo->findOneBy(array('codProduto' => $params['codProduto'], 'grade' => $params['grade']));

                $params['codProdutoVolume'] = null;
                $params['codProdutoEmbalagem'] = null;
                if (isset($produtoVolumeEn) && !empty($produtoVolumeEn)) {
                    $params['codProdutoVolume'] = $produtoVolumeEn->getId();
                } else {
                    $params['codProdutoEmbalagem'] = $produtoEmbalagemEn->getId();
                }

                /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
                $ordemServicoRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
                $ordemServicoEn = $ordemServicoRepo->saveByInventarioManual();

                $params['codOs'] = $ordemServicoEn->getId();

                /** @var \Wms\Domain\Entity\Inventario\ContagemOsRepository $contOsRepo */
                $contOsRepo = $this->getEntityManager()->getRepository('wms:Inventario\ContagemOs');
                $contOsEn = $contOsRepo->save($params);

                $params['idContagemOs'] = $contOsEn->getId();

                /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contEndRepo */
                $contEndRepo = $this->getEntityManager()->getRepository('wms:Inventario\ContagemEndereco');
                $contEndRepo->save($params);
                $this->addFlashMessage('success','Endereço adicionado ao inventário');

                $this->getEntityManager()->flush();
            } catch(Exception $e) {
                $this->getEntityManager()->rollback();
                $this->addFlashMessage('error', $e->getMessage());
            }
        }
    }

}