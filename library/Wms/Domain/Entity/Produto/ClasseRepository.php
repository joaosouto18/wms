<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Fabricante as FabricanteEntity,
    Doctrine\Common\Persistence\ObjectRepository;


class ClasseRepository extends EntityRepository
{

    public function save($idClasse, $nome, $idClassePai = null, $flush = true)
    {
        $idClasse = trim($idClasse);
        $nome = trim($nome);

        $em = $this->getEntityManager();

        if ($flush == true) {
            $em->beginTransaction();
        }

        try {
            $classeEn = $em->getRepository('wms:Produto\Classe')->findOneBy(array('id' => $idClasse));
            $novo = false;
            if (!$classeEn) {
                $novo = true;
                $classeEn = new Classe();
                $classeEn->setNome($nome);
                $classeEn->setId($idClasse);
                $classeEn->setIdPai($idClassePai);
            } else {
                $classeEn->setNome($nome);
            }

            $em->persist($classeEn);

            if ($flush == true) {
                $em->flush();
                $em->commit();
            } else {
                if ($novo == true) {
                    $em->flush();
                    $em->clear();
                }
            }
            return $classeEn;

        } catch (\Exception $e) {
            if ($flush == true) {
                $em->rollback();
            }
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