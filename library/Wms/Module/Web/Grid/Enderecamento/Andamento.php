<?php

namespace Wms\Module\Web\Grid\Enderecamento;
          

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class Andamento extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init ($idRecebimento, $codProduto, $grade)
    {
 
        /** @var \Wms\Domain\Entity\Enderecamento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Andamento');
        $result = $andamentoRepo->getAndamento($idRecebimento, $codProduto, $grade);
        $this->setAttrib('title','Andamento Enderecamento');
        $this->setSource(new \Core\Grid\Source\Doctrine($result))
                ->setId('enderecamento-andamento-grid')
                ->setAttrib('caption', 'Andamento do endereçamento')
                ->setAttrib('class', 'grid-andamento')
                ->addColumn(array(
                    'label'  => 'Data',
                    'index'  => 'dataAndamento',
                    'render' => 'DataTime'
                ))
                ->addColumn(array(
                    'label' => 'Usuário',
                    'index' => 'nome',
                ))
                ->addColumn(array(
                    'label' => 'Andamento',
                    'index' => 'dscObservacao',
                ))
                ->setShowExport(false);

        return $this;
    }

}

