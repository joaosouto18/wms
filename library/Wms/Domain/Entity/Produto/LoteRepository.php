<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Id\SequenceGenerator,
    Wms\Domain\Entity\Lote as LoteEntity;
use Wms\Domain\Entity\NotaFiscal\Item;
use Wms\Domain\Entity\NotaFiscal\NotaFiscalItemLoteRepository;
use Wms\Domain\Entity\NotaFiscalRepository;
use Wms\Domain\Entity\Pessoa;
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
            $lote->setProduto($this->_em->getReference("wms:Produto",["id" => $codProduto, "grade" => $grade]));
            $lote->setCodProduto($codProduto);
            $lote->setGrade($grade);
        }

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
     * @param boolean $cine Create If Not Exist
     * @return Lote|null
     * @throws \Exception
     */
    public function verificaLote($lote, $idProduto, $grade, $codPessoaNovaCriacao = null, $cine = false){
        /** @var Lote $loteEn */
        $dql = $this->_em->createQueryBuilder();
        $dql->select("l")
            ->from("wms:Produto\Lote", "l")
            ->where("l.descricao = :lote")
            ->andWhere("((l.codProduto = :idProduto AND l.grade = :grade ) OR (l.codProduto IS NULL AND l.grade IS NULL))")
            ->setParameters([":lote" => $lote, ":idProduto" => $idProduto, ":grade" => $grade]);

        $loteEn = $dql->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if (!empty($loteEn) && !empty($loteEn->getCodProduto())) {
            return $loteEn;
        } elseif (!empty($loteEn) && $loteEn->isInterno() && empty($loteEn->getCodProduto()) && $cine) {
            $loteEn->setCodProduto($idProduto)->setGrade($grade);
            $this->_em->persist($loteEn);
            return $loteEn;
        } elseif (empty($loteEn)) {
            $loteEn = $this->findOneBy(['descricao' => $lote]);
            if (((!empty($loteEn) && $loteEn->isInterno()) || empty($loteEn)) && $cine) {
                if (empty($codPessoaNovaCriacao)) {
                    $codPessoaNovaCriacao = \Zend_Auth::getInstance()->getIdentity()->getId();
                }
                return self::save($idProduto, $grade, $lote, $codPessoaNovaCriacao, Lote::INTERNO);
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

    /**
     * @param $idRecebimento
     * @param $itensConferidos
     * @param Pessoa $conferente
     * @throws \Doctrine\ORM\ORMException
     */
    public function reorderNFItensLoteByRecebimento($idRecebimento, $itensConferidos, $conferente)
    {

        $dqlNota = $this->_em->createQueryBuilder()
            ->select("nfi")
            ->from("wms:NotaFiscal\Item", "nfi")
            ->innerJoin("nfi.notaFiscal", "nf")
            ->where("nf.recebimento = :idRecebimento AND nfi.codProduto = :codProduto AND nfi.grade = :grade");

        $arrLotes = $arr = [];
        $strLink = "+#+";
        foreach ($itensConferidos as $item) {
            if (empty($item['qtdConferida'])) continue;
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
                    $keys = array_keys($itensVinculados);
                    $idItemIndex = end($keys);
                    $itensVinculados[$idItemIndex]['qtdVinc'] = Math::adicionar($itensVinculados[$idItemIndex]['qtdVinc'], $restante);
                }
            }
        }

        /* CRIA NOVOS REGISTROS DE LOTE INTERNO E REPLICA CASO ESTEJA VINCULADO À MAIS DE 1 PRODUTO */
        foreach ($arrLotes as $lote => $produtos) {
            foreach ($produtos as $prodGrade => $var) {
                list($codigo, $grade) = explode($strLink, $prodGrade);
                self::verificaLote($lote, $codigo, $grade, $conferente->getId(), true);
            }
        }

        /* PERSISTE OS REGISTROS DE ITENS DAS NFS VINCULADOS AOS RESPECTIVOS LOTES RECEBIDOS*/
        /** @var NotaFiscalItemLoteRepository $notaItemLoteRepo */
        $notaItemLoteRepo = $this->_em->getRepository("wms:NotaFiscal\NotaFiscalItemLote");
        foreach ($itensVinculados as $idItemLote => $itemVals) {
            list($idItemNF, $lote) = explode($strLink, $idItemLote);
            $notaItemLoteRepo->save($lote, $idItemNF, $itemVals['qtdVinc']);
        }
    }
}
