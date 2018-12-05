<?php

namespace Wms\Module\Web\Grid\Produto;

/**
 * Description of Classe
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class Classe extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {

        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('c , p.nome as nomePai')
                ->from('wms:Produto\Classe', 'c')
                ->leftJoin('c.pai', 'p')
                ->orderBy('c.nome');

        $this->setSource(new \Core\Grid\Source\Doctrine($source))
                ->setId('classe-grid')
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
                    'label' => 'Classe do Produto',
                    'index' => 'nome',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Código Pai',
                    'index' => 'idPai',
                    'filter' => array(
                        'render' => array(
                            'type' => 'number',
                            'range' => true,
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Nome do Pai',
                    'index' => 'nomePai',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->setHasOrdering(true);

        return $this;
    }

}
