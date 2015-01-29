<?php
namespace Wms\Domain\Entity\Sistema\Recurso;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Sistema\Recurso\Auditoria as AuditoriaEntity;

class AuditoriaRepository extends EntityRepository
{
    /**
     *
     * @param int $recursoId Id do recurso a ser gravado
     * @param string $descricao Descricao a ser gravada na base
     */
    public static function save($recursoId, $descricao)
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        
        //Busca Codigo da Filial
        $sessao = new \Zend_Session_Namespace('deposito');
        
        $usuarioId = \Zend_Auth::getInstance()->getIdentity()->getId();
        
        $usuario = $em->getReference('wms:Usuario', (int) $usuarioId);
        $recurso = $em->getReference('wms:Sistema\Recurso', (int) $recursoId);
	$deposito = $em->getReference('wms:Deposito', (int) $sessao->idDepositoLogado);
        $filial = $em->getReference('wms:Filial', (int) $deposito->getIdFilial());

        $auditoriaEntity = new AuditoriaEntity;
        $auditoriaEntity->setUsuario($usuario)
                ->setFilial($filial)
                ->setRecurso($recurso)
                ->setDscOperacao($descricao)
                ->setDatOperacao(new \DateTime);
        
        $em->persist($auditoriaEntity);
    }

}