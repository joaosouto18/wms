<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class SeparacaoMapaSeparacaoRepository extends EntityRepository{

    public function separaProduto($codigoBarras, $codMapaSeparacao, $codOs, $codDepositoEndereco, $qtdSeparar){
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $produtoEn = $produtoRepo->getProdutoByCodBarrasOrCodProduto($codigoBarras);
        if($this->verificaProdutoSeparar($produtoEn->getId(), $produtoEn->getGrade(), $codMapaSeparacao, $codDepositoEndereco, $qtdSeparar)){
            $this->save($produtoEn, $codMapaSeparacao, $codOs, $qtdSeparar);
        }
    }

    public function verificaProdutoSeparar($codProduto, $grade, $codMapaSeparacao, $codDepositoEndereco, $qtdSeparar){
        $sql = "SELECT
                    (MPS.QTD_SEPARAR - MPS.QTD_CORTADO) AS SEPARAR,
                    P.DSC_PRODUTO,
                    P.DSC_GRADE,
                    MPS.COD_DEPOSITO_ENDERECO
                FROM 
                  MAPA_SEPARACAO_PRODUTO MPS
                  INNER JOIN PRODUTO P ON (P.COD_PRODUTO = MPS.COD_PRODUTO AND P.DSC_GRADE = MPS.DSC_GRADE)
                WHERE 
                  MPS.COD_PRODUTO = $codProduto AND
                  MPS.DSC_GRADE = '$grade' AND
                  MPS.COD_DEPOSITO_ENDERECO = $codDepositoEndereco AND
                  MPS.COD_MAPA_SEPARACAO = $codMapaSeparacao";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if(!empty($result)){
            $qtdTotalSeparar = 0;
            foreach ($result as $value){
                $qtdTotalSeparar += $value['SEPARAR'];
            }
            if($qtdSeparar <= $qtdTotalSeparar ){
                return true;
            }
        }else{
            throw new \Exception("Produto não encontrado nesse endereço");
        }
    }

    public function save($produtoEn, $codMapaSeparacao, $codOs, $qtdSeparar){
        $separacao = new SeparacaoMapaSeparacao();
        $separacao->setCodMapaSeparacao($codMapaSeparacao);
        $separacao->setCodOs($codOs);
        $separacao->setProduto($produtoEn);
        $separacao->setCodProduto($produtoEn->getId());
        $separacao->setGrade($produtoEn->getGrade());
        $separacao->setCodProdutoEmbalagem(NULL);
        $separacao->setQtdEmbalagem(NULL);
        $separacao->setCodProdutoVolume(NULL);
        $separacao->setDthSeparacao(new \DateTime());
        $separacao->setQtdSeparada($qtdSeparar);
        $this->_em->persist($separacao);
        $this->_em->flush();
    }
}