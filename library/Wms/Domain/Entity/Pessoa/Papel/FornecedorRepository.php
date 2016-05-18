<?php
namespace Wms\Domain\Entity\Pessoa\Papel;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\AtorRepository;
use Wms\Domain\Entity\Ator;

class FornecedorRepository extends AtorRepository
{

    public function save($idFornecedor)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');
            $fornecedorEn = $fornecedorRepo->findOneBy(array('codClienteExterno' => $idFornecedor));
            if (!$fornecedorEn)
                $fornecedorEn = new Fornecedor();

            $fornecedorEn->setId($idFornecedor);
            $em->persist($fornecedorEn);

        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }
}