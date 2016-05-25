<?php

namespace Wms\Domain\Entity\CodigoFornecedor;

use Doctrine\ORM\EntityRepository;

class ReferenciaRepository extends EntityRepository
{
    public function buscarFornecedorByNome($term)
    {
        if ($term == null or empty($term)) {
            throw new \Exception('Termo obrigatório');
        }

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('f.id, p.nome as label')
            ->from('wms:Pessoa\Papel\Fornecedor', 'f')
            ->innerJoin('f.pessoa', 'p')
            ->where("UPPER(p.nome) like UPPER('%$term%')");

        return $dql->getQuery()->getResult(\PDO::FETCH_ASSOC);
    }
}
