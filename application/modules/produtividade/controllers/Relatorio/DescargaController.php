<?php
class Produtividade_Relatorio_DescargaController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $form = new \Wms\Module\Produtividade\Form\RelatorioDescarga();
        $params = $form->getParams();
        if ($params) {

            /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $descargaRepo */
            $descargaRepo = $this->em->getRepository('wms:Recebimento\Descarga');
            $produtos = $descargaRepo->getInfosDescarga($params);

            if ($produtos) {
                $this->exportCSV($produtos,'Descarga_Recebimento',true);
            }

            $form->populate($params);
        }

        $this->view->form = $form;
    }
}