<?php

use \Wms\Domain\Entity\RelatoriosSimples,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Controller responsável por gerar relatórios simples
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class Web_RelatoriosSimplesController extends Crud
{
    public $entityName = 'RelatoriosSimples';

    private function getConsulta($params,$relatorio) {
        $arrayConsulta=array();

        /** @var \Wms\Domain\Entity\RelatoriosSimplesRepository $consulta */
        $consulta=$this->getEntityManager()->getRepository('wms:RelatoriosSimples');

        switch($relatorio){
            case "relatorio-ondas":
                $arrayConsulta=$consulta->getConsultaRelatorioOndas($params,$relatorio);
                $arrayConsulta['titulo']="Relatório de Ondas";
                break;

            case "relatorio-reserva-inativa":
                $arrayConsulta=$consulta->getConsultaRelatorioReservaInativa($params);
                $arrayConsulta['titulo']="Relatório de Reservas de Estoque Inativas";
                break;

            case "relatorio-pedidos-expedicao":
                $arrayConsulta=$consulta->getConsultaRelatorioPedidosExpedicaoSql($params);
                $arrayConsulta['titulo']="Relatório de Pedidos da Expedição";
                break;
        }

        return $arrayConsulta;
    }

    public function imprimirAction() {
        $params=$this->_getAllParams();
        if ( !empty($params['relatorio']) ){
            try {
                $relatorio=$params['relatorio'];
                $consulta=$this->getConsulta($params,$relatorio);

                if ( !empty($consulta) ){
                    if ( $consulta!=false ){

                        if ($params['tipo']=='pdf'){

                            /*$Report = new \Wms\Module\Web\Report\RelatoriosSimples();

                            if ( $Report->init($consulta,$relatorio ) ) {
                                $this->addFlashMessage('error', 'Erro ao gerar o relatório');
                            }*/

                            $nome=strtoupper($relatorio);
                            $titulo=$consulta['titulo'];
                            unset($consulta['titulo']);
                            $this->exportPDF($consulta,$nome,$titulo,"P");
                        }

                    }   else {
                        $this->addFlashMessage('error', 'Relatório não gerado');
                    }
                }

            } catch (\Exception $e) {
                echo $e->getMessage();
                die;
            }
        } else {
            $this->addFlashMessage('error', 'Relatório não gerado');
        }
    }

}
