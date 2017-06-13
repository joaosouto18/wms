<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaConferencia;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class EquipeSeparacaoRepository extends EntityRepository
{

    public function save($etiquetaInicial,$etiquetaFinal,$usuarioEn, $save = true)
    {
        $equipeSeparacao = new Expedicao\EquipeSeparacao();
        $equipeSeparacao->setCodUsuario($usuarioEn->getId());
        $equipeSeparacao->setDataVinculo(new \DateTime());
        $equipeSeparacao->setEtiquetaInicial($etiquetaInicial);
        $equipeSeparacao->setEtiquetaFinal($etiquetaFinal);
        $this->getEntityManager()->persist($equipeSeparacao);

        if($save===true)
            $this->getEntityManager()->flush();
    }

    /**
     * Retorna os intervalos das Etiquetas do Usuário
     * @param $usuarioEn EquipeSeparacao
     *
     * @return array
     */
    public function getIntervaloEtiquetaUsuario($usuarioEn) {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select("es.etiquetaInicial, es.etiquetaFinal")
            ->from("wms:Expedicao\EquipeSeparacao","es")
            ->where("es.codUsuario = :codUsuario ")
            ->addOrderBy("es.etiquetaInicial", "ASC")
            ->setParameter('codUsuario', $usuarioEn->getId());

        return $sql->getQuery()->getResult();
    }
}
