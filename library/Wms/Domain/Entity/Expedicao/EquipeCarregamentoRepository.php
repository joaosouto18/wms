<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class EquipeCarregamentoRepository extends EntityRepository
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
                $enCarregamento = new EquipeCarregamento();
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

    public function realizarDescarga($expedicao)
    {
        if (empty($expedicao)) {
            throw new \Exception("Recebimento não informado");
        }
        //Se a descarga não é setada então não é realizada( return false)
        if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'N') {
            return false;
        }
        $enEquipeVinculada = $this->findBy(array('expedicao' => $expedicao));
        if ($enEquipeVinculada) {
            return false;
        }

        return true;
    }

}
