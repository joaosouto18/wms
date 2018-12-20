<?php
namespace Wms\Domain\Entity\Armazenagem;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Armazenagem\LinhaSeparacao as Linha;


class LinhaSeparacaoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param LinhaSeparacao $linha
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(Linha $linha, array $values)
    {
	$linha->setDescricao($values['identificacao']['descricao']);
	$this->getEntityManager()->persist($linha);
    }
    
    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Armazenagem\LinhaSeparacao', $id);
	$numErros = 0;
	
	$dqlProduto = $em->createQueryBuilder()
		->select('count(p) qtty')
		->from('wms:Produto', 'p')
		->where('p.linhaSeparacao = ?1')
		->setParameter(1, $id);
	$resultSetProduto = $dqlProduto->getQuery()->execute();
	$countProduto = (integer) $resultSetProduto[0]['qtty'];
	if ($countProduto > 0) {
	    $msg .= "{$countProduto} produto(s) ";
	    $numErros++;
	}
	
	if($numErros > 0 ){
	    throw new \Exception("Não é possível remover a Linha de Separação {$proxy->getDescricao()}, 
				   há {$msg} vinculado(s).");
	}
	
	// remove
	$em->remove($proxy);
    }
    
    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
	$linhas = array();
	foreach ($this->findAll() as $linha)
	    $linhas[$linha->getId()] = $linha->getDescricao();
	return $linhas;
    }

    public function getLinhaSeparacaoByConferenciaExpedicao($codExpedicao)
    {
        $sql = "SELECT SUM(MSP.QTD_SEPARAR) QTD_SEPARAR,
                       SUM(MSC.QTD_CONFERIDA) QTD_CONFERIDA,
                       LS.DSC_LINHA_SEPARACAO,
                       LS.COD_LINHA_SEPARACAO
                FROM MAPA_SEPARACAO MS
                INNER JOIN (SELECT (SUM(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM  - NVL(MSP.QTD_CORTADO,0))) QTD_SEPARAR,
                                   MSP.COD_PRODUTO,
                                   MSP.DSC_GRADE,
                                   MS.COD_MAPA_SEPARACAO
                              FROM MAPA_SEPARACAO_PRODUTO MSP
                              INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                              WHERE MS.COD_EXPEDICAO = $codExpedicao
                              GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, MS.COD_MAPA_SEPARACAO) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                INNER JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                LEFT JOIN (SELECT MS.COD_MAPA_SEPARACAO, 
                                  MSC.COD_PRODUTO, 
                                  MSC.DSC_GRADE,
                                  SUM(MSC.QTD_CONFERIDA * MSC.QTD_EMBALAGEM) QTD_CONFERIDA
                              FROM MAPA_SEPARACAO_CONFERENCIA MSC
                              INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSC.COD_MAPA_SEPARACAO
                              WHERE MS.COD_EXPEDICAO = $codExpedicao
                              GROUP BY MS.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = P.COD_PRODUTO AND MSC.DSC_GRADE = P.DSC_GRADE
                LEFT JOIN LINHA_SEPARACAO LS ON P.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                WHERE MS.COD_EXPEDICAO = $codExpedicao
                GROUP BY LS.DSC_LINHA_SEPARACAO, LS.COD_LINHA_SEPARACAO
                HAVING SUM(MSP.QTD_SEPARAR) - SUM(MSC.QTD_CONFERIDA) = 0";

        $linhaSeparacao = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $linhas = array();
        foreach ($linhaSeparacao as $linha)
            $linhas[$linha['COD_LINHA_SEPARACAO']] = $linha['DSC_LINHA_SEPARACAO'];
        return $linhas;

    }
}
