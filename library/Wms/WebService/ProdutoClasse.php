<?php

class classe {
    /** @var string */
    public $idClasse;
    /** @var string */
    public $nome;
    /** @var string */
    public $idClassePai;

}

class classes {
    /** @var classe[] */
    public $classes = array();
}


class Wms_WebService_ProdutoClasse extends Wms_WebService
{

    /**
     * Retorna um Classe específico no WMS pelo seu ID
     *
     * @param string $idClasse ID do Classe
     * @return classe|Exception
     */
    public function buscar($idClasse)
    {
        $classeEntity = $this->__getServiceLocator()->getService('Produto\Classe')->get($idClasse);

        if ($classeEntity == null)
            throw new \Exception('Classe não encontrada');

        $classe = new classe();
        $classe->idClasse = $idClasse;
        $classe->nome = $classeEntity->getNome();
        $classe->idClassePai = $classeEntity->getIdPai();
        return $classe;
    }

    /**
     * Adiciona um Classe no WMS
     * 
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean|Exception se o Classe foi inserida com sucesso ou não
     */
    private function inserir($idClasse, $nome, $idClassePai = null)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $service = $this->__getServiceLocator()->getService('Produto\Classe');
        $em->beginTransaction();

        try {
            $produtoClasse = $service->get($idClasse);

            if (!$produtoClasse)
                $produtoClasse = new \Wms\Domain\Entity\Produto\Classe;

            $produtoClasse->setId($idClasse)
                    ->setNome($nome);

            if ($idClassePai != null) {
                $classePai = $service->get($idClassePai);

                if ($classePai == null)
                    throw new \Exception('Classe pai não existe');

                $produtoClasse->setPai($classePai);
            }

            $em->persist($produtoClasse);
            $em->flush();
            
            $em->commit();    
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }

    /**
     * Altera um Classe no WMS
     * 
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean|Exception se o Classe foi inserida com sucesso ou não
     */
    private function alterar($idClasse, $nome, $idClassePai = null)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $service = $this->__getServiceLocator()->getService('Produto\Classe');
            $produtoClasse = $service->get($idClasse);
            
            $produtoClasse->setId($idClasse)
                    ->setNome($nome);

            if ($idClassePai != null) {
                $classePai = $service->get($idClassePai);

                if ($classePai == null)
                    throw new \Exception('Classe pai não existe');

                $produtoClasse->setPai($classePai);
            }

            $em->persist($produtoClasse);
            $em->flush();
            
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }

    /**
     * Salva um Classe no WMS. Se o Classe não existe, insere, senão, altera 
     * 
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean Classe foi salvo com sucesso
     */
    public function salvar($idClasse, $nome, $idClassePai = null)
    {
        $service = $this->__getServiceLocator()->getService('Produto\Classe');
        $entity = $service->get($idClasse);

        //novo Classe
        $op = ($entity == null) ? $this->inserir($idClasse, $nome, $idClassePai) :
                $this->alterar($idClasse, $nome, $idClassePai);

        if (!$op)
            throw new \Exception('Houve um erro ao salvar uma Classe');
        
        return true;
    }

    /**
     * Exclui um Classe do WMS
     * 
     * @param string $id
     * @return boolean|Exception
     */
    public function excluir($idClasse)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $service = $this->__getServiceLocator()->getService('Produto\Classe');
            $produtoClasse = $service->get($idClasse);

            if (!$produtoClasse)
                throw new \Exception('Não existe classe de Produto com esse codigo no sistema');

            $em->remove($produtoClasse);
            $em->flush();
            
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new $e->getMessage();
        }
        
        return true;
    }
    
    /**
     * Lista todos os Classees cadastrados no sistema
     * 
     * @return classes|Exception
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('c.id as idClasse, c.nome, c.idPai as idClassePai')
                ->from('wms:Produto\Classe', 'c')
                ->orderBy('c.nome')
                ->getQuery()
                ->getArrayResult();

        $classes = new classes();
        $arrayClasses = array();
        foreach ($result as $line) {
            $classe = new classe();
            $classe->idClasse = $line['idClasse'];
            $classe->idClassePai = $line['idClassePai'];
            $classe->nome = $line['nome'];
            $arrayClasses[] = $classe;
        }
        $classes->classes = $arrayClasses;
        return $classes;
    }

}