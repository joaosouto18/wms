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
    public function save($codProduto, $grade, $qtd, $operacao, $codPessoa){
        $estoqueProprietario = new EstoqueProprietario();
        $estoqueProprietario->setCodProduto($codProduto);
        $estoqueProprietario->setGrade($grade);
        $estoqueProprietario->setQtd($qtd);
        $estoqueProprietario->setCodPessoa($codPessoa);
        $estoqueProprietario->setOperacao($operacao);
        $estoqueProprietario->setDthOperacao(new \DateTime);

        $this->_em->persist($estoqueProprietario);
        $this->_em->flush();
    }

    public function efetivaEstoquePropRecebimento($idRecebimento){
        $nfRepository = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $nfVetEntity = $nfRepository->findBy(array('recebimento' => $idRecebimento));
        if(!empty($nfVetEntity)){
            foreach ($nfVetEntity as $nf){
                $itemsNF = $nfRepository->getConferencia($nf->getFornecedor()->getId(), $nf->getNumero(), $nf->getSerie(), '', $nf->getStatus()->getId());
                if(!empty($itemsNF)){
                    foreach ($itemsNF as $itens){
                        $this->save($itens['COD_PRODUTO'], $itens['DSC_GRADE'],$itens['QTD_CONFERIDA'], EstoqueProprietario::RECEBIMENTO, $nf->getCodPessoa());
                    }
                }
        var_dump($itemsNF);
            }
        }
    }

    public function verificaProprietarioExistente($cnpj){
        $cnpj = str_replace(array('.','-','/'),'',$cnpj);
        return $this->getEntityManager()->getRepository('wms:Pessoa\Juridica')->findOneBy(array('cnpj' => $cnpj));
    }
}