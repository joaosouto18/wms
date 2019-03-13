<?php

use \Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Form\LogomarcaForm as LogomarcaForm,
    \Wms\Module\Web\Page;

/**
 * Description of Web_FilialController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_FilialController extends Crud
{

    protected $entityName = 'Filial';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
            ->select('f, pj.nome, pj.nomeFantasia, pj.cnpj')
            ->from('wms:Filial', 'f')
            ->innerJoin('f.juridica', 'pj')
            ->orderBy('pj.nome');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('recurso-sistema-grid');
        $grid->addColumn(array(
            'label' => 'Nome Fantasia',
            'index' => 'nomeFantasia',
            'filter' => array(
                'render' => array(
                    'type' => 'text',
                    'condition' => array('match' => array('fulltext'))
                ),
            ),
        ))
            ->addColumn(array(
                'label' => 'Razão Social',
                'index' => 'nome',
                'filter' => array(
                    'render' => array(
                        'type' => 'text',
                        'condition' => array('match' => array('fulltext'))
                    ),
                ),
            ))
            ->addColumn(array(
                'label' => 'CNPJ',
                'index' => 'cnpj',
                'render' => 'documento',
                'filter' => array(
                    'render' => array(
                        'type' => 'number',
                    ),
                ),
                'hasOrdering' => false,
            ))
            ->addColumn(array(
                'label' => 'Ativo',
                'index' => 'isAtivo',
                'render' => 'SimOrNao',
                'filter' => array(
                    'render' => array(
                        'type' => 'select',
                        'attributes' => array(
                            'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO')
                        )
                    ),
                ),
            ))
            ->addAction(array(
                'label' => 'Editar',
                'actionName' => 'edit',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Logomarca',
                'actionName' => 'logomarca',
                'pkIndex' => 'id'
            ))
            ->setHasOrdering(true);

        $desativar = new \Core\Grid\Action(array(
            'label' => 'Desativar',
            'actionName' => 'desativar',
            'pkIndex' => 'id',
            'cssClass' => 'confirm'
        ));

        $desativar->setCondition('\Wms\Module\Web\Grid\Condition::isAtivo');

        $ativar = new \Core\Grid\Action(array(
            'label' => 'Ativar',
            'actionName' => 'ativar',
            'pkIndex' => 'id',
            'cssClass' => 'confirm'
        ));

        $ativar->setCondition('\Wms\Module\Web\Grid\Condition::isInativo');

        $grid->addAction($ativar)
            ->addAction($desativar);

        $this->view->grid = $grid->build();
    }

    /**
     * Ativa desativa uma filial
     *
     * @param int $id
     * @param boolean $boolean
     * @return type
     * @throws \Exception
     */
    public function ativaDesativa($id, $boolean)
    {
        try {
            if ($id == null)
                throw new \Exception('Id deve ser enviado para executar a ação');

            $deposito = $this->em->find('wms:Filial', (int)$id);

            $deposito->setIsAtivo($boolean);
            $this->em->persist($deposito);
            $this->em->flush();

            $this->_helper->messenger('success', 'Registro alterado com sucesso');
            return $this->redirect('index');
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            return $this->redirect('index');
        }
    }

    /**
     *
     * @return type
     */
    public function ativarAction()
    {
        $id = $this->getRequest()->getParam('id');
        $this->ativaDesativa($id, true);
    }

    /**
     *
     * @return type
     */
    public function desativarAction()
    {
        $id = $this->getRequest()->getParam('id');
        $this->ativaDesativa($id, false);
    }

    public function logomarcaAction()
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
            )
        ));

        if ($this->getRequest()->isPost()) {

            $uploaddir = (dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '\public\img/';
            $uploadfile = $uploaddir . basename($_FILES['arquivo']['name']);
            $nome       = $uploaddir . 'logo_cliente.jpg';

            if( ($_FILES['arquivo']['type'] == 'image/jpeg' || $_FILES['arquivo']['type'] == 'image/png' || $_FILES['arquivo']['type'] == 'image/gif') && (move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadfile)) ){
                 rename($uploadfile, $nome);
                 $this->_helper->messenger('success', 'Logomarca enviada com sucesso!');
            }
            else
                $this->_helper->messenger('error', 'A logomarca não foi enviada. Certifique-se de enviar apenas imagens com extensão JPEG, GIF ou PNG. ');

        }
    }
}