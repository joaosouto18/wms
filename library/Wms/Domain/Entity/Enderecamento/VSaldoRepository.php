<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class VSaldoRepository extends EntityRepository
{

    public function saldo($params)
    {
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();
        $tipoPickingRotativo = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING_ROTATIVO'))->getValor();

        $query = $this->getEntityManager()->createQueryBuilder()
        ->select('s.codProduto, s.grade,s.dscLinhaSeparacao, s.qtd, p.descricao, s.dscEndereco, MOD(e.predio,2) as lado, e.id as idEndereco, s.codUnitizador, s.unitizador, s.volume, tp.descricao as tipoComercializacao')
        ->from("wms:Enderecamento\VSaldo","s")
        ->leftJoin("s.produto","p")
        ->leftJoin('p.tipoComercializacao','tp')
        ->leftJoin("s.depositoEndereco", "e")
        ->orderBy("e.rua, e.predio, lado, e.nivel, e.apartamento, s.codProduto, s.grade, s.volume");

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
            $query->andWhere("e.idCaracteristica NOT IN ($tipoPicking, $tipoPickingRotativo)");
        }
		
        if (($params['pulmao'] == 0) && ($params['picking'] == 1)) {
            $query->andWhere("e.idCaracteristica IN ($tipoPicking, $tipoPickingRotativo)");
        }

		$result = $query->getQuery()->getResult();
		return $result;
    }

}