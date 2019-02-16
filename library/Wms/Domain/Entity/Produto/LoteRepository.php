<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Id\SequenceGenerator,
    Wms\Domain\Entity\Lote as LoteEntity;
use Wms\Domain\Entity\NotaFiscal\Item;
use Wms\Domain\Entity\NotaFiscal\NotaFiscalItemLoteRepository;
use Wms\Domain\Entity\NotaFiscalRepository;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\ProdutoRepository;
use Wms\Math;


class LoteRepository extends EntityRepository
{
    /**
     * @param $codProduto
     * @param $grade
     * @param $dsc
     * @param $codPessoa
     * @param string $origem
     * @return Lote
     * @throws \Exception
     */
    public function save($codProduto, $grade, $dsc, $codPessoa, $origem = Lote::EXTERNO) {

        $lote = new Lote();
        $sqcGenerator = new SequenceGenerator("SQ_LOTE_01", 1);
        $idLote = $sqcGenerator->generate($this->_em, $lote);
        $lote->setId($idLote);
        if ($origem == 'I' && empty($dsc)) {
            $dsc = 'LI'.$idLote;
        } else {
            $lote->setCodProduto($codProduto);
        }

        $lote->setGrade($grade);
        $lote->setDescricao($dsc);
        $lote->setCodPessoaCriacao($codPessoa);
        $lote->setDthCriacao(new \DateTime);
        $lote->setOrigem($origem);
        $this->_em->persist($lote);

        return $lote;
    }

