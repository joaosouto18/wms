<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class EtiquetaMaeRepository extends EntityRepository
{

    /**
     * @param array $dadosEtiqueta
     * @return int
     * @throws \Exception
     */
    protected function save(array $dadosEtiquetaMae,$idExpedicao)
    {
        $enEtiquetaMae = new EtiquetaMae();

        /** @var \Wms\Domain\Entity\Expedicao $ExpedicaoRepo */
        $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        $expedicao=$ExpedicaoRepo->find($idExpedicao);

        $enEtiquetaMae->setExpedicao($expedicao);
        \Zend\Stdlib\Configurator::configure($enEtiquetaMae, $dadosEtiquetaMae);

        $this->_em->persist($enEtiquetaMae);

        return $enEtiquetaMae->getId();
    }

    /**
     * @param array $dadosEtiqueta
     * @return int
     * @throws \Exception
     */
    protected function saveQuebra(array $dadosEtiquetaQuebra,$idEtiquetaMae)
    {
        $enEtiquetaMaeQuebra = new EtiquetaMaeQuebra();

        $enEtiquetaMaeQuebra->setIndTipoQuebra($dadosEtiquetaQuebra['tipoQuebra']);
        $enEtiquetaMaeQuebra->setCodQuebra($dadosEtiquetaQuebra['codQuebra']);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaMae $EtiquetaMaeRepo */
        $EtiquetaMaeRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaMae');
        $etiquetaMae=$EtiquetaMaeRepo->find($idEtiquetaMae);

        $enEtiquetaMaeQuebra->setEtiquetaMae($etiquetaMae);

        \Zend\Stdlib\Configurator::configure($enEtiquetaMaeQuebra, $dadosEtiquetaQuebra);

        $this->_em->persist($enEtiquetaMaeQuebra);

        return $enEtiquetaMaeQuebra->getId();
    }

    /**
     * @param array $quebras
     * @param $idExpedicao COD_EXPEDICAO
     * @DEF Tipo 1 -> Fracionado CodQuebra 2 -> Não Fracionado
     * @return int
     */
    public function gerarEtiquetaMae($quebras,$tipoFracao,$idExpedicao,$dscEtiqueta){
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');

        $sql="
            INSERT INTO ETIQUETA_MAE
                (
                    COD_ETIQUETA_MAE,
                    COD_EXPEDICAO,
                    DSC_QUEBRA
                 )
                 VALUES (
                 SQ_ETIQUETA_MAE_01.NEXTVAL,
                  ".$idExpedicao.",
                  '".$dscEtiqueta."'
                 )
                 ";
        $result = $this->getEntityManager()->getConnection()->query($sql);

        $sql="
            SELECT COD_ETIQUETA_MAE FROM ETIQUETA_MAE WHERE COD_EXPEDICAO=".$idExpedicao." AND DSC_QUEBRA='".$dscEtiqueta."'
                 ";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchall(\PDO::FETCH_ASSOC);
        $codEtiquetaMae=$result[0]['COD_ETIQUETA_MAE'];

        foreach ($quebras as $chv => $vlr){
            if ( !empty($tipoFracao[0]["TIPO"]) && $tipoFracao[0]["TIPO"]=="1" ) {
                $fracionados=$vlr['frac'];

                foreach ($fracionados as $chvFrac => $vlrFrac){

                    $sql="INSERT INTO ETIQUETA_MAE_QUEBRA
                          (
                              COD_ETIQUETA_MAE_QUEBRA,
                              IND_TIPO_QUEBRA,
                              COD_QUEBRA,
                              COD_ETIQUETA_MAE,
                              TIPO_FRACAO
                          )
                           VALUES (
                                SQ_ETIQUETA_MAE_QUEBRA_01.NEXTVAL,
                               '".$vlrFrac['tipoQuebra']."',
                               ".$ExpedicaoRepo->getCodQuebra($tipoFracao,$vlrFrac['tipoQuebra']).",
                               ".$codEtiquetaMae.",
                               'FRACIONADOS'

                           )";

                    $result = $this->getEntityManager()->getConnection()->query($sql);
                }


            } else {
                $naofracionados=$vlr['frac'];

                foreach ($naofracionados as $chvNFrac => $vlrNFrac){

                    $sql="INSERT INTO ETIQUETA_MAE_QUEBRA
                          (
                          COD_ETIQUETA_MAE_QUEBRA,
                          IND_TIPO_QUEBRA,
                          COD_QUEBRA,
                          COD_ETIQUETA_MAE,
                          TIPO_FRACAO
                          )
                           VALUES (
                                SQ_ETIQUETA_MAE_QUEBRA_01.NEXTVAL,
                               '".$vlrNFrac['tipoQuebra']."',
                               ".$ExpedicaoRepo->getCodQuebra($tipoFracao,$vlrNFrac['tipoQuebra']).",
                               ".$codEtiquetaMae.",
                               'NAOFRACIONADOS'

                           )";

                    $result = $this->getEntityManager()->getConnection()->query($sql);
                }

            }


        }

        return $codEtiquetaMae;
    }

}