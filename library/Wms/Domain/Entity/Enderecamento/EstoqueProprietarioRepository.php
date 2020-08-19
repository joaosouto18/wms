<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 27/11/2017
 * Time: 16:03
 */
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Filial;
use Wms\Math;

class EstoqueProprietarioRepository extends EntityRepository
{
    public function save($codProduto, $grade, $qtd, $operacao, $saldoFinal, $codPessoa, $codOperacao = null, $codOperacaoDetalhe = null){
        $estoqueProprietario = new EstoqueProprietario();
        $estoqueProprietario->setCodProduto($codProduto);
        $estoqueProprietario->setGrade($grade);
        $estoqueProprietario->setQtd($qtd);
        $estoqueProprietario->setSaldoFinal($saldoFinal);
        $estoqueProprietario->setCodPessoa($codPessoa);
        $estoqueProprietario->setOperacao($operacao);
        $estoqueProprietario->setCodOperacao($codOperacao);
        $estoqueProprietario->setCodOperacaoDetalhe($codOperacaoDetalhe); // Cod da NF ou do Pedido
        $estoqueProprietario->setDthOperacao(new \DateTime);
        $this->_em->persist($estoqueProprietario);
    }

    public function buildMovimentacaoEstoque($codProduto, $grade, $qtd, $operacao, $codPessoa, $codOperacao = null, $codOperacaoDetalhe = null, $cnpjGrupoExcluir = array()) {
        
        $saldo = $this->getSaldoProp($codProduto, $grade, $codPessoa);
        $saldoFinal = $saldo + $qtd;
        /**
         * Verifica se é uma operação credito ou debito do estoque
         */
        if($qtd > 0){
            $this->save($codProduto, $grade, $qtd, $operacao, $saldoFinal, $codPessoa, $codOperacao, $codOperacaoDetalhe);
        }else{

            /**
             * Verifica se esse proprietario tem saldo suficiente para atender o solicitado
             */
            if($saldoFinal >= 0){
                $this->save($codProduto, $grade, $qtd, $operacao, $saldoFinal, $codPessoa, $codOperacao, $codOperacaoDetalhe);
            }else{
                /*
                 * Debita o saldo completo do proprietario e parte para o proximo
                 */
                if (!empty($saldo)) {
                    $this->save($codProduto, $grade, ($saldo * -1), $operacao, 0, $codPessoa, $codOperacao, $codOperacaoDetalhe);
                    $qtd = $qtd + $saldo;
                }
                $propExclui[] = $codPessoa;
                $cnpj = $this->getCnpjProp($codPessoa);
                /*
                 * Busca o grupo desse proprietario excluindo o proprietario que ja foi debitado
                 */
                $vetProprietario = $this->getSaldoGrupo($cnpj, $propExclui, $codProduto, $grade);
                foreach ($vetProprietario as $nextProp){
                    $qtd = $qtd + $nextProp['SALDO_FINAL'];
                    if($qtd < 0){
                        $this->save($codProduto, $grade, ($nextProp['SALDO_FINAL'] * -1), $operacao, 0, $nextProp['COD_PESSOA'], $codOperacao, $codOperacaoDetalhe);
                    }else{
                        $this->save($codProduto, $grade, (($nextProp['SALDO_FINAL'] - $qtd) * -1), $operacao, $qtd, $nextProp['COD_PESSOA'], $codOperacao, $codOperacaoDetalhe);
                        break;
                    }
                }
                if (!in_array($cnpj, $cnpjGrupoExcluir))
                    $cnpjGrupoExcluir[] = $cnpj;
                /*
                 * Caso o grupo nao tenha atendido por completo o solicitado
                 * passa para o proximo grupo seguindo a ordem de prioridade
                 */
                while($qtd < 0) {
                    $proximoCnpj = $this->getProprietarioProximoGrupo($cnpjGrupoExcluir);
                    if (!empty($proximoCnpj)) {
                        $cnpjGrupoExcluir[] = $proximoCnpj;
                        $vetProprietario = $this->getSaldoGrupo($proximoCnpj, $propExclui, $codProduto, $grade);
                        if (empty($vetProprietario))
                            continue;
                        /*
                         * Chama a função de forma recursiva
                         */
                        $this->buildMovimentacaoEstoque($codProduto, $grade, $qtd, $operacao, $vetProprietario[0]['COD_PESSOA'], $codOperacao, $codOperacaoDetalhe, $cnpjGrupoExcluir);
                        break;
                    }else{
                        throw new \Exception('Estoque Proprietario insuficiente.'.$codProduto);
                    }
                }
            }
        }
    }

