<?php

namespace Wms\Module\Web\Grid\Recebimento;

/**
 * Description of Tipo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Conferencia extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {
        extract($params);

        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('c, p.id idProduto, p.grade, p.descricao, md.descricao as motivoDivergencia')
                ->from('wms:Recebimento\Conferencia', 'c')
                ->join('c.produto', 'p')
                ->leftjoin('c.motivoDivergencia', 'md');

        if (isset($idOrdemServico))
            $source->where('c.ordemServico = :idOrdemServico')
                    ->setParameter('idOrdemServico', $idOrdemServico);

        $source->andWhere('p.grade = c.grade')
                ->orderBy('c.id');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $this->setSource(new \Core\Grid\Source\Doctrine($source))
                ->setId('recebimento-conferencia-grid')
                ->setAttrib('caption', 'Confer&ecirc;ncia dos Produtos')
                ->addColumn(array(
                    'label' => 'Código do Produto',
                    'index' => 'idProduto',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
                ))
                ->addColumn(array(
                    'label' => 'Data da Conferencia',
                    'index' => 'dataConferencia',
                    'render' => 'DataTime',
                ))
                ->addColumn(array(
                    'label' => 'Quantidade Conferida',
                    'index' => 'qtdConferida',
                ))
                ->addColumn(array(
                    'label' => 'Quantidade Divergencia',
                    'index' => 'qtdDivergencia',
                ))
                ->addColumn(array(
                    'label' => 'Obs',
                    'index' => 'motivoDivergencia',
                ));

        return $this;
    }

}