    /**
     * @param $lote
     * @param $idProduto
     * @param $grade
     * @param null $codPessoaNovaCriacao
     * @return Lote|null
     * @throws \Exception
     */
    public function verificaLote($lote, $idProduto, $grade, $codPessoaNovaCriacao = null){
        /** @var Lote $loteEn */
        $loteEn = $this->findOneBy(['descricao' => $lote]);
        if (!empty($loteEn)) {
            if ($loteEn->getCodProduto() == $idProduto && $loteEn->getGrade() == $grade) {
                return $loteEn;
            } elseif ($loteEn->getOrigem() == Lote::INTERNO && empty($loteEn->getCodProduto()) && empty($loteEn->getGrade())) {
                $loteEn->setCodProduto($idProduto)->setGrade($grade);
                $this->_em->persist($loteEn);
                return $loteEn;
            } elseif ($loteEn->getOrigem() == Lote::INTERNO && ($loteEn->getCodProduto() != $idProduto || $loteEn->getGrade() != $grade)) {
                return self::save($idProduto, $grade, $lote, (!empty($codPessoaNovaCriacao)) ? $codPessoaNovaCriacao : $loteEn->getCodPessoaCriacao(), Lote::INTERNO);
            }
        }

        return null;
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
        if (isset($parametros['loteLimpo']) && !empty($parametros['loteLimpo'])) {
            $where .= " AND L.COD_PRODUTO IS NULL";
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

    public function getLoteRecebimento($lote, $codProduto, $grade) {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("l")
            ->from("wms:Produto\Lote", 'l')
            ->where("1 = 1 AND (l.descricao = '$lote' AND l.codProduto = '$codProduto' AND l.grade = '$grade')
                    OR (l.descricao = '$lote' AND l.codProduto is null AND l.grade is null)");

        $result = $dql->getQuery()->getResult();

        if (!empty($result)){
            return $result[0];
        }
        return null;
    }

    public function reorderNFItensLoteByRecebimento($idRecebimento, $itensConferidos)
    {

        $dqlNota = $this->_em->createQueryBuilder()
            ->select("nfi")
            ->from("wms:NotaFiscal\Item", "nfi")
            ->innerJoin("nfi.notaFiscal", "nf")
            ->where("nf.recebimento = :idRecebimento AND nfi.codProduto = :codProduto AND nfi.grade = :grade");

        $arrLotes = $arr = [];
        $strLink = "$#$";
        foreach ($itensConferidos as $item) {
            $codGrade = $item['codProduto'].$strLink.$item['grade'];
            $arr[$codGrade][$item['lote']] = $item['qtdConferida'];
            $arrLotes[$item['lote']][$codGrade] = true;
        }

        /*
         * AQUI É FEITA A REPARTIÇÃO ENTRE LOTES E NOTAS DE CADA PRODUTO, CONTEMPLANDO AS SITUAÇÕES:
         * Mesmo produto em N Notas conferido em 1 Lote
         * Mesmo produto em 1 Nota conferido em N Lotes
         * Mesmo produto em N Notas conferido em N Lotes
         */
        $itensVinculados = [];

        foreach ($arr as $prodGrade => $lotes) {
            list($codigo, $grade) = explode($strLink, $prodGrade);
            /** @var Item[] $itensNf */
            $itensNf = $dqlNota->setParameters([
                "idRecebimento" => $idRecebimento,
                "codProduto" => $codigo,
                "grade" => $grade])->getQuery()->getResult();

            foreach ($lotes as $lote => $qtdConferida) {
                $restante = $qtdConferida;
                foreach ($itensNf as $item) {
                    $idItemNF = $item->getId();
                    $itemLote = $idItemNF.$strLink.$lote;
                    if (!isset($itensVinculados[$itemLote])) {
                        $qtdItemNF = $item->getQuantidade();
                        if (Math::compare($restante, $qtdItemNF, "<=")) {
                            $qtd = $restante;
                        } else {
                            $qtd = $qtdItemNF;
                        }
                        $restante = Math::subtrair($restante, $qtd);
                        $itensVinculados[$itemLote]['qtdVinc'] = $qtd;
                        $itensVinculados[$itemLote]['qtdLivre'] = Math::subtrair($qtdItemNF, $qtd);
                        $itensVinculados[$itemLote]['vinculado'] = ($itensVinculados[$itemLote]['qtdLivre'] == 0);
                    } elseif (!$itensVinculados[$itemLote]['vinculado']) {
                        if (Math::compare($restante, $itensVinculados[$idItemNF]['qtdPend'], "<=")) {
                            $qtd = $restante;
                        } else {
                            $qtd = $itensVinculados[$idItemNF]['qtdPend'];
                        }
                        $restante = Math::subtrair($restante, $qtd);
                        $itensVinculados[$itemLote]['qtdVinc'] = Math::adicionar($itensVinculados[$itemLote]['qtdVinc'], $qtd);
                        $itensVinculados[$itemLote]['qtdLivre'] = Math::subtrair($itensVinculados[$itemLote]['qtdLivre'], $qtd);
                        $itensVinculados[$itemLote]['vinculado'] = ($itensVinculados[$itemLote]['qtdLivre'] == 0);
                    }
                    if ($restante == 0) break;
                }
                if ($restante > 0) {
                    $idItemIndex = key(end($itensVinculados));
                    $itensVinculados[$idItemIndex]['qtdVinc'] = Math::adicionar($itensVinculados[$idItemIndex]['qtdVinc'], $restante);
                }
            }
        }

        /* REPLICA O LOTE INTERNO CASO ESTEJA VINCULADO À MAIS DE 1 PRODUTO */
//        foreach ($arrLotes as $lote => $produtos) {
//            $loteEn = $this->findOneBy(["descricao" => $lote, "codProduto" => null, "grade" => null]);
//            end($produtos);
//            $last = key($produtos);
//            reset($produtos);
//            foreach ($produtos as $prodGrade => $var) {
//                list($codigo, $grade) = explode($strLink, $prodGrade);
//                if ($prodGrade == $last) {
//                    $loteEn->setCodProduto($codigo)->setGrade($grade);
//                    $this->_em->persist($loteEn);
//                } else {
//                    self::save($codigo, $grade, $lote, $loteEn->getCodPessoaCriacao(), Lote::INTERNO);
//                }
//            }
//        }

        /* PERSISTE OS REGISTROS DE ITENS DAS NFS VINCULADOS AOS RESPECTIVOS LOTES RECEBIDOS*/
        /** @var NotaFiscalItemLoteRepository $notaItemLoteRepo */
        $notaItemLoteRepo = $this->_em->getRepository("wms:NotaFiscal\NotaFiscalItemLote");
        foreach ($itensVinculados as $idItemLote => $itemVals) {
            list($idItemNF, $lote) = explode($strLink, $idItemLote);
            $notaItemLoteRepo->save($lote, $idItemNF, $itemVals['qtdVinc']);
        }
    }
}
