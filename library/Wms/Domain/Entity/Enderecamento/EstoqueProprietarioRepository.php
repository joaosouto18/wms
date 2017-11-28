<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 27/11/2017
 * Time: 16:03
 */
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class EstoqueProprietarioRepository extends EntityRepository
{
    public function save($produtoEn, $qtd, $operacao, $codPessoa){
        $estoqueProprietario = new EstoqueProprietario();
        $estoqueProprietario->setCodProduto($produtoEn->getId());
        $estoqueProprietario->setGrade($produtoEn->getGrade());
        $estoqueProprietario->setQtd($qtd);
        $estoqueProprietario->setProduto($produtoEn);
        $estoqueProprietario->setCodPessoa($codPessoa);
        $estoqueProprietario->setOperacao($operacao);
        $estoqueProprietario->setDthOperacao(new \DateTime);

        $this->_em->persist($estoqueProprietario);
        $this->_em->flush();
    }

    public function verificaProprietarioExistente($cnpj){
        $cnpj = str_replace(array('.','-','/'),'',$cnpj);
        return $this->getEntityManager()->getRepository('wms:Pessoa\Juridica')->findOneBy(array('cnpj' => $cnpj));
    }
}