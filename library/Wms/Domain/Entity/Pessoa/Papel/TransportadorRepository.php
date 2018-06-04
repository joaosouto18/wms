<?php

namespace Wms\Domain\Entity\Pessoa\Papel;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Pessoa\Papel\Transportador as TransportadorEntity,
    Wms\Domain\Entity\AtorRepository;

class TransportadorRepository extends AtorRepository
{

    /**
     * 
     * @param TransportadorEntity $transportadorEntity
     * @param array $values matriz de valores
     */
    public function save(TransportadorEntity &$transportadorEntity, array $values)
    {
        $em = $this->getEntityManager();

        $pessoaJuridicaEntity = $em->getRepository('wms:Pessoa\Juridica')->findOneBy(array('cnpj' => $values['pessoa']['juridica']['cnpj']));

        $transportadorEntity->setIdExterno($values['pessoa']['juridica']['idExterno'])
                ->setIsAtivo($values['pessoa']['juridica']['isAtivo']);
        
        if (!$pessoaJuridicaEntity)
            $this->persistirAtor($transportadorEntity, $values);
        else
            $transportadorEntity->setPessoa($pessoaJuridicaEntity);

        $em->persist($transportadorEntity);
        $em->flush();
    }

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
        $transportadores = array();
        foreach ($this->findAll() as $transportadorEntity) {
            $transportadores[$transportadorEntity->getId()] = $transportadorEntity->getPessoa()->getNomeFantasia();
        }
        return $transportadores;
    }

}
