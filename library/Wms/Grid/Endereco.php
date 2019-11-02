<?php

namespace Wms\Grid;

use Wms\Module\Web\Grid;

class Endereco extends Grid
{

    public function init(array $params = array())
    {
        extract($params);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $this->setAttrib('title','Endereços');
        $source = $qb
            ->select("e, c.descricao as dscCaracteristica, a.descricao areaArmazenagem, ea.descricao estruturaArmazenagem, te.descricao as dscTipoEndereco, 
            CASE WHEN e.bloqueadaEntrada = 1 and e.bloqueadaSaida = 1 THEN 'Entrada/Saída' 
                                           WHEN e.bloqueadaEntrada = 1 and e.bloqueadaSaida = 0 THEN 'Entrada' 
                                           WHEN e.bloqueadaEntrada = 0 and e.bloqueadaSaida = 1 THEN 'Saída' 
                                           ELSE 'Disponivel' END bloqueada")
            ->from('wms:Deposito\Endereco', 'e')
            ->innerJoin('e.caracteristica', 'c')
            ->innerJoin('e.areaArmazenagem', 'a')
            ->innerJoin('e.estruturaArmazenagem', 'ea')
            ->innerJoin('e.tipoEndereco', 'te')
            ->orderBy('e.descricao');

        if ((!is_null($inicialRua) && $inicialRua != '') && (!is_null($finalRua) && $finalRua != '')) {
            $source->andWhere("e.rua BETWEEN :inicilaRua AND :finalRua")
                ->setParameter('inicilaRua', $inicialRua)
                ->setParameter('finalRua', $finalRua);
        }

        if ((!is_null($inicialPredio) && $inicialPredio != '') && (!is_null($finalPredio) && $finalPredio != '')) {
            $source->andWhere("e.predio BETWEEN :inicilaPredio AND :finalPredio")
                ->setParameter('inicilaPredio', $inicialPredio)
                ->setParameter('finalPredio', $finalPredio);
        }

        if ((!is_null($inicialNivel) && $inicialNivel != '') && (!is_null($finalNivel) && $inicialNivel != '')) {
            $source->andWhere("e.nivel BETWEEN :inicilaNivel AND :finalNivel")
                ->setParameter('inicilaNivel', $inicialNivel)
                ->setParameter('finalNivel', $finalNivel);
        }

        if ((!is_null($inicialApartamento) && $inicialApartamento != '') && (!is_null($finalApartamento) && $finalApartamento != '')) {
            $source->andWhere("e.apartamento BETWEEN :inicilaApartamento AND :finalApartamento")
                ->setParameter('inicilaApartamento', $inicialApartamento)
                ->setParameter('finalApartamento', $finalApartamento);
        }
        if ($bloqueadaEntrada === "0")
            $source->andWhere("e.bloqueadaEntrada = 0");
        if ($bloqueadaEntrada === "1")
            $source->andWhere("e.bloqueadaEntrada = 1");
        if ($bloqueadaSaida === "0")
            $source->andWhere("e.bloqueadaSaida = 0");
        if ($bloqueadaSaida === "1")
            $source->andWhere("e.bloqueadaSaida = 1");

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
                'label' => 'End. Bloqueado',
                'index' => 'bloqueada',
            ))
            ->addColumn(array(
                'label' => 'Disponibilidade',
                'index' => 'ativo',
                'render' => 'AtivoOrInativo'
            ));


        return $this;
    }

}
