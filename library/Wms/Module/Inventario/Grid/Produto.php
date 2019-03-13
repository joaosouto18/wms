<?php

namespace Wms\Module\Inventario\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Module\Web\Grid\Produto\DadoLogistico,
    Core\Util\Produto as UtilProduto;

class Produto extends DadoLogistico {

    public function init(array $params = array()) {
        extract($params);

        $tipoPicking = Endereco::PICKING;

        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $source = $em->createQueryBuilder()
                ->select("CONCAT(CONCAT(CONCAT(e.id, '%#%'), CONCAT(s.codProduto,'%#%')), s.grade) as id,
                      s.codProduto,
                      s.grade,
                      p.descricao, e.descricao descricaoEnd")
                ->from("wms:Enderecamento\VSaldoCompleto", "s")
                ->leftJoin("s.produto", "p")
                ->leftJoin("s.depositoEndereco", "e")
                ->leftJoin("wms:Armazenagem\Unitizador", "u", "WITH", "u.id=s.codUnitizador")
                ->distinct(true);

        if (!empty($params['idLinhaSeparacao'])) {
            $grandeza = $params['idLinhaSeparacao'];
            $grandeza = implode(',', $grandeza);
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
        
        if (!empty($grades)) {
            $grade = mb_strtoupper($grades, 'UTF-8');
            $source->andWhere('p.grade IN ('.str_replace('"', '', $grade).')');
        }
        if (!empty($params['incluirinput'])) {
            $id = UtilProduto::preencheZerosEsquerda($params['incluirinput'], 2);
            $source->andWhere("p.id IN (" . trim($id) . ")");
        }
        if (!empty($params['id']) && empty($params['incluirinput'])) {
            $id = UtilProduto::preencheZerosEsquerda($params['id'], 2);
            $id = trim($id);
            $source->andWhere("p.id IN (  '$id'  )");
        }
        
        if (($params['pulmao'] == 1) && ($params['picking'] == 0)) {
            $source->andWhere("e.idCaracteristica != $tipoPicking");
        }

        if (($params['pulmao'] == 0) && ($params['picking'] == 1)) {
            $source->andWhere("e.idCaracteristica = '$tipoPicking'");
        }
        $source->orderBy('p.descricao', "ASC");
        $this->setSource(new \Core\Grid\Source\Doctrine($source))
                ->setId('produtos-grid')
                ->setAttrib('caption', 'Produtos')
                ->addColumn(array(
                    'label' => 'Código',
                    'index' => 'codProduto'
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
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

        if(isset($params['incluir-lista'])) {
            if ($params['incluir-lista'] == 1) {
                return $source->getQuery()->getResult();
            }
        }
        return $this;
    }

}
