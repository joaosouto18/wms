<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoEmbaladoRepository extends EntityRepository
{

    public function save($idMapa,$codPessoa,$mapaSeparacaoEmbalado=null)
    {
        $pessoaEn = $this->getEntityManager()->getReference('wms:Pessoa',$codPessoa);
        $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$idMapa);
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO);
        $mapaSeparacaoEmbalado = new MapaSeparacaoEmbalado();
        $mapaSeparacaoEmbalado->setMapaSeparacao($mapaSeparacaoEn);
        $mapaSeparacaoEmbalado->setPessoa($pessoaEn);
        $mapaSeparacaoEmbalado->setSequencia($mapaSeparacaoEmbalado->getSequencia() + 1);
        $mapaSeparacaoEmbalado->setStatus($siglaEn);


        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        $this->getEntityManager()->flush();
    }

    public function fecharMapaSeparacaoEmbalado($idMapa,$idPessoa,$mapaSeparacaoEmbaladoRepo)
    {

        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('mapaSeparacao' => $idMapa, 'pessoa' => $idPessoa, 'status' => Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO));
        if (!isset($mapaSeparacaoEmbaladoEn) || empty($mapaSeparacaoEmbaladoEn)) {
            throw new \Exception('Não existe conferencia de embalados em aberto para esse Cliente!');
        }

        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO);
        $mapaSeparacaoEmbaladoEn->setStatus($siglaEn);

        $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
        $this->getEntityManager()->flush();
    }
}

