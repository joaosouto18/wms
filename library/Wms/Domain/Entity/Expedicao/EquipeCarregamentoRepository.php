<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class EquipeCarregamentoRepository extends EntityRepository
{
    public function vinculaOperadores($expedicao, array $operadores, $placa, $dthFinal = false)
    {
        $em = $this->_em;
        $em->beginTransaction();
        try {
            $expedicaoRepo        = $em->getRepository('wms:Expedicao');
            $entityExpedicao      = $expedicaoRepo->findOneBy(array('id' => $expedicao));
            $usuarioRepo            = $em->getRepository('wms:Usuario');

            $equipeCarregamentoEntities = $this->findBy(array('expedicao' => $expedicao));

            foreach ($operadores as $key => $operador) {
                $entityUsuario = $usuarioRepo->findOneBy(array('pessoa' => $operador));
                $existeCadastro = false;
                foreach ($equipeCarregamentoEntities as $keyOperador => $equipeCarregamentoEntity) {
                    if ($equipeCarregamentoEntity->getUsuario() == $entityUsuario) {
                        $usuarioFechamentoProdutividade = $equipeCarregamentoEntity;
                        $existeCadastro = true;
                        break;
                    }
                }
                if (!$dthFinal && !$existeCadastro) {
                    $entityCarregamento = new EquipeCarregamento();
                    $entityCarregamento->setDataVinculo(new \DateTime());
                    $entityCarregamento->setExpedicao($entityExpedicao);
                    $entityCarregamento->setUsuario($entityUsuario);
                    $em->persist($entityCarregamento);
                }

                if ($dthFinal && $existeCadastro) {
                    $usuarioFechamentoProdutividade->setDataFim(new \DateTime());
                }

                if ($dthFinal && !$existeCadastro) {
                    throw new \Exception($entityUsuario->getPessoa()->getNome() . ' não foi vinculado(a) no inicio do carregamento.');
                }
            }

            foreach ($equipeCarregamentoEntities as $equipeCarregamentoEntity) {
                $mantemCadastrado = false;
                foreach ($operadores as $operador) {
                    $entityUsuario = $usuarioRepo->findOneBy(array('pessoa' => $operador));
                    if ($equipeCarregamentoEntity->getUsuario() == $entityUsuario) {
                        $mantemCadastrado = true;
                        break;
                    }
                }
                if (!$mantemCadastrado) {
                    $em->remove($equipeCarregamentoEntity);
                }
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

    public function getEquipeCarregamento($params)
    {
        if (!empty($params['idExpedicao']) || !empty($params['pessoa'])) {
            $sql = $this->getEntityManager()->createQueryBuilder()
                ->select('ec.id equipe, ec.dataVinculo, e.id expedicao, pf.nome')
                ->from('wms:Expedicao\EquipeCarregamento', 'ec')
                ->innerJoin('ec.expedicao', 'e')
                ->innerJoin('ec.usuario', 'u')
                ->innerJoin('u.pessoa', 'pf');
            if (!empty($params['idExpedicao'])) {
                $sql->andWhere("e.id = $params[idExpedicao]");
            }
            if (!empty($params['pessoa'])) {
                $sql->andWhere("pf.id = $params[pessoa]");
            }

            return $sql->getQuery()->getResult();
        }
        return [] ;
    }

}
