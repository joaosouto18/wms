<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class VEtiquetaSeparacaoRepository extends EntityRepository
{
    public function getMotivoReimpressao($params)
    {
        $where[] = "V.REIMPRESSAO IS NOT NULL";

        if (!empty($params['idEtiqueta']))
            $where[] = "V.CODBARRAS = $params[idEtiqueta]";

        if (!empty($params['expedicao']))
            $where[] = "V.EXPEDICAO = $params[expedicao]";

        if (!empty($params['codPedido']))
            $where[] = "V.CODEXTERNO = '$params[codPedido]'";

        if (!empty($params['codCargaExterno']))
            $where[] = "V.CODCARGAEXTERNO = $params[codCargaExterno]";

        if (!empty($params['dataInicial1']))
            $where[] = "E.DTH_INICIO >= TO_DATE('$params[dataInicial1] 00:00:00', 'DD/MM/YYYY HH24:MI:SS')";

        if (!empty($params['dataInicial2']))
            $where[] = "E.DTH_INICIO <= TO_DATE('$params[dataInicial2] 23:59:59', 'DD/MM/YYYY HH24:MI:SS')";

        if (!empty($params['codProduto']))
            $where[] = "V.CODPRODUTO = '$params[codProduto]'";

        if (!empty($params['grade']))
            $where[] = "V.GRADE = '$params[grade]'";

        $sql = "
            SELECT
                   V.CODBARRAS AS \"Etiqueta\",
                   V.EXPEDICAO AS \"Expedição\",
                   V.CODCARGAEXTERNO AS \"Carga\",
                   V.CODEXTERNO AS \"Pedido\",
                   V.CLIENTE AS \"Cliente\",
                   V.CODPRODUTO AS \"Código\",
                   V.PRODUTO AS \"Produto\",
                   V.GRADE AS \"Grade\",
                   V.TIPOCOMERCIALIZACAO AS \"Volume\",
                   V.REIMPRESSAO AS \"Motivo\"
            FROM V_ETIQUETA_SEPARACAO V
      INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = V.EXPEDICAO
           WHERE " . implode(" AND ", $where);

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function getCountEtiquetasByCliente($idExpedicao)
    {
        $sql = "SELECT CODCLIENTEEXTERNO, COUNT(CODBARRAS) NUM FROM V_ETIQUETA_SEPARACAO WHERE EXPEDICAO = $idExpedicao GROUP BY CODCLIENTEEXTERNO";
        $result = [];

        foreach ($this->_em->getConnection()->query($sql)->fetchAll() as $item) {
            $result[$item['CODCLIENTEEXTERNO']] = $item['NUM'];
        }

        return $result;
    }
}