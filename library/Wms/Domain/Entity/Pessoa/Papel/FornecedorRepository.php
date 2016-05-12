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
    
}
