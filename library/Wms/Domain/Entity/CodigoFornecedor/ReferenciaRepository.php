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
        $teste = 'CONCAT(p.nome, CONCAT(" - (",CONCAT(p.cnpj,")")))';
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('f.id, CONCAT(p.nome, CONCAT(\' - (\',CONCAT(p.cnpj,\')\')))as label')
            ->from('wms:Pessoa\Papel\Fornecedor', 'f')
            ->innerJoin('f.pessoa', 'p')
            ->where("UPPER(p.nome) like UPPER('%$term%')");

        $result = $dql->getQuery()->getResult(\PDO::FETCH_ASSOC);
        return $result;
    }
}
