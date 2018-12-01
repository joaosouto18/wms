<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 11:00
 */

namespace Wms\Domain\Entity;


use Wms\Domain\Entity\Inventario;
use Wms\Domain\EntityRepository;

class InventarioNovoRepository extends EntityRepository
{
    /**
     * @return InventarioNovo
     * @throws \Exception
     */
    public function save($params) {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventario = new InventarioNovo();

            $statusEntity = $em->getReference('wms:Util\Sigla', Inventario::STATUS_GERADO);
            $enInventario->setStatus($statusEntity);
            $enInventario->setInicio(new \DateTime);
            $enInventario->setDescricao($params['descricao']);

            $em->persist($enInventario);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventario;
    }

    public function getEndProdInventario($params)
    {
        extract($params);

        $query = $this->_em->createQueryBuilder()
            ->select("de.id,
                de.descricao as dscEndereco, 
                c.descricao as caracEnd,
                p.id as codProduto,
                p.grade,
                p.descricao as dscProduto ")
            ->from('wms:Deposito\Endereco', 'de')
            ->innerJoin('de.caracteristica', 'c')
            ->leftJoin('wms:Enderecamento\Estoque', 'e', 'WITH', 'e.depositoEndereco = de')
            ->leftJoin('e.produto', 'p')
            ->leftJoin('p.fabricante', 'f')
            ->leftJoin('p.classe', 'cl')
            ->leftJoin('p.linhaSeparacao', 'ls')
        ;

        if (!empty($ruaInicial) || !empty($ruaFinal)) {
            $condition = [];
            if (!empty($ruaInicial)) {
                $condition[] = "de.rua >= $ruaInicial";
            }
            if (!empty($ruaFinal)) {
                $condition[] = "de.rua <= $ruaFinal";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($predioInicial) || !empty($predioFinal)) {
            $condition = [];
            if (!empty($predioInicial)) {
                $condition[] = "de.predio >= $predioInicial";
            }
            if (!empty($predioFinal)) {
                $condition[] = "de.predio <= $predioFinal";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($nivelInicial) || !empty($nivelFinal)) {
            $condition = [];
            if (!empty($nivelInicial)) {
                $condition[] = "de.nivel >= $nivelInicial";
            }
            if (!empty($nivelFinal)) {
                $condition[] = "de.nivel <= $nivelFinal";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($aptoInicial) || !empty($aptoFinal)) {
            $condition = [];
            if (!empty($aptoInicial)) {
                $condition[] = "de.apartamento >= $aptoInicial";
            }
            if (!empty($aptoFinal)) {
                $condition[] = "de.apartamento <= $aptoFinal";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($lado)) {
            if ($lado == "P")
                $query->andWhere("MOD(de.predio,2) = 0");
            if ($lado == "I")
                $query->andWhere("MOD(de.predio,2) = 1");
        }

        if (!empty($situacao))
            $query->andWhere("de.situacao = :situacao")
                ->setParameter('situacao', $situacao);

        if (!empty($status))
            $query->andWhere("de.status = :status")
                ->setParameter('status', $status);

        if (!empty($idCarac))
            $query->andWhere("de.idCaracteristica = ?1")
                ->setParameter(1, $idCarac);

        if (!empty($estrutArmaz))
            $query->andWhere("de.idEstruturaArmazenagem = ?2")
                ->setParameter(2, $estrutArmaz);

        if (!empty($areaArmaz))
            $query->andWhere("de.idAreaArmazenagem = ?3")
                ->setParameter(3, $areaArmaz);

        if (!empty($tipoEnd))
            $query->andWhere("de.idTipoEndereco = ?4")
                ->setParameter(4, $tipoEnd);

        if (!empty($ativo))
            $query->andWhere("de.ativo = ?5")
                ->setParameter(5, $ativo);

        if (!empty($fabricante))
            $query->andWhere("f.id = ?6")
                ->setParameter(6, $fabricante);

        if (!empty($descricao))
            $query->andWhere("p.descricao like '%?7%'")
                ->setParameter(7, $descricao);

        if (!empty($codProduto))
            $query->andWhere("p.id = '?8'")
                ->setParameter(8, $codProduto);

        if (!empty($grade))
            $query->andWhere("p.grade = '?9'")
                ->setParameter(9, $grade);

        if (!empty($classe))
            $query->andWhere("cl.id = ?10")
                ->setParameter(10, $classe);

        if (!empty($linhaSep))
            $query->andWhere("ls.id = ?11")
                ->setParameter(11, $linhaSep);

        $query->orderBy('de.rua, de.predio, de.nivel, de.apartamento');

        return $query->getQuery()->getResult();
    }

}