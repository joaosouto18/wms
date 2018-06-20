<?php

namespace Wms\Domain\Entity\Pessoa\Fisica;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Pessoa\Fisica\Conferente as ConferenteEntity,
    Wms\Domain\Entity\AtorRepository;

class ConferenteRepository extends AtorRepository {

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue() {
        $conferentes = array();

        $result = $this->findAll();
        /** @var Conferente $conferente */
        foreach ($result as $conferente) {
            $pessoa = $conferente->getPessoa();
            if (!empty($pessoa))
                $conferentes[$pessoa->getId()] = $pessoa->getNome();
        }

        return $conferentes;
    }

    /**
     * Persiste dados do conferente no sistema
     * 
     * @param Conferente $conferente
     * @param array $values valores vindo de um formulário
     */
    public function save(ConferenteEntity $conferente, array $values) {
        $em = $this->getEntityManager();
        $this->persistirAtor($conferente, $values);

        $conferenteEntity = $em->getRepository('wms:Pessoa\Fisica\Conferente')->findOneBy(array('pessoa' => $conferente->getId()));
        if (!$conferenteEntity) {
            $em->persist($conferente);
        }
        $em->flush();
    }

    /**
     * Remove o conferente através do seu id
     * @param integer $id 
     */
    public function remove($id) {
        $em = $this->getEntityManager();
        $conferente = $em->getReference('wms:Pessoa\Fisica\Conferente', $id);

        // remove
        $em->remove($conferente);
    }

}