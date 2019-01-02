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

    public function buildMovimentacaoEstoque($codProduto, $grade, $qtd, $operacao, $codPessoa, $codOperacao = null, $codOperacaoDetalhe = null, $cnpjGrupoExcluir = array()){

        /**
         * Verifica se é uma operação credito ou debito do estoque
         */
        if($qtd > 0){
            $this->save($codProduto, $grade, $qtd, $operacao, $saldoFinal, $codPessoa, $codOperacao, $codOperacaoDetalhe);
        }else{

            $saldo = $this->getSaldoProp($codProduto, $grade, $codPessoa);
            $saldoFinal = $saldo + $qtd;

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
                        throw new \Exception('Estoque Proprietario insuficiente.');
                    }
                }
            }
        }
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
                WHERE COD_PRODUTO = $codProduto AND DSC_GRADE = '$grade' AND COD_PESSOA = $codPessoa AND ROWNUM = 1
                ORDER BY COD_ESTOQUE_PROPRIETARIO DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $result['SALDO_FINAL'];
    }

    public function efetivaEstoquePropRecebimento($idRecebimento){
        $nfRepository = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $nfVetEntity = $nfRepository->findBy(array('recebimento' => $idRecebimento));
        if(!empty($nfVetEntity)){
            foreach ($nfVetEntity as $nf){
                $itemsNF = $nfRepository->getConferencia($nf->getFornecedor()->getId(), $nf->getNumero(), $nf->getSerie(), '', 16);
                if(!empty($itemsNF)){
                    foreach ($itemsNF as $itens){
                        $saldo = $this->getSaldoProp($itens['COD_PRODUTO'], $itens['DSC_GRADE'], $nf->getCodPessoaProprietario());
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
                      WHERE COD_PESSOA = $idProprietario
                      GROUP BY COD_PESSOA)
                  GROUP BY 
                    EP.COD_PESSOA, EP.SALDO_FINAL 
                  ORDER BY 
                    EP.SALDO_FINAL DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getHistoricoEstoqueProprietario($idProprietario, $codProduto, $grade){
        $args = [];
        if(!empty($idProprietario)){
            $args[] = "COD_PESSOA = $idProprietario";
        }
        if(!empty($codProduto)){
            $args[] .= "EP.COD_PRODUTO = $codProduto";
        }
        if(!empty($grade)){
            $args[] .= "EP.DSC_GRADE = '$grade'";
        }

        $whereSub = (!empty($args)) ? "WHERE " . implode(" AND ", $args): "";

        $sql = "SELECT 
                  MAX(EP.COD_ESTOQUE_PROPRIETARIO) as COD, 
                  PJ.NOM_FANTASIA AS PROPRIETARIO,
                  EP.COD_PRODUTO AS PRODUTO,
                  EP.DSC_GRADE AS GRADE,
                  EP.SALDO_FINAL AS SALDO
                FROM 
                  ESTOQUE_PROPRIETARIO EP 
                  INNER JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = EP.COD_PESSOA
                WHERE 1 = 1 AND
                  EP.COD_ESTOQUE_PROPRIETARIO IN (
                      SELECT MAX(COD_ESTOQUE_PROPRIETARIO) FROM ESTOQUE_PROPRIETARIO 
                      $whereSub
                      GROUP BY COD_PESSOA, COD_PRODUTO, DSC_GRADE)
                  GROUP BY 
                      EP.SALDO_FINAL, EP.COD_PRODUTO, EP.DSC_GRADE, PJ.NOM_FANTASIA
                  ORDER BY 
                      PJ.NOM_FANTASIA, EP.COD_PRODUTO, EP.SALDO_FINAL DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
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

        return $entityPessoa;
    }
}