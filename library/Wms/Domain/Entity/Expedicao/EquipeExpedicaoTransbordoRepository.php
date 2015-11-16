<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class EquipeExpedicaoTransbordoRepository extends EntityRepository
{
    public function vinculaOperadores($expedicao, array $operadores, $placa)
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

                $entityUsuario  = $usuarioRepo->findOneBy(array('pessoa' => $idOperador));
                $enCarregamento = new EquipeExpedicaoTransbordo();
                $enCarregamento->setDataVinculo(new \DateTime());
                $enCarregamento->setExpedicao($entityExpedicao);
                $enCarregamento->setUsuario($entityUsuario);
                $enCarregamento->setPlaca($placa);
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
