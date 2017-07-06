<?php

namespace Wms\Domain\Entity\Pessoa\Papel;

use Wms\Domain\Entity\AtorRepository;

class FornecedorRepository extends AtorRepository
{

    public function getAll(){
        $SQL = "SELECT F.COD_FORNECEDOR,
                       P.NOM_PESSOA
                  FROM FORNECEDOR F
                  LEFT JOIN PESSOA P ON P.COD_PESSOA = F.COD_FORNECEDOR";
        $resultado = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $arrayResult = array();
        foreach ($resultado as $linha) {
            $arrayResult[$linha['COD_FORNECEDOR']] = $linha['NOM_PESSOA'];
        }
        return $arrayResult;
        
    }

    public function getAllByExterno(){
        $SQL = "SELECT F.COD_FORNECEDOR_EXTERNO,
                       P.NOM_PESSOA
                  FROM FORNECEDOR F
                  LEFT JOIN PESSOA P ON P.COD_PESSOA = F.COD_FORNECEDOR";
        $resultado = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $arrayResult = array();
        foreach ($resultado as $linha) {
            $arrayResult[$linha['COD_FORNECEDOR_EXTERNO']] = $linha['NOM_PESSOA'];
        }
        return $arrayResult;

    }

    public function save($idFornecedor)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {

            $fornecedorEn = $this->findOneBy(array('codClienteExterno' => $idFornecedor));
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

