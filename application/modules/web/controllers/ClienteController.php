<?php

use Wms\Domain\Entity\Pessoa\Papel\Cliente,
    Wms\Module\Web\Page,
    Wms\Module\Web\Form\Subform\Pessoa\Papel\FiltroCliente;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ClienteController extends \Wms\Controller\Action
{

    protected $entityName = 'Pessoa\Papel\Cliente';

    /**
     *
     * @return type 
     */
    public function indexAction()
    {
        $form = new FiltroCliente;
        
        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                ->select('c, p.nome, NVL(pf.cpf, pj.cnpj) cpfCnpj')
                ->from('wms:Pessoa\Papel\Cliente', 'c')
                ->innerJoin('c.pessoa', 'p')
                ->leftJoin('wms:Pessoa\Fisica', 'pf', "WITH", "pf.id = p.id")
                ->leftJoin('wms:Pessoa\Juridica', 'pj', "WITH", "pj.id = p.id")
                ->orderBy('p.nome');

            if (!empty($nome)) {
                $nome = mb_strtoupper($nome, 'UTF-8');
                $source->where("p.nome LIKE '%{$nome}%'");
            }
            if (!empty($cpf)) {
                if (strlen($cpf) == 11) {
                    $source->andWhere("pf.cpf = '{$cpf}'");
                }
                if (strlen($cpf) == 14) {
                    $source->andWhere("pj.cnpj = '{$cpf}'");
                }
            }

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addColumn(array(
                        'label' => 'Cliente',
                        'index' => 'nome'
                    ))
                    ->addColumn(array(
                        'label' => 'CPF/CNPJ',
                        'index' => 'cpfCnpj',
                        'render' => 'documento',
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar Cliente',
                        'title' => 'Detalhes do Cliente',
                        'actionName' => 'view-cliente-ajax',
                        'cssClass' => 'view-cliente dialogAjax',
                        'pkIndex' => 'id'
                    ));

            $this->view->grid = $grid->build();
            $form->populate($values);
        }
        $this->view->form = $form;
    }

    /**
     *
     * @throws \Exception 
     */
    public function viewClienteAjaxAction()
    {

        $id = $this->getRequest()->getParam('id');

        if ($id == null) {
            throw new \Exception('ID do cliente inválido');
        }

        $cliente = $this->em->find('wms:Pessoa\Papel\Cliente', $id);

        if ($cliente == null) {
            throw new \Exception('Este cliente não existe');
        }

        $this->view->pessoa = $cliente;
    }

}