    private function selectSaldoProprietario($codProduto, $incluir = true, $propsDebitados = [])
    {
        $where = "" ;
        if (!empty($propsDebitados)) {
            $strIds = implode(", ", $propsDebitados);
            $where = "WHERE E.COD_EMPRESA NOT IN ($strIds)";
        }

        $ordenacao = ($incluir) ? "ASC" : "DESC";

        $sql = "SELECT NVL(ESTQ.SALDO_FINAL, 0) SALDO, PJ.COD_PESSOA, E.COD_EMPRESA
                FROM EMPRESA E
                INNER JOIN PESSOA_JURIDICA PJ ON E.IDENTIFICACAO = PJ.NUM_CNPJ
                LEFT JOIN (SELECT EP.SALDO_FINAL, EP.COD_PESSOA
                           FROM ESTOQUE_PROPRIETARIO EP
                           INNER JOIN (SELECT MAX(COD_ESTOQUE_PROPRIETARIO) LAST_MOV, COD_PESSOA
                                       FROM ESTOQUE_PROPRIETARIO
                                       WHERE COD_PRODUTO = '$codProduto'
                                       GROUP BY COD_PESSOA) IDEP ON IDEP.LAST_MOV = EP.COD_ESTOQUE_PROPRIETARIO
                           WHERE EP.SALDO_FINAL > 0) ESTQ ON ESTQ.COD_PESSOA = PJ.COD_PESSOA
                $where
                ORDER BY E.PRIORIDADE_ESTOQUE $ordenacao";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return [$result[0]['SALDO'], $result[0]['COD_PESSOA'], $result[0]['COD_EMPRESA']];
    }

    public function updateSaldoByInventario($codProduto, $idInventario)
    {
        $qtd = self::getDiffEstoqueProduto($codProduto);
        if ($qtd > 0) {
            list( $saldo, $codPessoa ) = self::selectSaldoProprietario($codProduto);
            $saldoFinal = Math::adicionar($saldo, $qtd);
            $this->save($codProduto, "UNICA", $qtd, EstoqueProprietario::INVENTARIO, $saldoFinal, $codPessoa, $idInventario);
        } else {
            $qtd = $qtd * -1;
            $propsDebitados = [];
            while ($qtd != 0) {
                list( $saldoDisponivel, $codPessoa , $propsDebitados[]) = self::selectSaldoProprietario($codProduto, false, $propsDebitados);

                if ($saldoDisponivel > 0) {
                    if (empty($codPessoa)) break;

                    if (Math::compare($qtd, $saldoDisponivel, "<")) {
                        $qtdMov = $qtd;
                    } else {
                        $qtdMov = $saldoDisponivel;
                    }
                    $saldoFinal = Math::subtrair($saldoDisponivel, $qtdMov);
                    $this->save($codProduto, "UNICA", $qtdMov, EstoqueProprietario::INVENTARIO, $saldoFinal, $codPessoa, $idInventario);
                    $qtd = Math::subtrair($qtd, $qtdMov);
                }
            }
        }
    }

