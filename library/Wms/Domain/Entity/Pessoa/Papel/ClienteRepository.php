<?php
namespace Wms\Domain\Entity\Pessoa\Papel;

use Doctrine\ORM\EntityRepository,
   Wms\Domain\Entity\AtorRepository;
use Wms\Domain\Entity\Ator;

class ClienteRepository extends AtorRepository
{
    public function getCliente($params)
    {
        $codCliente = $params['codCliente'];
        $nome = $params['nome'];
        $praca = $params['praca'];
        $cidade = $params['cidade'];
        $bairro = $params['bairro'];
        $estado = $params['estado'];

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("p.nome, c.id as codCliente")
            ->from("wms:Pessoa\Papel\Cliente","c")
            ->innerJoin('wms:Pessoa', 'p' , 'WITH', 'c.pessoa = p.id');

        if ($codCliente != null) {
            $source->andWhere("c.id = $codCliente");
        }

        $result =  $source->getQuery()->getResult();

        return $result;
    }
}