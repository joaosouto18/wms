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
        $nome = $params['nomeCliente'];
        $praca = $params['praca'];
        $cidade = $params['cidade'];
        $bairro = $params['bairro'];
        $estado = $params['estado'];

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("p.nome, c.id, e.localidade as cidade, e.bairro, s.referencia as estado, pc.id as praca")
            ->from("wms:Pessoa\Papel\Cliente", "c")
            ->leftJoin("wms:Pessoa", 'p' , 'WITH', 'c.pessoa = p.id')
            ->leftJoin("wms:Pessoa\Endereco", 'e', 'WITH', 'p.id = e.pessoa')
            ->leftJoin("wms:Util\Sigla", 's', 'WITH', 'e.uf = s.id')
            ->leftJoin("wms:MapaSeparacao\Praca", 'pc', 'WITH', 'c.praca = pc.id');

        if ($codCliente != null) {
            $source->andWhere("c.id = $codCliente");
        }

        if ($nome != null) {
            $source->andWhere("p.nome = :nome")
                ->setParameter('nome', '%' . $nome . '%');
        }

        if ($cidade != null) {
            $source->andWhere("e.localidade = :cidade")
                ->setParameter('cidade', '%' . $cidade . '%');
        }

        if ($bairro != null) {
            $source->andWhere("e.bairro = :bairro")
                ->setParameter('bairro', '%' . $bairro . '%');
        }

        if ($praca != null) {
            $source->andWhere("pc.id = $praca");
        }

        $result =  $source->getQuery()->getResult();

        return $result;
    }

    public function atualizarPracaPorCliente($clientes)
    {
        foreach($clientes as $cliente)
        {
            $entity = $this->find($cliente);

            $entity->setPraca($cliente['praca']);
            $this->_em->persist($entity);
        }
        try {
            $this->_em->flush();

            return "Praça atualizada com sucesso!";
        } catch(Exception $e) {
            throw new $e->getMessage();
        }
    }
}