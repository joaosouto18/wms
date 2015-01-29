<?php

use Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Page,
    Wms\Module\Web\Form\Pessoa\Fisica\Filtro as FiltroConferente;

/**
 * Description of Web_UsuarioController
 *
 * @author Adriano Uliana
 */
class Web_Cadastro_ConferenteController extends Crud
{

    protected $entityName = 'Pessoa\Fisica\Conferente';
    protected $pkField = 'pessoa';

    public function indexAction()
    {
        $form = new FiltroConferente;

        if ($values = $form->getParams()) {
            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('p.id, p.nome, p.cpf')
                    ->from('wms:Pessoa\Fisica\Conferente', 'c')
                    ->leftJoin('c.pessoa', 'p')
                    ->orderBy('p.nome');

            // caso tenha nome e cpf verifico condicoes
            if (!empty($nome)) {
                $nome = mb_strtoupper($nome, 'UTF-8');
                $source->andWhere("p.nome LIKE '{$nome}%'");
            }
            if (!empty($cpf)) {
                $cpf = str_replace(array('.', '-'), '', $cpf);
                $source->andWhere("p.cpf = '{$cpf}'");
            }

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->addColumn(array(
                        'label' => 'Conferente',
                        'index' => 'nome',
                    ))
                    ->addColumn(array(
                        'label' => 'CPF',
                        'index' => 'cpf',
                        'render' => 'documento',
                    ))
                    ->addAction(array(
                        'label' => 'Editar',
                        'actionName' => 'edit',
                        'pkIndex' => 'id'
                    ))
                    ->addAction(array(
                        'label' => 'Excluir',
                        'actionName' => 'delete',
                        'pkIndex' => 'id',
                        'cssClass' => 'del',
                    ));
            $this->view->grid = $grid->build();
            $form->setSession($values)
                    ->populate($values);
        }
        $this->view->form = $form;
    }

}
