<?php

use Wms\Domain\Entity\Pessoa\Papel\Cliente,
    Wms\Module\Web\Page,
    Wms\Domain\Entity\Auditoria,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Module\Web\Form\Sistema\Recurso\Auditoria\Filtro as FiltroForm;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_AuditoriaController extends \Wms\Controller\Action
{

    protected $entityName = 'Sistema\Recurso\Auditoria';

    public function indexAction()
    {
        $form = new FiltroForm;
        
        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('a, u.login, pj.nomeFantasia filial, r.descricao')
                    ->from('wms:Sistema\Recurso\Auditoria', 'a')
                    ->innerJoin('a.usuario', 'u')
                    ->innerJoin('a.filial', 'f')
                    ->innerJoin('f.juridica', 'pj')
                    ->innerJoin('a.recurso', 'r')
                    ->orderBy('a.datOperacao');

            if ((!empty($dataInicial)) && (!empty($dataFinal))) {
                $dataI = new \DateTime(str_replace("/", "-", $dataInicial));
                $dataF = new \DateTime(str_replace("/", "-", $dataFinal));

                $source->andWhere("TRUNC(a.datOperacao) BETWEEN ?1 AND ?2")
                        ->setParameter(1, $dataI)
                        ->setParameter(2, $dataF);
            }

            if ($filial)
                $source->andWhere("a.filial = {$filial}");

            if ($recurso)
                $source->andWhere("a.recurso = '{$recurso}'");

            if ($usuario)
                $source->andWhere(" u.login LIKE '{$usuario}%'");

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
            $grid->setId('grid-auditoria');
            $grid->addColumn(array(
                        'label' => 'Filial',
                        'index' => 'filial'
                    ))
                    ->addColumn(array(
                        'label' => 'Recurso',
                        'index' => 'descricao'
                    ))
                    ->addColumn(array(
                        'label' => 'Usuário',
                        'index' => 'login'
                    ))
                    ->addColumn(array(
                        'label' => 'Data da Operação',
                        'index' => 'datOperacao',
                        'render' => 'DataTime'
                    ))
                    ->addColumn(array(
                        'label' => 'Descrição',
                        'index' => 'dscOperacao'
                    ))
                    ->addAction(array(
                        'label' => 'Visualizar',
                        'actionName' => 'view-auditoria-ajax',
                        'cssClass' => 'view-auditoria',
                        'pkIndex' => 'id'
                    ));

            $this->view->grid = $grid->build();
            $form->populate($values);
        }
        $this->view->form = $form;
    }

    /**
     * Auditoria
     */
    public function viewAuditoriaAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');

        $auditoria = $this->em->find('wms:Sistema\Recurso\Auditoria', $id);
        $this->view->auditoria = $auditoria;

        $dql = $this->em->createQueryBuilder()
                ->select('a.dscOperacao, m.dscMascaraAuditoria')
                ->from('wms:Sistema\Recurso\Auditoria', 'a')
                ->innerJoin('a.recurso', 'r')
                ->innerJoin('r.mascaras', 'm')
                ->where('a.id = :idAuditoria')
                ->setParameter('idAuditoria', $id)
                ->orderBy('a.dscOperacao');

        $this->view->dados = $dql->getQuery()->execute();
    }

}

?>
