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
        $dql = "SELECT DISTINCT
                   EVP.COD_VOLUME_PATRIMONIO,
                   VP.DSC_VOLUME_PATRIMONIO,
                   EVP.COD_EXPEDICAO,
                   ULTSAIDA.QTD_SAIDAS,
                   TO_CHAR(EVP.DTH_FECHAMENTO,'DD/MM/YYYY HH24:MI:SS') as DATA_SAIDA,
                   TO_CHAR(EVP.DTH_CONFERIDO,'DD/MM/YYYY HH24:MI:SS') as DATA_CONFERENCIA

              FROM EXPEDICAO_VOLUME_PATRIMONIO EVP
             INNER JOIN (SELECT MAX(DTH_FECHAMENTO) as ULTIMA_SAIDA,
                                COUNT(DISTINCT (COD_EXPEDICAO)) as QTD_SAIDAS,
                                COD_VOLUME_PATRIMONIO
                           FROM EXPEDICAO_VOLUME_PATRIMONIO
                          GROUP BY COD_VOLUME_PATRIMONIO) ULTSAIDA
                ON ULTSAIDA.COD_VOLUME_PATRIMONIO = EVP.COD_VOLUME_PATRIMONIO
               AND ULTSAIDA.ULTIMA_SAIDA = DTH_FECHAMENTO
              LEFT JOIN VOLUME_PATRIMONIO VP ON VP.COD_VOLUME_PATRIMONIO = EVP.COD_VOLUME_PATRIMONIO
              ORDER BY EVP.COD_VOLUME_PATRIMONIO";

        $array = $this->getEntityManager()->getConnection()->query($dql)->fetchAll(\PDO::FETCH_ASSOC);

        return $array;

    }

    public function getVolumesByExpedicao($idExpedicao)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('vp.id volume, vp.descricao, e.id as expedicao, p.nome as quebra, p.id')
            ->from('wms:Expedicao\VolumePatrimonio', 'vp')
            ->innerJoin('wms:Expedicao\ExpedicaoVolumePatrimonio', 'evp', 'WITH', 'evp.volumePatrimonio = vp.id')
            ->innerJoin('wms:Pessoa\Papel\cliente', 'c', 'WITH', 'evp.tipoVolume = c.codClienteExterno')
            ->innerJoin('c.pessoa', 'p')
            ->innerJoin('evp.expedicao', 'e')
            ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'c.expedicao = e.id')
            ->innerJoin('wms:Expedicao\Pedido', 'p', 'WITH', 'p.carga = c.id')
            ->where("evp.expedicao = $idExpedicao")
        ;
       return $sql->getQuery()->getResult();
    }

}