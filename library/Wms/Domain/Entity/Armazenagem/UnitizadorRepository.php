<?php

namespace Wms\Domain\Entity\Armazenagem;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Armazenagem\Unitizador as UnitizadorEntity;

class UnitizadorRepository extends EntityRepository
{

    /**
     * Salva o registro no banco
     * @param Unitizador $tipo
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(UnitizadorEntity $tipo, array $values)
    {
        $tipo->setDescricao($values['identificacao']['descricao']);
        $tipo->setLargura($values['identificacao']['largura']);
        $tipo->setAltura($values['identificacao']['altura']);
        $tipo->setProfundidade($values['identificacao']['profundidade']);
        $tipo->setArea($values['identificacao']['area']);
        $tipo->setCubagem($values['identificacao']['cubagem']);
        $tipo->setCapacidade($values['identificacao']['capacidade']);
        $tipo->setQtdOcupacao($values['identificacao']['qtdOcupacao']);

        $this->getEntityManager()->persist($tipo);
    }

    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Armazenagem\Unitizador', $id);

        // remove
        $em->remove($proxy);
    }

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
        $unitizadores = array();
        foreach ($this->findAll() as $unitizador)
            $unitizadores[$unitizador->getId()] = $unitizador->getDescricao();
        return $unitizadores;
    }

    /**
     * Retorna id e descricao do unitizador via array associativo
     * 
     * @return array
     */
    public function getIdDescricaoAssoc()
    {
        $unitizadores = array();
        foreach ($this->findAll() as $unitizador)
            $unitizadores[] = array (
            'id'        => $unitizador->getId (),
            'descricao' => $unitizador->getDescricao(),
        );
        return $unitizadores;
    }

}
