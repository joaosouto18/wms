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

    public function getProdutosByExpedicaoOrderByCarga ($idExpedicao, $central = null,$embalado = NULL) {

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
            ->innerJoin("pp.produto"         ,"prod")
            ->leftJoin("prod.embalagens"     ,"pe")
            ->innerJoin("prod.linhaSeparacao","linha")
            ->innerJoin("pp.pedido"          ,"ped")
            ->innerJoin("ped.carga"          ,"car")
            ->innerJoin("ped.itinerario"     ,"it")
            ->innerJoin("prod.fabricante"    ,"fab")
            ->innerJoin("ped.pessoa"         ,"cli")
            ->innerJoin("cli.pessoa"         ,"pessoa")
            ->leftJoin("pessoa.enderecos"   ,"endere")
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
            ->where("car.expedicao = " . $idExpedicao . $andEmbalado)
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