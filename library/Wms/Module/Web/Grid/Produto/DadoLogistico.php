<?php

namespace Wms\Module\Web\Grid\Produto;

use Wms\Module\Web\Grid,
    Core\Util\Produto;

/**
 * Description of DadoLogistico
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class DadoLogistico extends Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {
        extract($params);

        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('p, c.nome classe, f.nome fabricante, tc.id as idTipoComercializacao, tc.descricao tipoComercializacao')
                ->addSelect("
                    (
                        SELECT COUNT(pe.id) 
                        FROM wms:Produto\Embalagem pe
                        WHERE pe.codProduto = p.id AND pe.grade = p.grade
                    )
                    AS qtdEmb
                    ")
                ->addSelect("
                    (
                        SELECT COUNT(pv.id) 
                        FROM wms:Produto\Volume pv

                        WHERE pv.codProduto = p.id AND pv.grade = p.grade
                    )
                    AS qtdVol
                    ")
                ->from('wms:Produto', 'p')
                ->innerJoin('p.classe', 'c')
                ->innerJoin('p.fabricante', 'f')
                ->innerJoin('p.tipoComercializacao', 'tc')
                ->orderBy('p.descricao');



        if (!empty($classe)) {
            $classe = mb_strtoupper($classe, 'UTF-8');
            $source->andWhere("p.classe = ?1")
                    ->setParameter(1, $classe);
        }
        if (!empty($fabricante)) {
            $fabricante = mb_strtoupper($fabricante, 'UTF-8');
            $source->andWhere("f.nome LIKE '{$fabricante}%'");
        }
        if (!empty($descricao)) {
            $descricao = mb_strtoupper($descricao, 'UTF-8');
            $source->andWhere("p.descricao LIKE '{$descricao}%'");
        }
        if (!empty($grade)) {
            $grade = mb_strtoupper($grade, 'UTF-8');
            $source->andWhere("p.grade LIKE '{$grade}%'");
        }
        if (!empty($id))
            $source->andWhere ("p.id = '" . $id . "'");

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $this->setSource(new \Core\Grid\Source\Doctrine($source))
                ->setId('dado-logistico-grid')
                ->setAttrib('caption', 'Dados Logísticos')
                ->addColumn(array(
                    'label' => 'Código',
                    'index' => 'id'
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
                    'label' => 'Classe',
                    'index' => 'classe'
                ))
                ->addColumn(array(
                    'label' => 'Fabricante',
                    'index' => 'fabricante'
                ))
                ->addColumn(array(
                    'label' => 'Tipo Comerc.',
                    'index' => 'tipoComercializacao'
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Emb.',
                    'index' => 'qtdEmb'
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Vol.',
                    'index' => 'qtdVol'
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'controllerName' => 'produto',
                    'actionName' => 'edit',
                    'pkIndex' => array('id', 'grade'),
                ))
                ->addAction(array(
                    'label' => 'Visualizar',
                    'controllerName' => 'produto',
                    'actionName' => 'view-produto-ajax',
                    'cssClass' => 'view dialogAjax',
                    'pkIndex' => array('id', 'grade', 'idTipoComercializacao'),
                ))
                ->addAction(array(
                    'label' => 'Migrar Dado Logistico',
                    'controllerName' => 'produto',
                    'actionName' => 'dado-logistico-ajax',
                    'pkIndex' => array('id', 'grade'),
                    'cssClass' => 'dialogAjax',
                ))
                ->addAction(array(
                    'label' => 'Imprimir etiqueta avulsa',
                    'controllerName' => 'produto',
                    'actionName' => 'gerar-etiqueta-pdf',
                    'pkIndex' => array('id', 'grade'),
                    'cssClass' => 'pdf',
                ))
                ->addAction(array(
                    'label' => 'Imprimir etiqueta picking',
                    'controllerName' => 'endereco',
                    'actionName' => 'imprimir',
                    'pkIndex' => array('id', 'grade'),
                    'cssClass' => 'pdf'
                ))
                ->setHasOrdering(true);

        return $this;
    }

}
