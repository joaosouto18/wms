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
    public function init ($idExpedicao, $verificaReconferencia)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $osRepo */
        $osRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');

        if ($verificaReconferencia == 'S') {
            $result = $osRepo->getOsByExpedicaoReconferencia($idExpedicao);

            $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-os-grid')
                ->setAttrib('caption', 'Ordens de Serviço')
                ->setAttrib('class', 'grid-expedicao-os')
                ->addColumn(array(
                    'label' => 'OS',
                    'index' => 'OS',
                ))
                ->addColumn(array(
                    'label' => 'Responsavel',
                    'index' => 'PESSOA',
                ))
                ->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'DSC_ATIVIDADE',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Conferida',
                    'index' => 'QTDCONFERIDA',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Segunda Conferência',
                    'index' => 'QTDSEGUNDACONFERENCIA',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Conferida Transbordo',
                    'index' => 'QTDCONFERIDATRANSBORDO',
                ))
                ->addColumn(array(
                    'label' => 'Inicio',
                    'index' => 'DATAINICIAL',
                ))
                ->addColumn(array(
                    'label' => 'Fim',
                    'index' => 'DATAFINAL',
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferencia',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'OS'
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferencia de Transbordo',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia-transbordo',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'OS'
                ))
                ->setShowExport(false);
        } else {
            $result = $osRepo->getOsByExpedicao($idExpedicao);

            $this->setSource(new \Core\Grid\Source\Doctrine($result))
                ->setId('expedicao-os-grid')
                ->setAttrib('caption', 'Ordens de Serviço')
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
        }

        return $this;
    }

}

