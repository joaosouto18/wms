<?php
namespace Wms\Domain\Entity\Pessoa\Papel;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\AtorRepository;
use DoctrineExtensions\Versionable\Exception;
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

    public function getFornecedorByCNPJ( $cnpj )
    {
        try {
            $sql = $this->getEntityManager()->createQueryBuilder()
                ->select(" p.id, f.idExterno")
                ->from("wms:Pessoa\Papel\Fornecedor", "f")
                ->innerJoin("wms:Pessoa", 'p' , 'WITH', 'f.pessoa = p.id')
                ->innerJoin("wms:Pessoa\Juridica", 'pj', 'WITH', 'pj.id = p.id')
                ->where('pj.cnpj = :cnpj')
                ->setParameter('cnpj', $cnpj);

            $result = $sql->getQuery()->getResult();
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (!empty($result)) {
            $fornecedor = new Fornecedor();
            $fornecedor->setIdExterno($result[0]['idExterno']);
            $fornecedor->setPessoa($this->getEntityManager()->getRepository('wms:Pessoa')->findOneBy(array("id" => $result[0]['id'])));
            return $fornecedor;
        }

        return null;
    }
}