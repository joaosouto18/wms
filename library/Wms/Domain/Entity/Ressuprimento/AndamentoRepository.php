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

    public function getAndamentoRessuprimento($idOndaOs)
    {
        $sql = "SELECT ra.NUM_SEQUENCIA as CODIGO, p.NOM_PESSOA as NOME, ra.DSC_OBSERVACAO as OBS, ra.DTH_ANDAMENTO as DATA, s.DSC_SIGLA as SIGLA
                  FROM RESSUPRIMENTO_ANDAMENTO ra
                INNER JOIN PESSOA p ON p.COD_PESSOA = ra.COD_USUARIO
                INNER JOIN SIGLA s ON ra.COD_TIPO = s.COD_SIGLA
                WHERE COD_ONDA_RESSUPRIMENTO_OS = $idOndaOs
                ORDER BY ra.NUM_SEQUENCIA";

        $result = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

}
