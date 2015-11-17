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

            $enDescarga = $this->findBy(array('expedicao' => $expedicao));
            foreach ($enDescarga as $value) {
                $em->remove($value);
            }

            foreach($operadores as $idOperador) {
                $entityUsuario  = $usuarioRepo->findOneBy(array('pessoa' => $idOperador));
                $enCarregamento = new EquipeExpedicaoTransbordo();
                $enCarregamento->setDataVinculo(new \DateTime());
                $enCarregamento->setExpedicao($entityExpedicao);
                $enCarregamento->setUsuario($entityUsuario);
                $enCarregamento->setPlaca(strtoupper($placa));
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
