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
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('ls.descricao, ls.id')
            ->from('wms:Expedicao\MapaSeparacao','ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto','msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->leftJoin('wms:Expedicao\MapaSeparacaoConferencia', 'msc', 'WITH', 'msc.mapaSeparacao = ms.id AND msc.codProduto = msp.codProduto AND msc.dscGrade = msp.dscGrade')
            ->innerJoin('msp.produto', 'p')
            ->innerJoin('p.linhaSeparacao', 'ls')
            ->where("ms.codExpedicao = $codExpedicao")
            ->groupBy('ls.descricao, ls.id')
            ->having('SUM(msp.qtdSeparar * msp.qtdEmbalagem - NVL(msp.qtdCortado,0)) - NVL(SUM(msc.qtdConferida * msc.qtdEmbalagem),0) = 0');

        $linhaSeparacao = $sql->getQuery()->getResult();

        $linhas = array();
        foreach ($linhaSeparacao as $linha)
            $linhas[$linha['id']] = $linha['descricao'];
        return $linhas;

    }
}
