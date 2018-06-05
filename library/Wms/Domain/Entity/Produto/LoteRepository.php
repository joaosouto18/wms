<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Id\SequenceGenerator,
    Wms\Domain\Entity\Lote as LoteEntity;


class LoteRepository extends EntityRepository
{
    public function save($produtoEntity, $grade, $dsc, $codPessoa, $origem = 'E'){


        $lote = new Lote();
        $sqcGenerator = new SequenceGenerator("SQ_LOTE_01", 1);
        $idLote = $sqcGenerator->generate($this->_em, $lote);
        $lote->setId($idLote);
        if($origem == 'I') {
            $dsc = 'LI'.$idLote;
        }else{
            $lote->setProduto($produtoEntity);
        }

        $lote->setGrade($grade);
        $lote->setDescricao($dsc);
        $lote->setCodPessoaCriacao($codPessoa);
        $lote->setDthCriacao(new \DateTime);
        $lote->setOrigem($origem);
        $this->_em->persist($lote);

        return $lote;
    }

    public function verificaLote($lote, $idProduto, $grade){
        return $this->findOneBy(array('descricao' => $lote['lote'], 'codProduto' => $idProduto, 'grade' => $grade));
    }

    public function getLotes($parametros){
        $where = "";
        if (isset($parametros['codProduto']) && !empty($parametros['codProduto'])) {
            $where .= " AND L.COD_PRODUTO = '".$parametros['codProduto']."'" ;
        }
        if (isset($parametros['codLote']) && !empty($parametros['codLote'])) {
            $where .= " AND L.COD_LOTE = '".$parametros['codLote']."'" ;
        }
        if (isset($parametros['tipoLote']) && !empty($parametros['tipoLote'])) {
            $where .= " AND L.IND_ORIGEM_LOTE = '".$parametros['tipoLote']."'" ;
        }
        if (isset($parametros['loteInicio']) && !empty($parametros['loteInicio'])) {
            $where .= " AND L.DSC_LOTE >= '".strtoupper($parametros['loteInicio'])."'" ;
        }
        if (isset($parametros['loteFim']) && !empty($parametros['loteFim'])) {
            $where .= " AND L.DSC_LOTE <= '".strtoupper($parametros['loteFim'])."'" ;
        }
        if (isset($parametros['lote']) && !empty($parametros['lote'])) {
            $where .= " AND L.DSC_LOTE = '".strtoupper($parametros['lote'])."'" ;
        }
        if (isset($parametros['dataIncio']) && !empty($parametros['dataIncio'])) {
            $where .= " AND L.DTH_CRIACAO >= TO_DATE('".$parametros['dataIncio']." 00:00','DD-MM-YYYY HH24:MI')";
        }
        if (isset($parametros['dataFim']) && !empty($parametros['dataFim'])) {
            $where .= " AND L.DTH_CRIACAO <= TO_DATE('".$parametros['dataFim']." 23:59','DD-MM-YYYY HH24:MI')";
        }
        if(isset($parametros['qtdLote']) && !empty($parametros['qtdLote'])){
            $sql = "SELECT L.*, PE.NOM_PESSOA , TO_CHAR(L.DTH_CRIACAO,'DD/MM/YYYY HH24:MI:SS') as CRIACAO, '' AS DSC_PRODUTO FROM LOTE L
                    INNER JOIN PESSOA PE ON PE.COD_PESSOA = L.COD_PESSOA_CRIACAO WHERE ROWNUM <= ".$parametros['qtdLote']." ORDER BY L.COD_LOTE DESC";
        }else{
            $sql = "SELECT L.*, P.DSC_PRODUTO, PE.NOM_PESSOA , TO_CHAR(L.DTH_CRIACAO,'DD/MM/YYYY HH24:MI:SS') as CRIACAO FROM LOTE L 
                    LEFT JOIN PRODUTO P ON (P.COD_PRODUTO = L.COD_PRODUTO AND P.DSC_GRADE = L.DSC_GRADE)
                    INNER JOIN PESSOA PE ON PE.COD_PESSOA = L.COD_PESSOA_CRIACAO WHERE 1 = 1 $where ORDER BY L.COD_LOTE DESC";
        }


        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
