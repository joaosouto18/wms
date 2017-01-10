<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Deposito\Endereco;

class VSaldoCompletoRepository extends EntityRepository
{

    public function saldo($params)
    {
        $tipoPicking = Endereco::ENDERECO_PICKING;
        $query = $this->getEntityManager()->createQueryBuilder()
        ->select('s.codProduto, s.grade,s.dscLinhaSeparacao, s.qtd, p.descricao, s.dscEndereco, MOD(e.predio,2) as lado, e.id as idEndereco, s.codUnitizador, s.unitizador, s.volume, tp.descricao as tipoComercializacao, MAX(pe.quantidade) quantidade')
        ->from("wms:Enderecamento\VSaldoCompleto","s")
        ->leftJoin("s.produto","p")
        ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'p.id = pe.codProduto AND p.grade = pe.grade')
        ->leftJoin('p.tipoComercializacao','tp')
        ->leftJoin("s.depositoEndereco", "e")
        ->leftJoin("wms:Armazenagem\Unitizador","u","WITH","u.id=s.codUnitizador")
        ->groupBy('s.codProduto, s.grade, s.dscLinhaSeparacao, s.qtd, p.descricao, s.dscEndereco, e.predio, e.id, s.codUnitizador, s.unitizador, s.volume, tp.descricao, e.rua, e.predio, e.nivel, e.apartamento, s.codProduto, s.grade, s.volume')
            ->orderBy("e.rua, e.predio, lado, e.nivel, e.apartamento, s.codProduto, s.grade, s.volume");

        $query->andWhere('e.ativo <> \'N\' ');

        if (!empty($params['grandeza'])) {
            $grandeza = $params['grandeza'];
            $grandeza = implode(',',$grandeza);
            $query->andWhere("s.codLinhaSeparacao in ($grandeza)");
        }

        if (!empty($params['inicioRua'])) {
            $query->andWhere('e.rua >= :inicioRua');
            $query->setParameter('inicioRua',$params['inicioRua']);
        }

        if (!empty($params['fimRua'])) {
            $query->andWhere('e.rua <= :fimRua');
            $query->setParameter('fimRua',$params['fimRua']);
        }

        if (($params['pulmao'] == 1) && ($params['picking'] == 0)) {
            $query->andWhere("e.idCaracteristica != $tipoPicking");
        }

        if (($params['pulmao'] == 0) && ($params['picking'] == 1)) {
            $query->andWhere("e.idCaracteristica = '$tipoPicking'");
        }

		$result = $query->getQuery()->getResult();
        return $result;
    }

}