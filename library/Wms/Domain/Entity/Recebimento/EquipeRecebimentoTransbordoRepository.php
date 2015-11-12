<?php
namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class EquipeRecebimentoTransbordoRepository extends EntityRepository
{
    public function vinculaOperadores($expedicao, array $operadores)
    {
        $em = $this->_em;
        $em->beginTransaction();
        try {
            $expedicaoRepo        = $em->getRepository('wms:Expedicao');
            $entityExpedicao      = $expedicaoRepo->findOneBy(array('id' => $expedicao));
            $usuarioRepo            = $em->getRepository('wms:Usuario');

            foreach($operadores as $idOperador) {
                $enDescarga = $this->findBy(array('expedicao' => $expedicao, 'usuario' => $idOperador));
                if ($enDescarga) {
                    continue;
                }

                $entityUsuario          = $usuarioRepo->findOneBy(array('pessoa' => $idOperador));
                $enCarregamento = new EquipeRecebimentoTransbordo();
                $enCarregamento->setDataVinculo(new \DateTime());
                $enCarregamento->setExpedicao($entityExpedicao);
                $enCarregamento->setUsuario($entityUsuario);
                $em->persist($enCarregamento);
            }
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
    }

}
