<?php

namespace Wms\Module\Web\Grid\Expedicao;


use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class OrdemServico extends Grid
{
    /**
     *
     * @param array $params
     */
    public function init ($idExpedicao, $reconferencia = null)
    {

        /** @var \Wms\Domain\Entity\OrdemServicoRepository $osRepo */
        $osRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');

        if ($reconferencia == null) {
            $resultPrimeiraConf = $osRepo->getOsByExpedicao($idExpedicao);

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($resultPrimeiraConf));
            $this->setSource(new \Core\Grid\Source\Doctrine($resultPrimeiraConf))
                ->setId('expedicao-os-grid')
                ->setAttrib('caption', 'Ordens de Serviço - 1ª Conferência')
                ->setAttrib('class', 'grid-expedicao-os')
                ->addColumn(array(
                    'label' => 'OS',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Responsavel',
                    'index' => 'pessoa',
                ))
                ->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'atividade',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Conferida',
                    'index' => 'qtdConferida',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Conferida Transbordo',
                    'index' => 'qtdConferidaTransbordo',
                ))
                ->addColumn(array(
                    'label' => 'Inicio',
                    'index' => 'dataInicial',
                    'render' => 'DataTime',
                ))
                ->addColumn(array(
                    'label' => 'Fim',
                    'index' => 'dataFinal',
                    'render' => 'DataTime',
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferencia',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferencia de Transbordo',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia-transbordo',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->setShowExport(false);
        } else {
            $resultSegundaConf = $osRepo->getOsByExpedicaoReconferencia($idExpedicao);

            $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($resultSegundaConf));
            $this->setSource(new \Core\Grid\Source\Doctrine($resultSegundaConf))
                ->setId('expedicao-os-grid-reconferencia')
                ->setAttrib('caption', 'Ordens de Serviço - 2ª Conferência')
                ->setAttrib('class', 'grid-expedicao-os')
                ->addColumn(array(
                    'label' => 'OS',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Responsavel',
                    'index' => 'pessoa',
                ))
                ->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'atividade',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Conferida',
                    'index' => 'qtdConferida',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Conferida Transbordo',
                    'index' => 'qtdConferidaTransbordo',
                ))
                ->addColumn(array(
                    'label' => 'Inicio',
                    'index' => 'dataInicial',
                    'render' => 'DataTime',
                ))
                ->addColumn(array(
                    'label' => 'Fim',
                    'index' => 'dataFinal',
                    'render' => 'DataTime',
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferencia',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferencia de Transbordo',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia-transbordo',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'id'
                ))
                ->setShowExport(false)
            ;
        }

        return $this;
    }

}

