<?php

class fornecedor {
    /** @var string */
    public $idFornecedor;
    /** @var string */
    public $nome;
    /** @var string */
    public $cnpj;
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
        $fornecedorEntity = $this->__getServiceLocator()->getService('Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Fornecedor não encontrado');

        $pessoa = $fornecedorEntity->getPessoa();
        $for = new fornecedor();
        $for->idFornecedor = $idFornecedor;
        $for->nome =  ($pessoa->getNomeFantasia() != null) ? $pessoa->getNomeFantasia() : $pessoa->getNome();
        $for->cnpj =  $pessoa->getCnpj();
        $for->insc = $pessoa->getInscricaoEstadual();
        return $for;
    }

    /**
     * Adiciona um fornecedor no WMS
     * 
     * @param string $idFornecedor ID 
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean|Exception se o fornecedor foi inserido com sucesso ou não
     */
    private function inserir($idFornecedor, $cnpj, $insc, $nome)
    {
        $service = $this->__getServiceLocator()->getService('Fornecedor');

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $pessoaJuridica = $em->getRepository('wms:Pessoa\Juridica')->findOneBy(array('cnpj' => str_replace(array('.', '-', '/'), '', $cnpj)));

        if ($pessoaJuridica == null)
            $pessoaJuridica = new \Wms\Domain\Entity\Pessoa\Juridica;
        
        $pessoaJuridica->setNome($nome)
                ->setNomeFantasia($nome)
                ->setCnpj($cnpj)
                ->setInscricaoEstadual($insc);

        $fornecedorEntity = new \Wms\Domain\Entity\Pessoa\Papel\Fornecedor;
        $fornecedorEntity->setPessoa($pessoaJuridica)
                ->setIdExterno($idFornecedor);

        if (!$service->post($fornecedorEntity))
            throw new \Exception('Houve um erro ao inserir um novo fornecedor');

        return true;
    }

    /**
     * Altera um fornecedor no WMS
     * 
     * @param string $idFornecedor ID 
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean|Exception se o fornecedor foi inserido com sucesso ou não
     */
    private function alterar($idFornecedor, $cnpj, $insc, $nome)
    {
        $service = $this->__getServiceLocator()->getService('Fornecedor');
        $fornecedorEntity = $service->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Não foi possível alterar Fornecedor inexistente');

        $pessoaJuridica = $fornecedorEntity->getPessoa();
        $pessoaJuridica->setNome($nome)
                ->setNomeFantasia($nome)
                ->setCnpj($cnpj)
                ->setInscricaoEstadual($insc);

        $fornecedorEntity->setPessoa($pessoaJuridica)
                ->setIdExterno($idFornecedor);

        if (!$service->put($fornecedorEntity))
            throw new \Exception('Houve um erro ao alterar um novo fornecedor');

        return true;
    }

    /**
     * Salva um fornecedor no WMS. Se o fornecedor não existe, insere, senão, altera 
     * 
     * @param string $idFornecedor ID 
     * @param string $cnpj CNPJ
     * @param string $insc Inscrição Estadual
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean se o fornecedor foi salvo ou não
     */
    public function salvar($idFornecedor, $cnpj, $insc, $nome)
    {
        $service = $this->__getServiceLocator()->getService('Fornecedor');
        $fornecedorEntity = $service->findOneBy(array('idExterno' => $idFornecedor));

        //novo fornecedor
        $op = ($fornecedorEntity == null) ? $this->inserir($idFornecedor, $cnpj, $insc, $nome) :
                $this->alterar($idFornecedor, $cnpj, $insc, $nome);

        if (!$op)
            throw new \Exception('Houve um erro ao salvar um novo fornecedor');

        return true;
    }

    /**
     * Exclui um fornecedor do WMS
     * 
     * @param string $id
     * @return boolean|Exception
     */
    public function excluir($idFornecedor)
    {
        $service = $this->__getServiceLocator()->getService('Fornecedor');
        $fornecedorEntity = $service->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Fornecedor não encontrado');
        
        if (!$service->delete($fornecedorEntity->getId()))
            throw new \Exception('Não foi possível deletar o fornedor ID:' . $idFornecedor);

        return true;
    }

    /**
     * Lista todos os fornecedores cadastrados no sistema
     * 
     * @return fornecedores
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('f.idExterno as idFornecedor, p.cnpj, p.nome, p.inscricaoEstadual as insc')
                ->from('wms:Pessoa\Papel\Fornecedor', 'f')
                ->innerJoin('f.pessoa', 'p')
                ->orderBy('p.nome')
                ->getQuery()
                ->getArrayResult();

        if ($result == null)
            throw new \Exception('Não foi possível recuperar os fornecedores:');

        $fornecedores = array();
        foreach($result as $line){
            $for = new fornecedor();
            $for->idFornecedor = $line['idFornecedor'];
            $for->nome =  $line['nome'];
            $for->cnpj =  $line['cnpj'];
            $for->insc = $line['insc'];
            $fornecedores[] = $for;
        }
        $clsFornecedres = new fornecedores();
        $clsFornecedres->fornecedores = $fornecedores;

        return $clsFornecedres;
    }

}

