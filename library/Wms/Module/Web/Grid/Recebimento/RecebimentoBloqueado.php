<?php

namespace Wms\Module\Web\Grid\Recebimento;

class RecebimentoBloqueado extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param array $params 
     */
    public function init()
    {
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepository */
        $recebimentoRepository = $this->getEntityManager()->getRepository('wms:Recebimento');
        $result = $recebimentoRepository->getQuantidadeConferidaBloqueada();

//        var_dump($result); exit;

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
            ->addAction(array(
                'label' => 'ACEITAR data de validade',
                'controllerName' => 'recebimento',
                'actionName' => 'index',
                'params' => array('liberar' => 'true'),
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'RECUSAR data de validade',
                'controllerName' => 'recebimento',
                'actionName' => 'index',
                'params' => array('liberar' => 'false'),
                'pkIndex' => 'id'
            ))

            ->setShowExport(false);

        return $this;
    }

}
