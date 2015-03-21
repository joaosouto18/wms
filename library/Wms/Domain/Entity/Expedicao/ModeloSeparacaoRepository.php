<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class ModeloSeparacaoRepository extends EntityRepository
{

    public function getModelos() {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->orderBy("m.id");

        return $source->getQuery()->getArrayResult();
       // print_r($source->getQuery()->getArrayResult());die();
    }

    public function getQuebras($modelos) {
        $resultado=array();
        $cont=0;
        foreach ($modelos as $chvModelo=>$vlrModelo){
             $source = $this->getEntityManager()->createQueryBuilder()
            ->select('tqFr.id,tqFr.tipoQuebra')
            ->from('wms:Expedicao\ModeloSeparacao', 'm')
            ->innerJoin('m.tiposQuebraFracionado', 'tqFr')
            ->innerJoin('m.tiposQuebraNaoFracionado', 'tqNFr')
             ->where('m.id='.$vlrModelo['id'])
            ->orderBy("m.id");
            $resultado[$cont]['frac']=$source->getQuery()->getArrayResult();

            $source = $this->getEntityManager()->createQueryBuilder()
                ->select('tqNFr.id, tqNFr.tipoQuebra')
                ->from('wms:Expedicao\ModeloSeparacao', 'm')
                ->innerJoin('m.tiposQuebraFracionado', 'tqFr')
                ->innerJoin('m.tiposQuebraNaoFracionado', 'tqNFr')
                ->where('m.id='.$vlrModelo['id'])
                ->orderBy("m.id");

            $resultado[$cont]['nfrac']=$source->getQuery()->getArrayResult();
            $cont++;
        }

        return $source->getQuery()->getArrayResult();
        // print_r($source->getQuery()->getArrayResult());die();
    }

}

