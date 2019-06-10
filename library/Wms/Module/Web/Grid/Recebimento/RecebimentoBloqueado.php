<?php

namespace Wms\Module\Web\Grid\Recebimento;

use Wms\Domain\Entity\Usuario;
use Wms\Module\Web\Grid;

class RecebimentoBloqueado extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param Usuario $user
     * @return Grid
     */
    public function init($user = null)
    {
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepository */
        $recebimentoRepository = $this->getEntityManager()->getRepository('wms:Recebimento');
        $result = $recebimentoRepository->getQuantidadeConferidaBloqueada();

        $percent = 0;
        if ($recebimentoRepository->getSystemParameterValue("HABILITA_PERC_RECEB") == "S") {
            $percentUser = $user->getPercentReceb();
            $percent = (!empty($percentUser)) ? $percentUser : 0;
            if (empty($percent)) {
                $percentPerfil = $user->getMaxPercentRecebPerfis();
                $percent = (!empty($percentPerfil)) ? $percentPerfil : 0;
            }
        }

        $this->setAttrib('title','Recebimento Bloqueado');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
            ->setId('recebimento-bloqueado-grid')
            ->setAttrib('caption', 'Recebimento Bloqueado')
            ->addColumn(array(
                'label' => 'Código do Recebimento',
                'index' => 'codRecebimento'
            ))
            ->addColumn(array(
                'label' => 'Código do Produto',
                'index' => 'codProduto',
            ))
            ->addColumn(array(
                'label' => 'Descrição',
                'index' => 'descricao'
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade'
            ))
            ->addColumn(array(
                'label' => 'Data Digitada',
                'index' => 'dataValidade'
            ))
            ->addColumn(array(
                'label' => 'Qtd. Bloqueada',
                'index' => 'qtdBloqueada'
            ))
            ->addColumn(array(
                'label' => 'Min. Dias Vida Util',
                'index' => 'diasVidaUtil'
            ))
            ->addColumn(array(
                'label' => 'Dias Vida Util Conf',
                'index' => 'diasValidos'
            ))
            ->addColumn(array(
                'label' => 'Percentual Vida Util',
                'index' => 'percentualVidaUtil'
            ))
            ->addAction(array(
                'label' => 'ACEITAR data de validade',
                'controllerName' => 'recebimento',
                'actionName' => 'liberar-recusar-recebimentos-ajax',
                'pkIndex' => array(
                    'codRecebEmbalagem',
                    'codRecebVolume',
                    'codRecebimento',
                    'codProduto',
                    'grade',
                    'dataValidade',
                    'diasVidaUtil',
                    'qtdBloqueada'
                ),
                'params' => array(
                    'liberar' => true,
                    'observacao' => 'Contagem Liberada com Sucesso'
                ),
                'condition' => function ($item) use ($percent){
                    return ($item["percentualVidaUtil"] >= $percent);
                }
            ))
            ->addAction(array(
                'label' => 'RECUSAR data de validade',
                'controllerName' => 'recebimento',
                'actionName' => 'liberar-recusar-recebimentos-ajax',
                'pkIndex' => array(
                    'codRecebEmbalagem',
                    'codRecebVolume',
                    'codRecebimento',
                    'codProduto',
                    'grade',
                    'dataValidade',
                    'diasVidaUtil',
                    'qtdBloqueada'
                ),
                'params' => array(
                    'liberar' => false,
                    'observacao' => 'Contagem Rejeitada'
                ),
                'condition' => function ($item) use ($percent){
                    return ($item["percentualVidaUtil"] >= $percent);
                }
            ))
            ->addAction(array(
                'label' => 'Acesso Negado',
                'title' => 'Percentual de vida util abaixo do permitido à este usuário',
                'cssClass' => "link-blocked",
                'condition' => function ($item) use ($percent){
                    return ($item["percentualVidaUtil"] < $percent);
                }
            ))

            ->setShowExport(false);

        return $this;
    }

}
