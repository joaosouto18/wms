<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class EquipeExpedicaoTransbordoRepository extends EntityRepository
{
    public function vinculaOperadores($idcarga, array $operadores)
    {
        $em = $this->_em;
        $em->beginTransaction();
        try {
            $cargaRepo              = $em->getRepository('wms:Expedicao\Carga');
            $entityCarga            = $cargaRepo->findOneBy(array('codCargaExterno' => $idcarga));
            $usuarioRepo            = $em->getRepository('wms:Usuario');

            foreach($operadores as $idOperador) {
                $enDescarga = $this->findBy(array('carga' => $idcarga, 'usuario' => $idOperador));
                if ($enDescarga) {
                    continue;
                }

                $entityUsuario          = $usuarioRepo->findOneBy(array('pessoa' => $idOperador));
                $enCarregamento = new EquipeExpedicaoTransbordo();
                $enCarregamento->setDataVinculo(new \DateTime());
                $enCarregamento->setCarga($entityCarga);
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
