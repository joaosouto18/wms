<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaConferencia;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class EquipeSeparacaoRepository extends EntityRepository
{

    public function save($etiquetaInicial,$etiquetaFinal,$usuarioEn)
    {
        $equipeSeparacao = new Expedicao\EquipeSeparacao();
        $equipeSeparacao->setCodUsuario($usuarioEn->getId());
        $equipeSeparacao->setDataVinculo(new \DateTime());
        $equipeSeparacao->setEtiquetaInicial($etiquetaInicial);
        $equipeSeparacao->setEtiquetaFinal($etiquetaFinal);
        $this->getEntityManager()->persist($equipeSeparacao);
        $this->getEntityManager()->flush();
    }

}