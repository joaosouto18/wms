<?php
/**
 * Created by PhpStorm.
 * User: tarci
 * Date: 29/06/2017
 * Time: 13:06
 */

namespace Wms\Service;


use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\NotaFiscalSaida;
use Wms\Domain\Entity\Pessoa\Juridica;

class ExpedicaoService extends AbstractService
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
        $this->entity = "wms:Expedicao";
    }

    public function createCargaReentrega(NotaFiscalSaida $nfSaida) {
        $wsExpedicao = new \Wms_WebService_Expedicao();
        /** @var Juridica $pessoa */
        $numNFS = $nfSaida->getNumeroNf();
        $serieNFS = $nfSaida->getSerieNf();

        /** @var Expedicao $expedicaoEn */
        $expedicaoEn = $this->em->getRepository($this->entity)->save($numNFS);
        $tipoCarga = $this->em->getRepository('wms:Util\Sigla')->findOneBy(array('tipo' => 69,'referencia'=> "C"));

        $enCarga = new Expedicao\Carga();
        $enCarga->setPlacaExpedicao($numNFS);
        $enCarga->setCentralEntrega(1);
        $enCarga->setCodCargaExterno(trim($numNFS));
        $enCarga->setExpedicao($expedicaoEn);
        $enCarga->setPlacaCarga($numNFS);
        $enCarga->setTipoCarga($tipoCarga);
        $enCarga->setSequencia(1);
        $enCarga->setDataFechamento(new \DateTime());
        $this->em->persist($enCarga);
        $this->em->flush($enCarga);

        $pessoa = $this->em->find("wms:Pessoa\Juridica", $nfSaida->getCodPessoa());
        $wsExpedicao->definirReentrega($pessoa->getCnpj(), $numNFS, $serieNFS, $numNFS,"C");


    }

}