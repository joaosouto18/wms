<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;

class AndamentoRepository extends EntityRepository
{
    public function save($idOndaOs, $tipo, $observacao = "")
    {
        $usuarioId = \Zend_Auth::getInstance()->getIdentity()->getId();

        $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);
        $siglaEn = $this->_em->getRepository('wms:Util\Sigla')->findOneBy(array('id'=>$tipo));


        $andamento = new Andamento();
        $andamento->setUsuario($usuario);
        $andamento->setDataAndamento(new \DateTime);
        $andamento->setTipo($siglaEn);
        $andamento->setDscObservacao($observacao);
        $andamento->setCodOndaRessuprimentoOs($idOndaOs);

        $this->_em->persist($andamento);
    }

}
