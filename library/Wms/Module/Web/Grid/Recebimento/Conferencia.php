<?php

namespace Wms\Module\Web\Grid\Recebimento;

/**
 * Description of Tipo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Conferencia extends \Wms\Module\Web\Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {
        extract($params);

        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('c, r.id as recebimento, p.id idProduto, p.grade, p.descricao, md.descricao as motivoDivergencia')
                ->from('wms:Recebimento\Conferencia', 'c')
                ->join('c.recebimento', 'r')
                ->join('c.produto', 'p')
                ->leftjoin('c.motivoDivergencia', 'md');

        if (isset($idOrdemServico))
            $source->where('c.ordemServico = :idOrdemServico')
                    ->setParameter('idOrdemServico', $idOrdemServico);

        $source->andWhere('p.grade = c.grade')
                ->orderBy('c.id');

        $result = $source->getQuery()->getArrayResult();

        $recebimentoRepo = $this->getEntityManager()->getRepository('wms:Recebimento');
        $produtoRepo     = $this->getEntityManager()->getRepository('wms:Produto');
        $notaFiscalRepo  = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        $repositorios = array(
            'notaFiscalRepo' => $notaFiscalRepo,
            'produtoRepo' => $produtoRepo
        );


        $gridArray = array();
        foreach ($result as $row) {

            $idRecebimento = $row['recebimento'];
            $idProduto = $row['idProduto'];
            $grade = $row['grade'];
            $qtdDivergencia = $row[0]['qtdDivergencia'];
            $qtdConferida = $row[0]['qtdConferida'];

            $produtoEn = $produtoRepo->findOneBy(array('id'=>$idProduto,'grade'=>$grade));
            if ($produtoEn->getPossuiPesoVariavel() == "S") {
                $qtds = $recebimentoRepo->getDivergenciaPesoVariavelByOs($idOrdemServico,$idRecebimento,$produtoEn,$repositorios);
                $qtdConferida = $qtds['pesoConferido'];
                $qtdDivergencia = $qtds['pesoDivergencia'];
            }

            $gridRow = array(
                'idProduto' => $idProduto,
                'descricao' => $row['descricao'],
                'grade' => $grade,
                'dataConferencia' => $row[0]['dataConferencia'],
                'qtdConferida' => $qtdConferida,
                'qtdDivergencia' => $qtdDivergencia,
                'motivoDivergencia' => $row['motivoDivergencia']
            );

            $gridArray[] = $gridRow;
        }

        $this->setSource(new \Core\Grid\Source\ArraySource($gridArray))
                ->setId('recebimento-conferencia-grid')
                ->setAttrib('caption', 'Confer&ecirc;ncia dos Produtos')
                ->addColumn(array(
                    'label' => 'Código do Produto',
                    'index' => 'idProduto',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
                ))
                ->addColumn(array(
                    'label' => 'Data da Conferencia',
                    'index' => 'dataConferencia',
                    'render' => 'DataTime',
                ))
                ->addColumn(array(
                    'label' => 'Quantidade Conferida',
                    'index' => 'qtdConferida',
                ))
                ->addColumn(array(
                    'label' => 'Quantidade Divergencia',
                    'index' => 'qtdDivergencia',
                ))
                ->addColumn(array(
                    'label' => 'Obs',
                    'index' => 'motivoDivergencia',
                ));

        return $this;
    }

}
