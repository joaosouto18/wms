<?php

namespace Wms\Module\Inventario\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Module\Web\Grid\Produto\DadoLogistico,
    Core\Util\Produto as UtilProduto;

class Produto extends DadoLogistico
{

    public function init(array $params = array())
    {
        extract($params);

        $tipoPicking = Endereco::ENDERECO_PICKING;

        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $source = $em->createQueryBuilder()

            ->select("CONCAT(CONCAT(CONCAT(e.id, '%#%'), CONCAT(s.codProduto,'%#%')), s.grade) as id,
                      s.codProduto,
                      s.grade,
                      p.descricao, e.descricao descricaoEnd")
            ->from("wms:Enderecamento\VSaldoCompleto","s")
            ->leftJoin("s.produto","p")
            ->leftJoin("s.depositoEndereco", "e")
            ->leftJoin("wms:Armazenagem\Unitizador","u","WITH","u.id=s.codUnitizador")
            ->distinct(true);

        if (!empty($params['idLinhaSeparacao'])) {
            $grandeza = $params['idLinhaSeparacao'];
            $grandeza = implode(',',$grandeza);
            $source->andWhere("s.codLinhaSeparacao in ($grandeza)");
        }

        if (!empty($descricao)) {
            $descricao = mb_strtoupper($descricao, 'UTF-8');
            $source->andWhere("p.descricao LIKE '{$descricao}%'");
        }
        if (!empty($grade)) {
            $grade = mb_strtoupper($grade, 'UTF-8');
            $source->andWhere("p.grade LIKE '{$grade}%'");
        }
        if (!empty($params['id'])) {
            $id = UtilProduto::preencheZerosEsquerda($params['id'], 2);
            $source->andWhere ("p.id = '" . $id . "'");
        }

        if (($params['pulmao'] == 1) && ($params['picking'] == 0)) {
            $source->andWhere("e.idCaracteristica != $tipoPicking");
        }

        if (($params['pulmao'] == 0) && ($params['picking'] == 1)) {
            $source->andWhere("e.idCaracteristica = '$tipoPicking'");
        }

        $this->setSource(new \Core\Grid\Source\Doctrine($source))
            ->setId('produtos-grid')
            ->setAttrib('caption', 'Produtos')
            ->addColumn(array(
                'label' => 'Código',
                'index' => 'codProduto'
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade'
            ))
            ->addColumn(array(
                'label' => 'Descrição',
                'index' => 'descricao'
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'descricaoEnd'
            ))

            ->setHasOrdering(false)
            ->setShowExport(false)
            ->addMassAction('mass-select', 'Selecionar');

        return $this;

    }

}
