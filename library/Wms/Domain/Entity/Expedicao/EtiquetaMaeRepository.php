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
     * @DEF CodQuebra 1 -> Fracionado CodQuebra 2 -> Não Fracionado
     * @return int
     */
    public function gerarEtiquetasMae(array $quebras,$idExpedicao)
    {
        $cont=0;

        foreach ($quebras as $chvQuebras =>$vlrQuebras ){

            $dadosEtiquetaMae['dscQuebra']="Etiqueta Mae ".$cont;
            $dadosEtiquetaMae['codExpedicao']=$idExpedicao;

            $etiquetaMae=$this->save($dadosEtiquetaMae,$idExpedicao);

            //fracionados
            foreach ($vlrQuebras['frac'] as $vlrFracionado){

                $dadosEtiquetaQuebra['tipoQuebra']=$vlrFracionado['tipoQuebra'];
                $dadosEtiquetaQuebra['codQuebra']=1;
                $dadosEtiquetaQuebra['codEtiquetaMae']=$etiquetaMae;
                $fracionado=$this->saveQuebra($dadosEtiquetaQuebra,$etiquetaMae);
            }

            //não fracionados
            foreach ($vlrQuebras['nfrac'] as $vlrNFracionado){
                $dadosEtiquetaQuebra['tipoQuebra']=$vlrNFracionado['tipoQuebra'];
                $dadosEtiquetaQuebra['codQuebra']=2;
                $dadosEtiquetaQuebra['codEtiquetaMae']=$etiquetaMae;
                $nfracionado=$this->saveQuebra($dadosEtiquetaQuebra,$etiquetaMae);
            }

        }

        $this->_em->flush();
        $this->_em->clear();

        return true;

    }

}