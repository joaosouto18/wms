<?php

namespace Wms\Module\Web\Grid\Recebimento;

/**
 * Description of Tipo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Andamento extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {
        extract($params);
        
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('a, p.nome', 's.sigla as tipoAndamento')
                ->from('wms:Recebimento\Andamento', 'a')
                ->leftJoin('a.usuario', 'u')
                ->leftJoin('u.pessoa', 'p')
                ->leftJoin('a.tipoAndamento', 's')
                ->orderBy('a.dataAndamento', 'desc');

        if (isset($idRecebimento))
            $source->andWhere('a.recebimento = :idRecebimento')
                    ->setParameter('idRecebimento', $idRecebimento);

        $this->setSource(new \Core\Grid\Source\Doctrine($source))
                ->setId('recebimento-andamento-grid')
                ->setAttrib('caption', 'Histórico')
                ->addColumn(array(
                    'label' => 'Data do Andamento',
                    'index' => 'dataAndamento',
                    'render' => 'DataTime'
                ))
                ->addColumn(array(
                    'label' => 'Tipo do Andamento',
                    'index' => 'tipoAndamento'
                ))
                ->addColumn(array(
                    'label' => 'Usuário do Andamento',
                    'index' => 'nome'
                ))
                ->setShowExport(false);
        
        return $this;
    }

}
