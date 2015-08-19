<?php
namespace Wms\Domain\Entity\Recebimento;

use Doctrine\ORM\EntityRepository;

class DescargaRepository extends EntityRepository
{
    public function vinculaOperadores($recebimento, array $operadores)
    {
        $em = $this->_em;
        $em->beginTransaction();
        try {
            $recebimentoRepo        = $em->getRepository('wms:Recebimento');
            $entityRecebimento      = $recebimentoRepo->findOneBy(array('id' => $recebimento));
            $usuarioRepo            = $em->getRepository('wms:Usuario');

            foreach($operadores as $idOperador) {
                $enDescarga = $this->findBy(array('recebimento' => $recebimento, 'usuario' => $idOperador));
                if ($enDescarga) {
                    continue;
                }

                $entityUsuario          = $usuarioRepo->findOneBy(array('pessoa' => $idOperador));
                $enDescarga = new Descarga();
                $enDescarga->setDataVinculo(new \DateTime());
                $enDescarga->setRecebimento($entityRecebimento);
                $enDescarga->setUsuario($entityUsuario);
                $em->persist($enDescarga);
            }
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function realizarDescarga($recebimento)
    {
        if (empty($recebimento)) {
            throw new \Exception("Recebimento não informado");
        }
        //Se a descarga não é setada então não é realizada( return false)
        if ($this->getSystemParameterValue('DESCARGA_RECEBIMENTO') == 'N') {
            return false;
        }
        $enDescarga = $this->findBy(array('recebimento' => $recebimento));
        if ($enDescarga) {
            return false;
        }

        return true;
    }

    public function getInfosDescarga($params)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('r.id Recebimento, u.login Funcionario, rd.dataVinculo Data_Vinculo, count(p.id) N_Paletes')
            ->from('wms:Recebimento\Descarga', 'rd')
            ->innerjoin('rd.usuario', 'u')
            ->innerjoin('rd.recebimento', 'r')
            ->innerJoin('wms:Enderecamento\Palete', 'p', 'WITH', 'r.id = p.recebimento')
            ->orderBy('r.id')
            ->groupBy('r.id, u.login, rd.dataVinculo');

        if (!empty($params['operadores'])) {
            $source->andWhere('u.pessoa = :idUsuario')
                ->setParameter('idUsuario', $params['operadores']);
        }

        if (!empty($params['dataInicio']) && !empty($params['dataFim'])) {
            $dataInicio = str_replace('/','-',$params['dataInicio']);
            $dataInicio = new \DateTime($dataInicio);
            $dataInicio = $dataInicio->format('Y-m-d');

            $dataFim = str_replace('/','-',$params['dataFim']);
            $dataFim = new \DateTime($dataFim);
            $dataFim = $dataFim->format('Y-m-d');

            $source->andWhere("TRUNC(rd.dataVinculo) between '$dataInicio' AND '$dataFim'");
        }

        return $source->getQuery()->getResult();
    }

}
