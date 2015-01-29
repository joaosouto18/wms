<?php

use Wms\Domain\Entity\Pessoa\Papel\Transportador,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Form\Subform\Pessoa\Papel\FiltroPJ;

/**
 * Description of Web_Consulta_TransportadorController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_Consulta_TransportadorController extends \Wms\Controller\Action
{

    protected $entityName = 'Pessoa\Papel\Transportador';

    public function indexAction()
    {
        $form = new FiltroPJ;

        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('t.idExterno, p.id, p.nome, p.nomeFantasia,  p.cnpj')
                    ->from('wms:Pessoa\Papel\Transportador', 't')
                    ->innerJoin('t.pessoa', 'p')
                    ->orderBy('p.nome');

            if (!empty($nome))
                $source->andWhere("p.nome LIKE :nome")
                        ->setParameter('nome', mb_strtoupper($nome, 'UTF-8') . '%');

            if (!empty($nomeFantasia))
                $source->andWhere("p.nomeFantasia LIKE :nomeFantasia")
                        ->setParameter('nomeFantasia', mb_strtoupper($nomeFantasia, 'UTF-8') . '%');

            if (!empty($cnpj))
                $source->andWhere("p.cnpj = :cnpj")
                        ->setParameter('cnpj', \Core\Util\String::toNumber($cnpj));


            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addColumn(array(
                        'label' => 'Codigo',
                        'index' => 'idExterno'
                    ))
                    ->addColumn(array(
                        'label' => 'Transportador',
                        'index' => 'nome'
                    ))
                    ->addColumn(array(
                        'label' => 'Nome Fantasia',
                        'index' => 'nomeFantasia'
                    ))
                    ->addColumn(array(
                        'label' => 'CNPJ',
                        'index' => 'cnpj',
                        'render' => 'documento',
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar Transportador',
                        'title' => 'Detalhes do Transportador',
                        'actionName' => 'view-transportador-ajax',
                        'cssClass' => 'view-transportador dialogAjax',
                        'pkIndex' => 'id',
                    ));

            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

    public function viewTransportadorAjaxAction()
    {

        $id = $this->getRequest()->getParam('id');

        if ($id == null) {
            throw new \Exception('ID do transportador inválido');
        }

        $transportador = $this->em->find('wms:Pessoa\Papel\Transportador', $id);

        if ($transportador == null) {
            throw new \Exception('Este transportador não existe');
        }

        $this->view->pessoa = $transportador;
    }

}