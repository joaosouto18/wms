<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;
use Wms\Module\Expedicao\Report\EtiquetaVolume;

class VolumePatrimonioRepository extends EntityRepository
{

    public function salvarSequencia ($codigoInicial, $codigoFinal, $descricao) {
        $em = $this->getEntityManager();
        for ($i = $codigoInicial; $i <= $codigoFinal; $i++) {

            /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonio $volumeEn */
            $volumeEn = $this->findOneBy(array('id'=>$i));
            if ($volumeEn == null) {
                $volumeEn = new VolumePatrimonio();
                $volumeEn->setDescricao($descricao);
                $volumeEn->setId($i);
                $volumeEn->setOcupado("N");
            } else {
                $volumeEn->setDescricao($descricao);
            }

            $em->persist($volumeEn);
        }

        $em->flush();
    }

    public function getExpedicaoByVolume($idVolumePatrimonio, $returnType = "str")
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('v.id , e.id as expedicao, e.dataInicio, v.tipoVolume')
            ->from('wms:Expedicao\ExpedicaoVolumePatrimonio', 'v')
            ->leftJoin("v.expedicao",'e')
            ->where("v.volumePatrimonio = $idVolumePatrimonio")
            ->orderBy("v.id","DESC");
        $result = $source->getQuery()->getArrayResult();

        if ($result == NULL) {
            return 0;
        }

        $cargas = "C:";
        foreach ($result as $line) {
            if ($cargas != "C:") $cargas = $cargas . ", ";
            $cargas = $cargas . $line['tipoVolume'];
        }
        $stringResult = "Exp: " . $result[0]['expedicao'] . " - " . $result[0]['dataInicio']->format('d/m/y') . " - " . $cargas;

        if ($returnType == "str") {
            return $stringResult;
        } else {
            return $result;
        }
    }

    public function getVolumes($codigoInicial = null, $codigoFinal = null, $descricao = null, $showExpedicao = false) {
        $source = $this->getEntityManager()->createQueryBuilder()
        ->select('v.id , v.descricao, v.ocupado')
        ->from('wms:Expedicao\VolumePatrimonio', 'v')
        ->orderBy("v.id");

        if (isset($codigoInicial) && $codigoInicial > 0) {
            $source->andWhere("v.id >= :codigoInicial")
            ->setParameter('codigoInicial', $codigoInicial);
        }
        if (isset($codigoFinal) && $codigoFinal > 0) {
            $source->andWhere("v.id <= :codigoFinal")
                ->setParameter('codigoFinal', $codigoFinal);
        }
        if (isset($descricao) && $descricao != ""){
            $source->andWhere("v.descricao LIKE :descricao")
                ->setParameter('descricao', '%'.$descricao.'%');
        }

        $result = $source->getQuery()->getArrayResult();

        if ($showExpedicao == true){
            $newResult = array();
            foreach ($result as $volume) {
                $volume['expedicao'] = "";
                if ($volume['ocupado'] == 'S') {
                    $volume['expedicao'] = $this->getExpedicaoByVolume($volume['id']);
                }

                $newVolume = array();
                $newVolume['id'] = $volume['id'];
                $newVolume['descricao'] = $volume['descricao'];
                $newVolume['ocupado'] = $volume['ocupado'];
                $newVolume['expedicao'] = $volume['expedicao'];
                $newResult[] = $newVolume;
            }
            return $newResult;
        }

        return $result;
    }

    public function imprimirFaixa($codigoInicial,$codigoFinal)
    {
        $values = $this->getVolumes($codigoInicial,$codigoFinal,null);
        $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 50));
        $result = $gerarEtiqueta->init($values);

    }

    public function imprimirRelatorio()
    {

        $dql = "SELECT v0_.COD_VOLUME_PATRIMONIO, v0_.DSC_VOLUME_PATRIMONIO, MAX(e1_.COD_EXPEDICAO) AS CodExpedicao, (select MAX(e2_.DTH_FECHAMENTO) from EXPEDICAO_VOLUME_PATRIMONIO e2_ where e2_.COD_EXPEDICAO = e1_.COD_EXPEDICAO) AS data
                FROM VOLUME_PATRIMONIO v0_
                INNER JOIN EXPEDICAO_VOLUME_PATRIMONIO e1_ ON (e1_.COD_VOLUME_PATRIMONIO = v0_.COD_VOLUME_PATRIMONIO)
                GROUP BY v0_.COD_VOLUME_PATRIMONIO, v0_.DSC_VOLUME_PATRIMONIO, e1_.COD_EXPEDICAO
                ORDER BY v0_.COD_VOLUME_PATRIMONIO DESC";

        $array = $this->getEntityManager()->getConnection()->query($dql)->fetchAll(\PDO::FETCH_ASSOC);

        return $array;

    }

}