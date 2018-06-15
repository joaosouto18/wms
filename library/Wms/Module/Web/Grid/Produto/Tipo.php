<?php

namespace Wms\Module\Web\Grid\Produto;

/**
 * Description of Tipo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Tipo extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {

        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('t')
                ->from('wms:Produto\Tipo', 't')
                ->orderBy('t.descricao');

        $this->setSource(new \Core\Grid\Source\Doctrine($source))
                ->setId('tipo-grid')
                ->addColumn(array(
                    'label' => 'Código',
                    'index' => 'id',
                    'filter' => array(
                        'render' => array(
                            'type' => 'number',
                            'range' => true,
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'actionName' => 'delete',
                    'pkIndex' => 'id',
                    'cssClass' => 'del'
                ))
                ->setHasOrdering(true);

        return $this;
    }

}
