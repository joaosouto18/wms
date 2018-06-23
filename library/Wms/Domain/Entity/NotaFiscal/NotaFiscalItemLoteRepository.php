<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 08/05/2018
 * Time: 09:21
 */

namespace Wms\Domain\Entity\NotaFiscal;

use Doctrine\ORM\EntityRepository;

class NotaFiscalItemLoteRepository extends EntityRepository
{
    /**
     * @param $lote string DSC_LOTE
     * @param $codNotaFiscalItem integer ID_NOTA_FISCAL_ITEM
     * @param $quantidade integer QTD
     */
    public function save($lote, $codNotaFiscalItem, $quantidade){
        $NFlote = new NotaFiscalItemLote();
        $NFlote->setLote($lote);
        $NFlote->setCodNotaFiscalItem($codNotaFiscalItem);
        $NFlote->setQuantidade($quantidade);
        $this->_em->persist($NFlote);
    }

    public function removeNFitem($idNFitem){
        $vetEentity = $this->findBy(array('codNotaFiscalItem' => $idNFitem));
        foreach ($vetEentity as $entity) {
            $this->_em->remove($entity);
        }
    }

    public function getQtdLoteByProdutoAndRecebimento($codProduto, $grade, $idRecebimento) {

        $sql = "SELECT NFI.COD_PRODUTO,
                       NFI.DSC_GRADE,
                       NFIL.DSC_LOTE,
                       SUM(NFIL.QUANTIDADE) as QTD
                  FROM NOTA_FISCAL_ITEM_LOTE NFIL
                  LEFT JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL_ITEM = NFIL.COD_NOTA_FISCAL_ITEM
                  LEFT JOIN NOTA_FISCAL NF ON NF.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL
                 WHERE NF.COD_RECEBIMENTO = $idRecebimento
                   AND NFI.COD_PRODUTO = '$codProduto'
                   AND NFI.DSC_GRADE = '$grade'
                 GROUP BY NFI.COD_PRODUTO, NFI.DSC_GRADE, NFIL.DSC_LOTE";
        $result = \Wms\Domain\EntityRepository::nativeQuery($sql);

        return $result;

    }

}