    private function getDiffEstoqueProduto($codProduto)
    {
        $sql = "select 
                    est.cod_produto, 
                    est.saldo estoque, 
                    estp.saldo saldo_proprietario, 
                    NVL(est.saldo,0) - NVL(estp.saldo,0) diff
                from (select cod_produto, sum(qtd) saldo from estoque group by cod_produto) est
                left join (select esp.cod_produto, sum(saldo_final) saldo
                           from estoque_proprietario esp
                           inner join (select max(cod_estoque_proprietario) id, cod_produto, cod_pessoa
                                       from estoque_proprietario group by cod_produto, cod_pessoa) last_mov on last_mov.id = esp.cod_estoque_proprietario
                                       group by esp.cod_produto) estp on estp.cod_produto = est.cod_produto
                where est.cod_produto = '$codProduto'";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll();

        if (!empty($result))
            return $result[0]['DIFF'];
        else
            return null;
    }

    public function getProprietarioProximoGrupo($cnpj){
        foreach ($cnpj as $value){
            $vetWhere[] = "IDENTIFICACAO NOT LIKE '$value%'";
        }
        $sql = "SELECT IDENTIFICACAO FROM EMPRESA WHERE ".implode(' AND ', $vetWhere)." ORDER BY PRIORIDADE_ESTOQUE";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return substr($result['IDENTIFICACAO'], 0, 8);

    }

