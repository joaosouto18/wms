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

    public function getEnderecosCriarNovoInventario($params)
    {
        $query = $this->_em->createQueryBuilder()
            ->select("
                de.id,
                de.descricao as dscEndereco, 
                c.descricao as caracEnd,
                aa.descricao as dscArea,
                ea.descricao as dscEstrutura,
                de.rua, de.predio, de.nivel, de.apartamento")
            ->from('wms:Deposito\Endereco', 'de')
            ->innerJoin('de.caracteristica', 'c')
            ->innerJoin('de.estruturaArmazenagem', 'ea')
            ->innerJoin('de.areaArmazenagem', 'aa')
        ;

        $query->distinct(true);

        if (!empty($params['ruaInicial']) || !empty($params['ruaFinal'])) {
            $condition = [];
            if (!empty($params['ruaInicial'])) {
                $condition[] = "de.rua >= $params[ruaInicial]";
            }
            if (!empty($params['ruaFinal'])) {
                $condition[] = "de.rua <= $params[ruaFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['predioInicial']) || !empty($params['predioFinal'])) {
            $condition = [];
            if (!empty($params['predioInicial'])) {
                $condition[] = "de.predio >= $params[predioInicial]";
            }
            if (!empty($params['predioFinal'])) {
                $condition[] = "de.predio <= $params[predioFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['nivelInicial']) || !empty($params['nivelFinal'])) {
            $condition = [];
            if (!empty($params['nivelInicial'])) {
                $condition[] = "de.nivel >= $params[nivelInicial]";
            }
            if (!empty($params['nivelFinal'])) {
                $condition[] = "de.nivel <= $params[nivelFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['aptoInicial']) || !empty($params['aptoFinal'])) {
            $condition = [];
            if (!empty($params['aptoInicial'])) {
                $condition[] = "de.apartamento >= $params[aptoInicial]";
            }
            if (!empty($params['aptoFinal'])) {
                $condition[] = "de.apartamento <= $params[aptoFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['lado'])) {
            if ($params['lado'] == "P")
                $query->andWhere("MOD(de.predio,2) = 0");
            if ($params['lado'] == "I")
                $query->andWhere("MOD(de.predio,2) = 1");
        }

        if (!empty($params['situacao']))
            $query->andWhere("de.situacao = :situacao")
                ->setParameter('situacao', $params['situacao']);

        if (!empty($params['status']))
            $query->andWhere("de.status = :status")
                ->setParameter('status', $params['status']);

        if (!empty($params['idCarac']))
            $query->andWhere("de.idCaracteristica = ?1")
                ->setParameter(1, $params['idCarac']);

        if (!empty($params['estrutArmaz']))
            $query->andWhere("de.idEstruturaArmazenagem = ?2")
                ->setParameter(2, $params['estrutArmaz']);

        if (!empty($params['areaArmaz']))
            $query->andWhere("de.idAreaArmazenagem = ?3")
                ->setParameter(3, $params['areaArmaz']);

        if (!empty($params['tipoEnd']))
            $query->andWhere("de.idTipoEndereco = ?4")
                ->setParameter(4, $params['tipoEnd']);

        if (!empty($params['ativo']))
            $query->andWhere("de.ativo = ?5")
                ->setParameter(5, $params['ativo']);

        $query->orderBy('de.rua, de.predio, de.nivel, de.apartamento');

        return $query->getQuery()->getResult();
    }

    public function getProdutosCriarNovoInventario($params)
    {
        $query = $this->_em->createQueryBuilder()
            ->select("
                de.id,
                de.descricao as dscEndereco, 
                c.descricao as caracEnd,
                p.id as codProduto,
                p.grade,
                p.descricao as dscProduto")
            ->from('wms:Enderecamento\Estoque', 'e')
            ->innerJoin('e.depositoEndereco', 'de')
            ->innerJoin('e.produto', 'p')
            ->innerJoin('de.caracteristica', 'c')
        ;

        $query->distinct(true);

        if (!empty($params['fabricante']))
            $query->andWhere("f.id = ?6")
                ->setParameter(6, $params['fabricante']);

        if (!empty($params['descricao']))
            $query->andWhere("p.descricao like ?7")
                ->setParameter(7, "%$params[descricao]%");

        if (!empty($params['codProduto']))
            $query->andWhere("p.id = ?8")
                ->setParameter(8, $params['codProduto']);

        if (!empty($params['grade']))
            $query->andWhere("p.grade = ?9")
                ->setParameter(9, $params['grade']);

        if (!empty($params['classe']))
            $query->andWhere("cl.id = ?10")
                ->setParameter(10, $params['classe']);

        if (!empty($params['linhaSep']))
            $query->andWhere("ls.id = ?11")
                ->setParameter(11, $params['linhaSep']);

        $query->orderBy('p.id, p.descricao, p.grade, de.rua, de.predio, de.nivel, de.apartamento');

        return $query->getQuery()->getResult();
    }

}
