<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class VRelProdutosRepository extends EntityRepository
{
    public function getProdutosByExpedicao ($idExpedicao, $centralEntregaPedido = null) {

        if ($centralEntregaPedido == null)
            return $this->findBy(array('codExpedicao' => $idExpedicao));
        else
            return $this->findBy(array('codExpedicao' => $idExpedicao, 'centralEntrega' => $centralEntregaPedido));

    }

    public function getProdutosByExpedicaoOrderByCarga ($idExpedicao, $central = null,$embalado = NULL)
    {
        $sql = "SELECT CONCAT(LISTAGG(NVL(CN.COD_CARGA,CA.COD_CARGA),',') WITHIN GROUP (ORDER BY CN.COD_CARGA), CONCAT(',',CA.COD_CARGA)) AS COD_CARGA
                FROM CARGA CN
                LEFT JOIN REENTREGA R ON CN.COD_CARGA = R.COD_CARGA
                LEFT JOIN (
                  SELECT LISTAGG(C.COD_CARGA,',') WITHIN GROUP (ORDER BY C.COD_CARGA) AS COD_CARGA, NFS.COD_NOTA_FISCAL_SAIDA
                  FROM CARGA C
                  LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                  LEFT JOIN NOTA_FISCAL_SAIDA_PEDIDO NFSP ON P.COD_PEDIDO = NFSP.COD_PEDIDO
                  LEFT JOIN NOTA_FISCAL_SAIDA NFS ON NFSP.COD_NOTA_FISCAL_SAIDA = NFS.COD_NOTA_FISCAL_SAIDA
                  GROUP BY C.COD_CARGA,NFS.COD_NOTA_FISCAL_SAIDA) CA ON CA.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL_SAIDA
                WHERE CN.COD_EXPEDICAO = $idExpedicao
                GROUP BY CN.COD_CARGA, CA.COD_CARGA";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $codCarga = substr($result[0]["COD_CARGA"],0,-1);

        $andCentral = "";
        if ($central)
            $andCentral = " AND ped.centralEntrega = $central";

        $andEmbalado = "";
        if ($embalado != NULL){
            if ($embalado == "S") {
                $andEmbalado = " AND pe.embalado = 'S'";
            }
            if ($embalado == "N"){
                $andEmbalado = " AND (pe.embalado = 'N' OR pe IS NULL)";
            }
        }
        $source = $this->_em->createQueryBuilder()
            ->select("car.codCargaExterno  as carga,
                      it.descricao         as itinerario,
                      endere.bairro        as bairro,
                      endere.localidade    as cidade,
                      endere.descricao     as rua,
                      pessoa.nome          as cliente,
                      prod.id              as codProduto,
                      prod.descricao       as produto,
                      prod.grade           as grade,
                      fab.nome             as fabricante,
                      pp.quantidade        as quantidade,
                      linha.descricao      as linhaSeparacao,
                      ped.sequencia        as sequencia,
                      ped.id               as pedido,
                      nvl(pe.embalado,'N') as embalado
                      ")
            ->from("wms:Expedicao\PedidoProduto", "pp")
            ->leftJoin("pp.produto"         ,"prod")
            ->leftJoin("prod.embalagens"     ,"pe")
            ->leftJoin("prod.linhaSeparacao","linha")
            ->leftJoin("pp.pedido"          ,"ped")
            ->leftJoin("ped.carga"          ,"car")
            ->leftJoin("ped.itinerario"     ,"it")
            ->leftJoin("prod.fabricante"    ,"fab")
            ->leftJoin("ped.pessoa"         ,"cli")
            ->leftJoin("cli.pessoa"         ,"pessoa")
            ->leftJoin('wms:Expedicao\PedidoEndereco','endere','WITH','ped.id = endere.pedido')
            ->addSelect("(SELECT COUNT(DISTINCT es.codReferencia) as QtdVolume
                            FROM wms:Expedicao\EtiquetaSeparacao es
                           WHERE es.codStatus IN (524,525)
                             AND es.produtoEmbalagem IS NULL
                             AND NOT(es.codReferencia IS NULL)
                             AND es.codProduto = prod.id
                             AND es.dscGrade = prod.grade
                             AND es.pedido = ped
                           GROUP BY es.codProduto, es.dscGrade, es.pedido ) corteVolume ")
            ->addSelect("(SELECT COUNT (es2.id) as QtdEmbalagem
                            FROM wms:Expedicao\EtiquetaSeparacao es2
                           WHERE es2.codStatus IN (524,525)
                             AND es2.produtoVolume IS NULL
                             AND es2.codProduto = prod.id
                             AND es2.dscGrade = prod.grade
                             AND es2.pedido = ped
                           GROUP BY es2.codProduto, es2.dscGrade, es2.pedido) corteEmbalagem")
            ->where("car.id in (" . $codCarga . ")" . $andEmbalado)
            ->distinct(true)
            ->orderBy("car.codCargaExterno,
                       ped.sequencia,
                       embalado,
                       it.descricao,
                       endere.bairro,
                       pessoa.nome,
                       linha.descricao,
                       fab.nome,
                       prod.id,
                       prod.descricao,
                       prod.grade");

        //if($embalado) {
        //    $source->andWhere("prod.linhaSeparacao != 15");
        //}
        return $source->getQuery()->getResult();
   }
}