    public function getSaldoGrupo($cnpj, $propExclui, $codProduto, $grade){
        $sql = "SELECT 
                  MAX(EP.COD_ESTOQUE_PROPRIETARIO), 
                  EP.COD_PESSOA, 
                  EP.SALDO_FINAL 
                FROM 
                  ESTOQUE_PROPRIETARIO EP 
                  INNER JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                WHERE 
                  NUM_CNPJ  LIKE '$cnpj%' AND
                  EP.COD_PRODUTO = $codProduto AND
                  EP.DSC_GRADE = '$grade' AND
                  EP.SALDO_FINAL > 0 AND
                  EP.COD_ESTOQUE_PROPRIETARIO IN (
                      SELECT MAX(COD_ESTOQUE_PROPRIETARIO) FROM ESTOQUE_PROPRIETARIO
                      WHERE COD_PESSOA NOT IN (".implode(',',$propExclui).") AND
                            COD_PRODUTO = $codProduto AND
                            DSC_GRADE = '$grade'
                      GROUP BY COD_PESSOA)
                  GROUP BY 
                    EP.COD_PESSOA, EP.SALDO_FINAL 
                  ORDER BY 
                    EP.SALDO_FINAL DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getGrupoProprietarios($cnpj){
        $sql = "SELECT COD_PESSOA FROM PESSOA_JURIDICA WHERE NUM_CNPJ LIKE '$cnpj%'";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getCnpjProp($codPessoa){
        $sql = "SELECT NUM_CNPJ FROM PESSOA_JURIDICA WHERE COD_PESSOA = $codPessoa";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return substr($result['NUM_CNPJ'], 0, 8);
    }

    public function getSaldoProp($codProduto, $grade, $codPessoa){
        $sql = "SELECT * FROM ESTOQUE_PROPRIETARIO 
                WHERE COD_PRODUTO = '$codProduto' 
                  AND DSC_GRADE = '$grade' 
                  AND COD_PESSOA = $codPessoa 
                ORDER BY COD_ESTOQUE_PROPRIETARIO DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            return reset($result)['SALDO_FINAL'];
        } else {
            return 0;
        }
    }

    public function criarEstoquePropRecebimento($idRecebimento){
        $nfRepository = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $nfVetEntity = $nfRepository->findBy(array('recebimento' => $idRecebimento));
        if(!empty($nfVetEntity)){
            foreach ($nfVetEntity as $key => $nf){
                $itemsNF = $nfRepository->getConferencia($nf->getEmissor()->getId(), $nf->getNumero(), $nf->getSerie(), '', 16);
                if(!empty($itemsNF)){
                    foreach ($itemsNF as $itens){
                        $saldo = 0;
                        $codProprietario = $nf->getCodPessoaProprietario();
                        if ($key == 0) {
                            $saldo = $this->getSaldoProp($itens['COD_PRODUTO'], $itens['DSC_GRADE'], $codProprietario);
                        } else {
                            $itensInserting = $this->_em->getUnitOfWork()->getScheduledEntityInsertions();

                            $arrCriterio = array(
                                'codPessoa' => $codProprietario,
                                'codProduto' => $itens['COD_PRODUTO'],
                                'grade' => $itens['DSC_GRADE']
                            );

                            $found = [];
                            foreach ($itensInserting as $entity) {
                                if (is_a($entity, "Wms\Domain\Entity\Enderecamento\ReservaEstoqueProprietario")) {
                                    foreach ($arrCriterio as $prop => $value) {
                                        $method = "get" . ucfirst($prop);
                                        if (method_exists($entity, $method) && (call_user_func(array($entity, $method)) != $value))  break;
                                        if ('grade' == $prop) $found[] = $entity;
                                    }
                                }
                            }

                            if (!empty($found)) {

                                /** @var EstoqueProprietario $saldoAnterior */
                                $saldoAnterior = end($found);
                                $saldo = $saldoAnterior->getSaldoFinal();

                            }
                        }
                        $saldoFinal = $saldo + $itens['QTD_CONFERIDA'];
                        $this->save($itens['COD_PRODUTO'], $itens['DSC_GRADE'],$itens['QTD_CONFERIDA'], EstoqueProprietario::RECEBIMENTO, $saldoFinal, $nf->getCodPessoaProprietario(), $idRecebimento, $nf->getId());
                    }
                }
            }
            $this->_em->flush();
        }
    }

    public function verificaProprietarioExistente($cnpj, $dadosFilial = []){
        $cnpj = str_replace(array('.','-','/'),'',$cnpj);
        $empresa = $this->findEmpresaProprietario($cnpj);
        if (empty($empresa)) {
            return false;
        } else {
            $entityPJ = $this->getEntityManager()->getRepository('wms:Pessoa\Juridica')->findOneBy(array('cnpj' => $cnpj));

            if (empty($entityPJ) && empty($dadosFilial)) {
                return false;
            } else if (empty($entityPJ)) {
                $entityPJ = $this->inserirFilial(null, $cnpj, $dadosFilial);
            } else {
                $entityFilial = $this->getEntityManager()->getRepository('wms:Filial')->findOneBy(array('juridica' => $entityPJ->getId()));
                if(empty($entityFilial)){
                    $this->inserirFilial($entityPJ);
                }
            }
            $idPessoa = $entityPJ->getId();
        }
        return $idPessoa;
    }

    public function findEmpresaProprietario($cnpj){
        $cnpj = str_replace(array('.','-','/'),'',$cnpj);
        $prefixCnpj = (substr($cnpj, 0, 8));
        $sql = "SELECT * FROM EMPRESA WHERE IDENTIFICACAO LIKE '$prefixCnpj%'";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEstoqueProprietario($idProprietario, $codProduto, $grade){
        $sql = "SELECT 
                  MAX(EP.COD_ESTOQUE_PROPRIETARIO), 
                  EP.COD_PESSOA, 
                  EP.SALDO_FINAL 
                FROM 
                  ESTOQUE_PROPRIETARIO EP 
                  INNER JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                WHERE 
                  EP.COD_PRODUTO = $codProduto AND
                  EP.DSC_GRADE = '$grade' AND
                  EP.COD_ESTOQUE_PROPRIETARIO IN (
                      SELECT MAX(COD_ESTOQUE_PROPRIETARIO) FROM ESTOQUE_PROPRIETARIO 
                      WHERE COD_PESSOA = $idProprietario AND
                            COD_PRODUTO = $codProduto AND
                            DSC_GRADE = '$grade'
                      GROUP BY COD_PESSOA)
                  GROUP BY 
                    EP.COD_PESSOA, EP.SALDO_FINAL 
                  ORDER BY 
                    EP.SALDO_FINAL DESC";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProprietarios()
    {
        $sql = "SELECT PJ.COD_PESSOA \"id\", E.NOM_EMPRESA \"nomProp\"
                FROM EMPRESA E
                INNER JOIN PESSOA_JURIDICA PJ ON PJ.NUM_CNPJ = E.IDENTIFICACAO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEstoqueProprietarioGerencial($params){
        $argsEP = [];
        $argsREP = [];
        $argsProp = "";
        if(!empty($params['codProp'])){
            $argsEP[] = "PJ.COD_PESSOA = $params[codProp]";
            $argsREP[] = "COD_PROPRIETARIO = $params[codProp]";
            $argsProp = " WHERE PJ.COD_PESSOA = $params[codProp]";
        }
        if(!empty($params['codProduto'])){
            $argsEP[] .= "EP.COD_PRODUTO = '$params[codProduto]'";
            $argsREP[] .= "COD_PRODUTO = '$params[codProduto]'";
        }

        $whereEP = (!empty($argsEP)) ? 'AND '. implode(" AND ", $argsEP): "";
        $whereREP = (!empty($argsREP)) ? 'AND '. implode(" AND ", $argsREP): "";

        $sql = "SELECT
                    PROP.NOM_EMPRESA \"nomProp\",
                    P.COD_PRODUTO \"codProduto\",
                    P.DSC_PRODUTO \"dscProduto\",
                    SUM(NVL(RES.SALDO_FINAL, 0)) \"qtdEstq\",
                    SUM(NVL(RES.PEND, 0)) \"qtdPend\"
                FROM EMPRESA PROP
                INNER JOIN PESSOA_JURIDICA PJ ON PROP.IDENTIFICACAO = PJ.NUM_CNPJ
                LEFT JOIN (
                    SELECT EP.COD_ESTOQUE_PROPRIETARIO,
                             EP.COD_PESSOA AS COD_PROPRIETARIO,
                             EP.SALDO_FINAL,
                             EP.COD_PRODUTO,
                             EP.DSC_GRADE,
                             0 AS PEND
                    FROM ESTOQUE_PROPRIETARIO EP
                    INNER JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                    INNER JOIN (
                          SELECT MAX(COD_ESTOQUE_PROPRIETARIO) ID, COD_PRODUTO, DSC_GRADE, COD_PESSOA
                          FROM ESTOQUE_PROPRIETARIO
                          GROUP BY COD_PESSOA, COD_PRODUTO, DSC_GRADE) MAX ON MAX.ID = EP.COD_ESTOQUE_PROPRIETARIO
                    WHERE EP.SALDO_FINAL > 0 $whereEP
                    UNION
                    SELECT
                          0 AS COD_ESTOQUE_PROPRIETARIO,
                          COD_PROPRIETARIO,
                          0 AS SALDO_FINAL,
                          COD_PRODUTO,
                          DSC_GRADE,
                          PEND
                    FROM (SELECT
                                 COD_PROPRIETARIO,
                                 COD_PRODUTO,
                                 DSC_GRADE,
                                 SUM(QTD) PEND
                          FROM RESERVA_ESTOQUE_PROPRIETARIO
                          WHERE IND_APLICADO = 'N' $whereREP
                          GROUP BY COD_PRODUTO, DSC_GRADE, COD_PROPRIETARIO)
                    ) RES ON RES.COD_PROPRIETARIO = PJ.COD_PESSOA
                LEFT JOIN PRODUTO P ON (P.COD_PRODUTO = RES.COD_PRODUTO AND P.DSC_GRADE = RES.DSC_GRADE)
                $argsProp
                GROUP BY PJ.COD_PESSOA, P.COD_PRODUTO, PROP.NOM_EMPRESA, P.DSC_PRODUTO
                ORDER BY PROP.NOM_EMPRESA, P.COD_PRODUTO DESC";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHistoricoProprietarioGerencial($params)
    {
        $args = [];
        if (!empty($params['codProp'])) {
            $args[] = "EP.COD_PESSOA = $params[codProp]";
        }
        if (!empty($params['codProduto'])) {
            $args[] .= "EP.COD_PRODUTO = '$params[codProduto]'";
        }
        if (!empty($params['dataInicial'])) {
            $args[] .= "EP.DTH_OPERACAO >= TO_DATE('$params[dataInicial] 00:00:00', 'DD/MM/YYYY HH24:MI:SS')";
        }
        if (!empty($params['dataFinal'])) {
            $args[] .= "EP.DTH_OPERACAO <= TO_DATE('$params[dataFinal] 23:59:59', 'DD/MM/YYYY HH24:MI:SS')";
        }

        $whereSub = (!empty($args)) ? "WHERE " . implode(" AND ", $args): "";

        $idOpRec = EstoqueProprietario::RECEBIMENTO;
        $idOpMov = EstoqueProprietario::MOVIMENTACAO;
        $idOpExp = EstoqueProprietario::EXPEDICAO;
        $idOpInv = EstoqueProprietario::INVENTARIO;

        $sql = "SELECT
                    PROP.NOM_EMPRESA \"nomProp\",
                    EP.COD_PRODUTO \"codProduto\",
                    P.DSC_PRODUTO \"dscProduto\",
                    CASE
                        WHEN EP.IND_OPERCAO = $idOpRec THEN 'Recebimento'
                        WHEN EP.IND_OPERCAO = $idOpMov THEN 'Movim. Manual'
                        WHEN EP.IND_OPERCAO = $idOpExp THEN 'Expedição'
                        WHEN EP.IND_OPERCAO = $idOpInv THEN 'Inventário'
                        END \"tipoMov\",
                    TO_CHAR(EP.DTH_OPERACAO, 'DD/MM/YYYY') \"dthMov\",
                    EP.QTD \"qtdMov\",
                    EP.SALDO_FINAL \"qtdEstq\"
                FROM ESTOQUE_PROPRIETARIO EP
                INNER JOIN PRODUTO P ON P.COD_PRODUTO = EP.COD_PRODUTO AND P.DSC_GRADE = EP.DSC_GRADE
                INNER JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                INNER JOIN EMPRESA PROP ON PROP.IDENTIFICACAO = PJ.NUM_CNPJ
                $whereSub
                ORDER BY
                    EP.DTH_OPERACAO DESC";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function inserirFilial($pj, $novoCnpj = null, $empresa = []){
        $entityFilial  = new Filial();

        if (!empty($novoCnpj) && !empty($empresa)) {
            $filial['pessoa']['juridica']['dataAbertura'] = date('d/m/Y');
            $filial['pessoa']['juridica']['cnpj'] = $novoCnpj;
            $filial['pessoa']['juridica']['idTipoOrganizacao'] = 114;
            $filial['pessoa']['juridica']['idRamoAtividade'] = null;
            $filial['pessoa']['juridica']['nome'] = $empresa['nome'];
            $filial['pessoa']['tipo'] = 'J';
            $pj = $this->getEntityManager()->getRepository('wms:Filial')->persistirAtor($entityFilial, $filial);
        }

        $entityFilial->setId($pj->getId());
        $entityFilial->setJuridica($pj);
        $entityFilial->setIdExterno($pj->getId());
        $entityFilial->setCodExterno($pj->getId());
        $entityFilial->setIndLeitEtqProdTransbObg('N');
        $entityFilial->setIndUtilizaRessuprimento('N');
        $entityFilial->setIndRecTransbObg('N');
        $entityFilial->setIsAtivo('S');
        $this->_em->persist($entityFilial);
        $this->_em->flush();

        return $entityFilial;
    }

    /**
     * @param $codProduto
     * @param $grade
     * @param $proprietario
     * @return EstoqueProprietario|null
     */
    public function getlastMov($codProduto, $grade, $proprietario) {
        /** @var EstoqueProprietario[]|array $last */
        $last = $this->findBy(['codProduto' => $codProduto, 'grade' => $grade, 'codPessoa' => $proprietario], ['id' => 'DESC'], 1);
        return (!empty($last)) ? $last[0] : null;
    }
}