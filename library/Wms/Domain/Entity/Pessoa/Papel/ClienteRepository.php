<?php
namespace Wms\Domain\Entity\Pessoa\Papel;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\AtorRepository;
use Wms\Domain\Entity\Ator;

class ClienteRepository extends AtorRepository
{
    public function getCliente($params)
    {
        $codCliente = (isset($params['codCliente']) ? $params['codCliente'] : null);
        $nome = (isset($params['nomeCliente']) ? $params['nomeCliente'] : null);
        $praca = (isset($params['praca']) ? $params['praca'] : null);
        $cidade = (isset($params['cidade']) ? $params['cidade'] : null);
        $bairro = (isset($params['bairro']) ? $params['bairro'] : null);
        $estado = (isset($params['estado']) ? $params['estado'] : null);

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("p.nome, c.id, e.localidade as cidade, e.bairro, s.referencia as estado, pc.id as praca, e.cep, e.pontoReferencia, e.descricao, e.numero, e.complemento, pc.nomePraca, pf.cpf, pj.cnpj")
            ->from("wms:Pessoa\Papel\Cliente", "c")
            ->leftJoin("wms:Pessoa", 'p' , 'WITH', 'c.pessoa = p.id')
            ->leftJoin("wms:Pessoa\Endereco", 'e', 'WITH', 'p.id = e.pessoa')
            ->leftJoin("wms:Util\Sigla", 's', 'WITH', 'e.uf = s.id')
            ->leftJoin("wms:MapaSeparacao\Praca", 'pc', 'WITH', 'c.praca = pc.id')
            ->leftJoin("wms:Pessoa\Fisica", 'pf', 'WITH', 'pf.id = p.id')
            ->leftJoin("wms:Pessoa\Juridica", 'pj', 'WITH', 'pj.id = p.id')
            ->setMaxResults(50);

        if ($codCliente != null) {
            $source->andWhere("c.id = $codCliente");
        }

        if ($nome != null) {
            $source->andWhere($source->expr()->like('p.nome', $source->expr()->literal('%' . $nome . '%')));
        }

        if ($cidade != null) {
            $source->andWhere($source->expr()->like('e.localidade', $source->expr()->literal('%' . $cidade . '%')));
        }

        if ($bairro != null) {
            $source->andWhere($source->expr()->like('e.bairro', $source->expr()->literal('%' . $bairro . '%')));
        }

        if ($praca != null) {
            $source->andWhere("pc.id = $praca");
        }

        if ($estado != null) {
            $source
                ->andWhere("s.referencia = :estado")
                ->setParameter('estado', $estado);
        }

        $result =  $source->getQuery()->getResult();

        return $result;
    }

    public function atualizarPracaPorCliente($clientes)
    {
        foreach($clientes as $cliente)
        {
            $entity = $this->find($cliente['id']);
            $praca = $this->_em->getRepository('wms:MapaSeparacao\Praca')->findOneBy(array('id' => $cliente['pracaId']));

            $entity->setPraca($praca);
            $this->_em->persist($entity);
        }
        try {
            $this->_em->flush();

            return "Dados atualizados com sucesso!";
        } catch(Exception $e) {
            throw new $e->getMessage();
        }
    }

    public function getDadosByCliente($idCliente)
    {

    }
}