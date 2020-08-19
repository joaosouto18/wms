<?php

use Core\Linfo\Exceptions\FatalException;
use Core\Linfo\Linfo;
use Core\Linfo\Common;

class Web_DevtoolsController extends \Wms\Controller\Action
{

    public function phpinfoAction(){

    }

    public function gerenciarServidorAction()
    {
        $settings = Common::getVarFromFile(APPLICATION_PATH . '/configs/linfo.php', 'settings');
        $linfo = new Linfo($settings);
        $linfo->scan();
        $output = new Core\Linfo\Output\Html($linfo);
        $output->output();
    }

    public function queryAction() {
        $params = $this->getRequest()->getParams();

        $conexaoRepo = $this->getEntityManager()->getRepository('wms:Integracao\ConexaoIntegracao');
        $conexoes = $conexaoRepo->getConexoesDisponiveis();

        if ($conexoes == null) {
            $this->addFlashMessage("error","Nenhuma conexão com banco de dados cadastrada");
        } else {
            try {
                $form = new \Wms\Module\Web\Form\Query();
                $form->init($conexoes);
                $form->setDefaults($params);
                $this->view->form = $form;

                if (isset($params['btnBuscar']) || isset($params['btnPDF']) || isset($params['btnXLS'])) {
                    if ($params['conexao'] == null) throw new \Exception("Conexão de banco de dados não informada");
                    $conexaoEn = $conexaoRepo->find($params['conexao']);
                    if ($conexaoEn == null) throw new \Exception("Conexão de banco de dados não encontrada");

                    $query = $params['query'];
                    if (strlen(trim($query)) == 0) throw new \Exception("Informe uma Query");

                    $words = explode(" ",trim($query));
                    $update = true;
                    if (strtoupper($words[0]) == "SELECT") {
                        $update = false;
                    }

                    if ($update == true && (isset($params['btnPDF']) || isset($params['btnXLS']))) {
                        throw new \Exception('Este comando é um comando de execução e não produz resultado para ser exportado');
                    }

                    $result = $conexaoRepo->runQuery($query, $conexaoEn, $update);
                    if (count($result) == 0) {
                        if ($update == true) {
                            $this->addFlashMessage('info', 'Comando executado com sucesso');
                        } else {
                            $this->addFlashMessage('info', 'Nenhum registro encontrado');
                        }
                    } else {
                        $title = 'QueryResult - ' . $conexaoEn->getDescricao();
                        $assemblyData = array();
                        $assemblyData['title'] = $title;
                        if (isset($params['btnBuscar'])) {
                            $grid = new \Wms\Module\Web\Grid\RelatorioCustomizado();
                            $grid->init($result, $assemblyData);
                            $this->view->grid = $grid;
                        }
                        if (isset($params['btnPDF'])) {
                            $this->exportPDF($result,  $title,$title,'L');
                        }
                        if (isset($params['btnXLS'])) {
                            $this->exportCSV($result, $title,true );
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->addFlashMessage('error',$e->getMessage());
            }

        }



    }

}