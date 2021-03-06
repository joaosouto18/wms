<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class Carregamento extends Grid
{
    public function init(array $params = array())
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expRepo */
        $expRepo = $this->getEntityManager()->getRepository('wms:Expedicao');

        $result = $expRepo->getCarregamentoByExpedicao($params['codExpedicao']);
        $this->setAttrib('title','Carregamento');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->addColumn(array(
                    'label' => 'Seq.',
                    'index' => 'codCliente',
                    'render' => 'Input'
                ))
                ->addColumn(array(
                    'label' => 'Cod. Cliente',
                    'index' => 'codCliente',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'cliente',
                ))
                ->addColumn(array(
                    'label' => 'Itens',
                    'index' => 'itens',
                ))
                ->addColumn(array(
                    'label' => 'Quantidade',
                    'index' => 'quantidade',
                ))
                ->addColumn(array(
                    'label' => 'Itinerario',
                    'index' => 'itinerario',
                ))
                ->addColumn(array(
                    'label' => 'Cidade',
                    'index' => 'cidade',
                ))
                ->addColumn(array(
                    'label' => 'Bairro',
                    'index' => 'bairro',
                ))
                ->addColumn(array(
                    'label' => 'Rua',
                    'index' => 'rua',
                ))
                ;

        $this->setShowExport(false)
            ->setButtonForm('Sequenciar');

        return $this;
    }

}

