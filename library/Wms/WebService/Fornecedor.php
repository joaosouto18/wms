<?php

class fornecedor {
    /** @var string */
    public $idFornecedor;
    /** @var string */
    public $nome;
    /** @var string */
    public $cnpj;
    /** @var string */
    public $cpf;
    /** @var string */
    public $insc;
}

class fornecedores {

    /** @var fornecedor[] */
    public $fornecedores = array();
}

class Wms_WebService_Fornecedor extends Wms_WebService
{

    /**
     * Retorna um fornecedor específico no WMS pelo seu ID
     *
     * @param string $idFornecedor ID do fornecedor
     * @return fornecedor
     */
    public function buscar($idFornecedor)
    {
        $idFornecedor = trim($idFornecedor);

        $fornecedorEntity = $this->__getServiceLocator()->getService('Fornecedor')->findOneBy(array('codExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Fornecedor não encontrado');

        return $this->parseObjWS($fornecedorEntity);
    }

    /**
     * Salva um fornecedor no WMS. Se o fornecedor não existe, insere, senão, altera
     *
     * @param string $idFornecedor ID
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @param string $cpf CPF
     * @return boolean se o fornecedor foi salvo ou não
     * @throws Exception
     */
    public function salvar($idFornecedor, $cnpj, $insc, $nome, $cpf)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        try {
            $em->beginTransaction();
            $this->__getServiceLocator()->getService('Fornecedor')->save([
                'codExterno' => trim($idFornecedor),
                'cnpj' => trim($cnpj),
                'insc' => trim($insc),
                'nome' => trim($nome),
                'cpf' => trim($cpf)
            ], true);

            $em->commit();
            return true;
        } catch (Exception $e) {
            $em->rollback();
            throw new \Exception('Houve um erro ao salvar um novo fornecedor: '. $e->getMessage());
        }
    }

    /**
     * Exclui um fornecedor do WMS
     * 
     * @param string $id
     * @return boolean|Exception
     */
    public function excluir($idFornecedor)
    {
        $idFornecedor = trim($idFornecedor);


        /** @var \Wms\Service\FornecedorService $fornecedorSvc */
        $fornecedorSvc = $this->__getServiceLocator()->getService('Fornecedor');

        /** @var \Wms\Domain\Entity\Pessoa\Papel\Fornecedor $fornecedorEntity */
        $fornecedorEntity = $fornecedorSvc->findOneBy(array('codExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Fornecedor não encontrado');
        
        if (!$fornecedorSvc->delete($fornecedorEntity->getId()))
            throw new \Exception('Não foi possível deletar o fornedor ID:' . $idFornecedor);

        return true;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function listar()
    {

        /** @var \Wms\Service\FornecedorService $fornecedorSvc */
        $fornecedorSvc = $this->__getServiceLocator()->getService('Fornecedor');

        $fornecedores = $fornecedorSvc->findAll();

        if ($fornecedores == null)
            throw new \Exception('Não foi encontrado nenhum fornecedor');

        $return = array();
        foreach ($fornecedores as $fornecedor) {
            $return[] = $this->parseObjWS($fornecedor);
        }

        return array('fornecedores' => $return);
    }

    private function parseObjWS(\Wms\Domain\Entity\Pessoa\Papel\Fornecedor $fornecedorEntity) {

        $for = new fornecedor();
        $for->idFornecedor = $fornecedorEntity->getCodExterno();
        $for->nome =  $fornecedorEntity->getNome();
        $pessoa = $fornecedorEntity->getPessoa();
        if (is_a($pessoa, \Wms\Domain\Entity\Pessoa\Juridica::class)) {
            $for->insc = $pessoa->getInscricaoEstadual();
            $for->cnpj =  $fornecedorEntity->getCpfCnpj();
        } else {
            $for->cpf = $fornecedorEntity->getCpfCnpj();
        }

        return $for;
    }
}

