<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Lote as LoteEntity;


class LoteRepository extends EntityRepository
{
    public function save($produtoEntity, $grade, $dsc, $codPessoa){

        $lote = new Lote();
        $lote->setProduto($produtoEntity);
        $lote->setGrade($grade);
        $lote->setDescricao($dsc);
        $lote->setCodPessoaCriacao($codPessoa);
        $lote->setDthCriacao(new \DateTime);
        $this->_em->persist($lote);
        return $lote;
    }

    public function verificaLote($lote, $idProduto, $grade){
        return $this->findOneBy(array('descricao' => $lote['lote'], 'codProduto' => $idProduto, 'grade' => $grade));
    }
}
