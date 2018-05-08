<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Lote as LoteEntity;


class LoteRepository extends EntityRepository
{
    public function save($produtoEntity, $grade, $dsc, $codPessoa = null){

        $lote = new Lote();
        $lote->setProduto($produtoEntity);
        $lote->setGrade($grade);
        $lote->setDescricao($dsc);
        $lote->setCodPessoaCriacao($codPessoa);
        $lote->setDthCriacao(new \DateTime);
        $this->_em->persist($lote);
    }
}
