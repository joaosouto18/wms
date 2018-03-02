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
    public function save($codProduto, $grade, $qtd, $operacao, $codPessoa, $codOperacao){
        $saldo = $this->calculaSaldoFinal($codProduto, $grade, $qtd);
        if($saldo >= 0) {
            $estoqueProprietario = new EstoqueProprietario();
            $estoqueProprietario->setCodProduto($codProduto);
            $estoqueProprietario->setGrade($grade);
            $estoqueProprietario->setQtd($qtd);
            $estoqueProprietario->setSaldoFinal($saldo);
            $estoqueProprietario->setCodPessoa($codPessoa);
            $estoqueProprietario->setOperacao($operacao);
            $estoqueProprietario->setCodOperacao($codOperacao);
            $estoqueProprietario->setDthOperacao(new \DateTime);

            $this->_em->persist($estoqueProprietario);
            $this->_em->flush();
        }else{
            throw new \Exception('Operação cancelada. Proprietário ficará com saldo negativo.');
        }
    }

    public function calculaSaldoFinal($codProduto, $grade, $qtd){
        $sql = "SELECT * FROM ESTOQUE_PROPRIETARIO 
                WHERE COD_PRODUTO = $codProduto AND DSC_GRADE = '$grade' AND ROWNUM = 1
                ORDER BY COD_ESTOQUE_PROPRIETARIO DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        $saldo = $result['SALDO_FINAL'] + $qtd;
        return $saldo;
    }

    public function efetivaEstoquePropRecebimento($idRecebimento){
        $nfRepository = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $nfVetEntity = $nfRepository->findBy(array('recebimento' => $idRecebimento));
        if(!empty($nfVetEntity)){
            foreach ($nfVetEntity as $nf){
                $itemsNF = $nfRepository->getConferencia($nf->getFornecedor()->getId(), $nf->getNumero(), $nf->getSerie(), '', 16);
                if(!empty($itemsNF)){
                    foreach ($itemsNF as $itens){
                        $this->save($itens['COD_PRODUTO'], $itens['DSC_GRADE'],$itens['QTD_CONFERIDA'], EstoqueProprietario::RECEBIMENTO, $nf->getCodPessoaProprietario(), $idRecebimento);
                    }
                }
            }
        }
    }

    public function verificaProprietarioExistente($cnpj){
        $cnpj = str_replace(array('.','-','/'),'',$cnpj);
        $empresa = $this->findEmpresaProprietario($cnpj);
        if(empty($empresa)){
            return false;
        }else{
            $entityFilial = $this->getEntityManager()->getRepository('wms:Pessoa\Juridica')->findOneBy(array('cnpj' => $cnpj));
            if(empty($entityFilial)){
                $entityFilial = $this->insereFilialEmpresa($cnpj, $empresa);
            }
            $idPessoa = $entityFilial->getId();
        }
        return $idPessoa;
    }

    public function insereFilialEmpresa($cnpj, $empresa){
        $filial['pessoa']['juridica']['dataAbertura'] = date('d/m/Y');
        $filial['pessoa']['juridica']['cnpj'] = $cnpj;
        $filial['pessoa']['juridica']['idTipoOrganizacao'] = 114;
        $filial['pessoa']['juridica']['idRamoAtividade'] = null;
        $filial['pessoa']['juridica']['nome'] = $empresa['NOM_EMPRESA'];
        $filial['pessoa']['tipo'] = 'J';

        $entityFilial  = new Filial();
        $entityPessoa = $this->getEntityManager()->getRepository('wms:Filial')->persistirAtor($entityFilial, $filial);
        $entityFilial->setId($entityPessoa->getId());
        $entityFilial->setIdExterno(null);
        $entityFilial->setCodExterno(null);
        $entityFilial->setIndLeitEtqProdTransbObg('N');
        $entityFilial->setIndUtilizaRessuprimento('N');
        $entityFilial->setIndRecTransbObg('N');
        $entityFilial->setIsAtivo('S');

        $this->_em->persist($entityFilial);
        $this->_em->flush();
        return $entityPessoa;
    }

    public function findEmpresaProprietario($cnpj){
        $cnpj = str_replace(array('.','-','/'),'',$cnpj);
        $prefixCnpj = (substr($cnpj, 0, 8));
        $sql = "SELECT * FROM EMPRESA WHERE IDENTIFICACAO LIKE '$prefixCnpj%'";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }
}