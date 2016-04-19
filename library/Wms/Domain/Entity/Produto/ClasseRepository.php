<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Fabricante as FabricanteEntity,
    Doctrine\Common\Persistence\ObjectRepository;


class ClasseRepository extends EntityRepository
{

    public function save($idClasse, $nome, $idClassePai = null)
    {
        $idClasse = trim($idClasse);
        $nome = trim($nome);

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $classeEn = $em->getRepository('wms:Produto\Classe')->findOneBy(array('id' => $idClasse));
            if (!$classeEn) {
                $classeEn = new Classe();
                $classeEn->setNome($nome);
                $classeEn->setId($idClasse);
                $classeEn->setIdPai($idClassePai);
            } else {
                $classeEn->setNome($nome);
            }

            $em->persist($classeEn);
            $em->flush();
            $em->commit();
            return $classeEn;

        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

     /**
     * Retorna um array id => valor do
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();
        
        foreach ($this->findBy(array(), array('nome' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getNome();
        }

        return $valores;
    }
}