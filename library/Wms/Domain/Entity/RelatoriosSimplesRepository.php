<?php


namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\RelatoriosSimples as RelatoriosSimplesEntity;

/**
 * RelatoriosSimples
 */
class RelatoriosSimplesRepository extends EntityRepository {

    public function getConsultaRelatorioOndas($params,$relatorio){

       // $header=$this->getHeader($relatorio);
        $result=false;
        if ( !empty($params['dataInicial']) ){
            $expInicial=explode("/",$params['dataInicial']);

            $dataInicio=new \DateTime();
            $dataInicio->setDate($expInicial[2],$expInicial[1],$expInicial[0]);

            if ( !empty($params['dataFinal']) )
                $data_fim=$params['dataFinal'];
            else
                $data_fim=$params['dataInicial'];

            $expFinal=explode("/",$data_fim);
            $dataFim=new \DateTime();
            $dataFim->setDate($expFinal[2],$expFinal[1],$expFinal[0]);

            $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("
                    o.id as OndaOs,
                    ores.id as Onda,
                    ores.dataCriacao as Data_Criacao,
                    p.id as Produto,
                    p.grade as Grade,
                    o.qtd as Quantidade,
                    e.descricao as Pulmao
                ")
                ->from("wms:Ressuprimento\OndaRessuprimentoOs","o")
                ->leftJoin("o.ondaRessuprimento", "ores")
                ->leftJoin("o.produto", "p")
                ->leftJoin("o.os", "os")
                ->leftJoin("o.endereco", "e")
                ->where("( ores.dataCriacao >= :dInicio and ores.dataCriacao <= :dFim )")
                ->setParameter("dInicio",$dataInicio)
                ->setParameter("dFim",$dataFim)
                ->orderBy("o.id,ores.id");

            $result =$dql->getQuery()->getArrayResult();

        }

        return $result;

    }


    public function getConsultaRelatorioReservaInativa($params){

        $result=false;
        if ( !empty($params['dataInicial']) ){
            $expInicial=explode("/",$params['dataInicial']);

            $dataInicio=new \DateTime();
            $dataInicio->setDate($expInicial[2],$expInicial[1],$expInicial[0]);

            if ( !empty($params['dataFinal']) )
                $data_fim=$params['dataFinal'];
            else
                $data_fim=$params['dataInicial'];

            $expFinal=explode("/",$data_fim);
            $dataFim=new \DateTime();
            $dataFim->setDate($expFinal[2],$expFinal[1],$expFinal[0]);

            $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("
                    re.dataReserva as DTReserva,
                    e.descricao as Endereco,
                    p.id as Produto,
                    p.grade as Grade,
                    re.qtd as Quantidade,
                    re.tipoReserva as Tipo
                ")
                ->from("wms:Ressuprimento\ReservaEstoque","re")
                ->leftJoin("re.produto", "p")
                ->leftJoin("re.endereco", "e")
                ->where("( re.dataReserva >= :dInicio and re.dataReserva <= :dFim and re.atendida='N' )")
                ->setParameter("dInicio",$dataInicio)
                ->setParameter("dFim",$dataFim)
                ->orderBy("re.id,re.dataReserva");

            $result =$dql->getQuery()->getArrayResult();

        }

        return $result;

    }

    public function getConsultaRelatorioPedidosExpedicaoSql($params)
    {
        $expedicoes = implode(',',$params['expedicao']);

        $sql = "
                SELECT
               PP.COD_PRODUTO,
               QTD.DSC_PRODUTO PRODUTO,
               PP.DSC_GRADE GRADE,
               QTD.QTD as QTD_PEDIDO,
               PK.DSC_DEPOSITO_ENDERECO AS END_PICKING,
               PU.DSC_DEPOSITO_ENDERECO AS END_PULMAO,
               E.QTD as QTD_ESTOQUE

          FROM PEDIDO_PRODUTO PP
          LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
          LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
          LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
          LEFT JOIN ESTOQUE E ON E.COD_PRODUTO = PP.COD_PRODUTO AND E.DSC_GRADE = PP.DSC_GRADE
          LEFT JOIN DEPOSITO_ENDERECO PU ON PU.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
          LEFT JOIN (SELECT MAX(PK.COD_DEPOSITO_ENDERECO) as COD_DEPOSITO_ENDERECO,
                            P.COD_PRODUTO,
                            P.DSC_GRADE
                       FROM PRODUTO P
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN DEPOSITO_ENDERECO PK ON (PK.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR PK.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO)
                      GROUP BY P.COD_PRODUTO,
                               P.DSC_GRADE) EPK ON EPK.COD_PRODUTO = PP.COD_PRODUTO AND EPK.DSC_GRADE = PP.DSC_GRADE
          LEFT JOIN DEPOSITO_ENDERECO PK ON EPK.COD_DEPOSITO_ENDERECO = PK.COD_DEPOSITO_ENDERECO
          LEFT JOIN ( SELECT SUM(PP2.QUANTIDADE) as QTD,
                             PROD2.COD_PRODUTO,
                             PROD2.DSC_GRADE,
                             SUBSTR(PROD2.DSC_PRODUTO,1,35) DSC_PRODUTO
                        FROM PEDIDO_PRODUTO PP2
                   LEFT JOIN PEDIDO P2 ON P2.COD_PEDIDO = PP2.COD_PEDIDO
                   LEFT JOIN CARGA C2 ON C2.COD_CARGA = P2.COD_CARGA
                   LEFT JOIN EXPEDICAO E2 ON E2.COD_EXPEDICAO = C2.COD_EXPEDICAO
                   LEFT JOIN PRODUTO PROD2 ON PROD2.COD_PRODUTO = PP2.COD_PRODUTO AND PROD2.DSC_GRADE = PP2.DSC_GRADE
                       WHERE E2.COD_EXPEDICAO IN (".$expedicoes.")

                    GROUP BY PROD2.COD_PRODUTO,
                             PROD2.DSC_GRADE,
                             PROD2.DSC_PRODUTO) QTD ON QTD.COD_PRODUTO = PP.COD_PRODUTO AND QTD.DSC_GRADE = PP.DSC_GRADE
         WHERE E.COD_EXPEDICAO IN (".$expedicoes.")
          AND PU.COD_CARACTERISTICA_ENDERECO != 37

         GROUP BY
               PP.COD_PRODUTO,
               PP.DSC_GRADE,
               PU.DSC_DEPOSITO_ENDERECO,
               PK.DSC_DEPOSITO_ENDERECO,
               E.QTD,
               PP.QUANTIDADE,
               QTD.QTD,
               QTD.DSC_PRODUTO,
               PK.NUM_RUA, PK.NUM_PREDIO, PK.NUM_APARTAMENTO,
               PU.NUM_RUA, PU.NUM_PREDIO, PU.NUM_NIVEL, PU.NUM_APARTAMENTO
          ORDER BY PK.NUM_RUA, PK.NUM_PREDIO, PK.NUM_APARTAMENTO,
                   PP.COD_PRODUTO,
                   PP.DSC_GRADE,
                   PU.NUM_RUA, PU.NUM_PREDIO, PU.NUM_NIVEL, PU.NUM_APARTAMENTO
        ";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);
        return $this->separarEnderecosPulmao($resultado);
    }

    public function getConsultaRelatorioPedidosExpedicao($params)
    {

        $result=false;
        if ( !empty($params) ){

            //Relatório Pedidos Expedição
            if ( empty($params['expedicao']) ){
                $select="
                    e.id  as Expedicao,e.dataInicio as DataInicio,e.dataFinalizacao as DataFim,e.placaExpedicao,s.sigla";
                $group="e.id,e.dataInicio,e.dataFinalizacao, e.placaExpedicao,s.sigla";
                $order="e.id";
            } else {
                //PDF Gerado
                $select="
                    pr.id as Cod,
                    pr.descricao as Produto,
                    pr.grade as Grade,
                    sum(pp.quantidade) as Qtd_Pedido,
                    nvl(ev.descricao, ee.descricao) as Endereco_Picking,
                    nvl(ev.rua, ee.rua) as picking_rua,
                    nvl(ev.predio, ee.predio) as picking_predio,
                    nvl(ev.nivel, ee.nivel) as picking_nivel,
                    ed.descricao as Endereco_Pulmao,
                    est.qtd as Qtd_Estoque,
                    e.id as Expedicao";
                $order="picking_rua, picking_predio, picking_nivel, pr.descricao, pr.grade, ed.rua, ed.predio, ed.nivel, ed.apartamento, Endereco_Picking, pp.quantidade, ed.descricao, e.id";
                $group="pr.id, pr.descricao,pr.grade, pp.quantidade, ev.descricao, ee.descricao, ed.descricao, est.qtd, e.id, ed.rua, ed.predio, ed.nivel, ed.apartamento, ev.rua, ee.rua, ev.predio, ee.predio, ev.nivel, ee.nivel";
            }

            $dql = $this->getEntityManager()->createQueryBuilder()
                ->select($select);

            if ( !empty($params['expedicao']) ){
               /* $dql->addSelect('
                    (
                    SELECT
                       LISTAGG(end.descricao, ', ') WITHIN GROUP (ORDER BY end.descricao) Pulmao
                    FROM wms:Enderecamento\Estoque est
                    INNER JOIN est.depositoEndereco end
                    WHERE est.codProduto=pr.id and est.grade=pr.grade
                    GROUP BY end.descricao
                   ) EnderecoPulmao');*/
            }

            $dql ->from("wms:Expedicao\PedidoProduto","pp")
                ->leftJoin("pp.pedido", "p")
                ->leftJoin("p.carga", "c")
                ->leftJoin("c.expedicao", "e")
                ->leftJoin("pp.produto", "pr")
                ->leftJoin("pr.volumes", "pv")
                ->leftJoin("pr.embalagens", "pe")
                 ->leftJoin("pv.endereco", "ev")
                ->leftJoin("pe.endereco", "ee")
                ->leftJoin("e.status", "s")
                ->leftJoin("wms:Enderecamento\Estoque", "est",'WITH','est.codProduto=pr.id and est.grade=pr.grade')
                ->leftJoin("est.depositoEndereco", "ed")
                ->groupBy($group)
                ->orderBy($order);

            $dql=$this->setConditionsPedidosExpedicao($params,$dql);
            $result =$dql->getQuery()->getArrayResult();

            if (!empty($params['expedicao'])){
                $result=$this->separarEnderecosPulmao($result);
                $result = $this->removerDetalheEndereco($result);
            }
        }

        return $result;

    }

    public function setConditionsPedidosExpedicao($params,$dql){
        if ( !empty($params['expedicao']) ){

            $i=0;
            foreach( $params['expedicao'] as $chv => $vlr ){
                if ($i==0)
                    $dql->where("( e.id  = ".$vlr.")");
                else
                    $dql->orWhere("e.id  = ".$vlr."");
                $i++;
            }

        }

        if ( !empty($params['dataInicial1']) ){
            $expInicial=explode("/",$params['dataInicial1']);

            $dataInicio1=new \DateTime();
            $dataInicio1->setDate($expInicial[2],$expInicial[1],$expInicial[0]);
            $dataInicio1->setTime(0,0,0);

            $dql->andWhere(" e.dataInicio >= :dInicio1 ")
                ->setParameter("dInicio1",$dataInicio1);
        }

        if ( !empty($params['dataInicial2']) ){
            $dataInicio2=$params['dataInicial2'];

            $expInicial2=explode("/",$dataInicio2);
            $dataInicio2=new \DateTime();
            $dataInicio2->setDate($expInicial2[2],$expInicial2[1],$expInicial2[0]);
            $dataInicio2->setTime(23,59,59);

            $dql->andWhere(" e.dataInicio <= :dInicio2 ")
                ->setParameter("dInicio2",$dataInicio2);
        }

        if ( !empty($params['dataFinal1']) ){
            $expFinal=explode("/",$params['dataFinal1']);

            $dataFinal1=new \DateTime();
            $dataFinal1->setDate($expFinal[2],$expFinal[1],$expFinal[0]);
            $dataFinal1->setTime(0,0,0);

            $dql->andWhere(" e.dataFinalizacao >= :dFinal1 ")
                ->setParameter("dFinal1",$dataFinal1);
        }

        if ( !empty($params['dataFinal2']) ){
            $dataFinal2=$params['dataFinal2'];

            $expFinal2=explode("/",$dataFinal2);
            $dataFinal2=new \DateTime();
            $dataFinal2->setDate($expFinal2[2],$expFinal2[1],$expFinal2[0]);
            $dataFinal2->setTime(23,59,59);

            $dql->andWhere(" e.dataFinalizacao <= :dFinal2 ")
                ->setParameter("dFinal2",$dataFinal2);
        }

        if ( !empty($params['idExpedicao']) ){
            $dql->andWhere(" e.id = :idExpedicao ")
                ->setParameter("idExpedicao",$params['idExpedicao']);
        }

        if ( !empty($params['status']) ){
            $dql->andWhere(" s.id = :status ")
                ->setParameter("status",$params['status']);
        }

        if ( !empty($params['codCargaExterno']) ){
            $dql->andWhere(" c.id = :codCarga ")
                ->setParameter("codCarga",$params['codCargaExterno']);
        }

        if ( !empty($params['placa']) ){
            $dql->andWhere(" c.placaCarga = :placa ")
                ->setParameter("placa",$params['placa']);
        }
        $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
        $dql->andWhere(" ed.descricao is not null ")
            ->andWhere("ed.idCaracteristica != $idCaracteristicaPicking");

        return $dql;
    }

    public function separarEnderecosPulmao($result){
        $arrayRetorno=array();
        $rowAntiga=null;
        $picking="!!!!!";
        $idProduto="!!!!";
        $contNumPulmao = 1;
        $qtdEnderecosPulmao = 2;

        foreach($result as $chv =>$vlr){
            $pickingResult=$vlr['END_PICKING'];
            $produtoResult=$vlr['COD_PRODUTO'].$vlr['GRADE'];

            if ($picking!=$pickingResult && $produtoResult!=$idProduto){
               if ( !empty($rowAntiga) && $contNumPulmao <= $qtdEnderecosPulmao){
                   $arrayRetorno[]=$this->removerEnderecos($rowAntiga,1);
                   $contNumPulmao++;
               }
                $arrayRetorno[]=$this->removerEnderecos($vlr,2);
                $contNumPulmao = 1;
            } else {
                if ( !empty($rowAntiga) && $contNumPulmao <= $qtdEnderecosPulmao ){
                    $arrayRetorno[]=$this->removerEnderecos($rowAntiga,1);
                    $contNumPulmao++;
                }
            }

            $picking=$vlr['END_PICKING'];
            $idProduto=$vlr['COD_PRODUTO'].$vlr['GRADE'];

            $rowAntiga=$vlr;
        }

        if ($picking!=$pickingResult && $produtoResult!=$idProduto){
            $arrayRetorno[]=$this->removerEnderecos($rowAntiga,2);
            $arrayRetorno[]=$this->removerEnderecos($rowAntiga,1);
        } else {
            if ( !empty($rowAntiga) && $contNumPulmao <= $qtdEnderecosPulmao ){
                $arrayRetorno[]=$this->removerEnderecos($rowAntiga,1);
            }
        }

        return $arrayRetorno;
    }
    /*
     * @var tipo: 1=> Picking, 2=>Pulmão
     */
    public function removerEnderecos($valor,$tipo){
        $dados=$valor;
        if ($tipo==1){
            $dados['END_PICKING']='';
            $dados['QTD_PEDIDO']='';
        } else {
            $dados['END_PULMAO']='';
            $dados['QTD_ESTOQUE']='';
        }

        unset($dados['id']);
        return $dados;
    }

}