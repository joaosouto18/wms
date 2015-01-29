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
            ->select('r.id Recebimento, u.login Funcionario, rd.dataVinculo Data_Vinculo, sum(np.numPeso) Peso_Total, count(p.id) N_Paletes')
            ->from('wms:Recebimento\Descarga', 'rd')
            ->innerjoin('rd.usuario', 'u')
            ->innerjoin('rd.recebimento', 'r')
            ->innerJoin('wms:Enderecamento\Palete', 'p', 'WITH', 'rd.recebimento = p.recebimento')
            ->innerJoin('wms:Produto\NormaPaletizacao', 'np', 'WITH', 'p.codNormaPaletizacao = np.id')
            ->orderBy('r.id')
            ->groupBy('r.id, u.login, rd.dataVinculo');

        if (!empty($params['operadores'])) {
            $source->andWhere('u.pessoa = :idUsuario')
                ->setParameter('idUsuario', $params['operadores']);
        }

        if (!empty($params['data'])) {
            $DataFinal1 = str_replace("/", "-", $params['data']);
            $dataF1 = new \DateTime($DataFinal1);

            $source->andWhere("TRUNC(rd.dataVinculo) = ?3")
                ->setParameter(3, $dataF1);
        }

        return $source->getQuery()->getArrayResult();
    }

}
