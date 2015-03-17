<?php

namespace Wms\Grid;

use Wms\Module\Web\Grid;

class Endereco extends Grid
{

    public function init(array $params = array())
    {
        extract($params['identificacao']);

        $qb = $this->getEntityManager()->createQueryBuilder();

        $source = $qb
            ->select("e, c.descricao as dscCaracteristica, a.descricao areaArmazenagem, ea.descricao estruturaArmazenagem, te.descricao as dscTipoEndereco")
            ->from('wms:Deposito\Endereco', 'e')
            ->innerJoin('e.caracteristica', 'c')
            ->innerJoin('e.areaArmazenagem', 'a')
            ->innerJoin('e.estruturaArmazenagem', 'ea')
            ->innerJoin('e.tipoEndereco', 'te')
            ->orderBy('e.descricao');

        if (!empty($inicialRua) && !empty($finalRua)) {
            $source->andWhere("e.rua BETWEEN :inicilaRua AND :finalRua")
                ->setParameter('inicilaRua', $inicialRua)
                ->setParameter('finalRua', $finalRua);
        }

        if (!empty($inicialPredio) && !empty($finalPredio)) {
            $source->andWhere("e.predio BETWEEN :inicilaPredio AND :finalPredio")
                ->setParameter('inicilaPredio', $inicialPredio)
                ->setParameter('finalPredio', $finalPredio);
        }

        if (!empty($inicialNivel) && !empty($finalNivel)) {
            $source->andWhere("e.nivel BETWEEN :inicilaNivel AND :finalNivel")
                ->setParameter('inicilaNivel', $inicialNivel)
                ->setParameter('finalNivel', $finalNivel);
        }

        if (!empty($inicialApartamento) && !empty($finalApartamento)) {
            $source->andWhere("e.apartamento BETWEEN :inicilaApartamento AND :finalApartamento")
                ->setParameter('inicilaApartamento', $inicialApartamento)
                ->setParameter('finalApartamento', $finalApartamento);
        }

        if (!empty($lado)) {
            if ($lado == "P")
                $source->andWhere("MOD(e.predio,2) = 0");
            if ($lado == "I")
                $source->andWhere("MOD(e.predio,2) = 1");
        }
        if (!empty($situacao))
            $source->andWhere("e.situacao = :situacao")
                ->setParameter('situacao', $situacao);
        if (!empty($status))
            $source->andWhere("e.status = :status")
                ->setParameter('status', $status);
        if (!empty($idCaracteristica))
            $source->andWhere("e.idCaracteristica = ?1")
                ->setParameter(1, $idCaracteristica);
        if (!empty($idEstruturaArmazenagem))
            $source->andWhere("e.idEstruturaArmazenagem = ?2")
                ->setParameter(2, $idEstruturaArmazenagem);
        if (!empty($idTipoEndereco))
            $source->andWhere("e.idTipoEndereco = ?4")
                ->setParameter(4, $idTipoEndereco);
        if (!empty($idAreaArmazenagem))
            $source->andWhere("e.idAreaArmazenagem = ?3")
                ->setParameter(3, $idAreaArmazenagem);
        if (!empty($ativo))
            $source->andWhere("e.ativo = ?5")
                ->setParameter(5, $ativo);

        $this->setSource(new \Core\Grid\Source\Doctrine($source));
        $this->addMassAction('mass-select', 'Selecionar')
            ->setShowExport(false);

        $this->addColumn(array(
            'label' => 'Endereço',
            'index' => 'descricao'
        ))
            ->addColumn(array(
                'label' => 'Área de Armazenagem',
                'index' => 'areaArmazenagem'
            ))
            ->addColumn(array(
                'label' => 'Característica',
                'index' => 'dscCaracteristica'
            ))
            ->addColumn(array(
                'label' => 'Estrutura Armazenagem',
                'index' => 'estruturaArmazenagem'
            ))
            ->addColumn(array(
                'label' => 'Tipo Endereço',
                'index' => 'dscTipoEndereco'
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'status',
                'render' => 'OcupadoOrDisponivel'
            ))
            ->addColumn(array(
                'label' => 'Situação',
                'index' => 'situacao',
                'render' => 'BloqueadoOrDesbloqueado'
            ))
            ->addColumn(array(
                'label' => 'Disponibilidade',
                'index' => 'ativo',
                'render' => 'AtivoOrInativo'
            ));


        return $this;
    }

}
