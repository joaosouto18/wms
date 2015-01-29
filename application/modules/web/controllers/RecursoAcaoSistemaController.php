<?php

use Wms\Module\Web\Controller\Action;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_RecursoAcaoSistemaController extends Action
{

    public function listAction()
    {
        $repo = $this->em->getRepository('wms:RecursoAcao');

        $repo->getAllAsTree();

        $this->view->grid = $grid->build();
    }

    /**
     * Lista todos os enderecos cadastrados para uma determinada pessoa
     */
    public function listJsonAction()
    {
        $params = $this->getRequest()->getParams();

        $recursosAcaoRepo = $this->em->getRepository('wms:Sistema\Recurso\Vinculo');
        $recursosAcao = $recursosAcaoRepo->findBy(array('recurso' => $params['idRecurso']));

        $matriz = array();

        foreach ($recursosAcao as $recursoAcao) {
            $matriz[] = array(
                'id' => $recursoAcao->getId(),
                'description' => $recursoAcao->getNome(),
            );
        }

        echo json_encode($matriz);
        exit;
    }

}
