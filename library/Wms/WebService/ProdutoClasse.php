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
     * Método para consultar no WMS a Classe específica pelo ID informado
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idClasse</b> - OBRIGATÓRIO
     * </p>
     *
     * @param string $idClasse ID do Classe
     * @return classe
     * @throws Exception
     */
    public function buscar($idClasse)
    {
        $idClasse = trim($idClasse);

        $classeEntity = $this->__getServiceLocator()->getService('Produto\Classe')->find($idClasse);

        if ($classeEntity == null)
            throw new \Exception('Classe não encontrada');

        $classe = new classe();
        $classe->idClasse = $idClasse;
        $classe->nome = $classeEntity->getNome();
        $classe->idClassePai = $classeEntity->getIdPai();
        return $classe;
    }

    /**
     * Método para salvar uma Classe no WMS. Se a Classe já existe atualiza, se não, registra
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idClasse</b> - OBRIGATÓRIO<br>
     * <b>nome</b> - OBRIGATÓRIO<br>
     * <b>idClassePai</b> - OPCIONAL (Caso tenha)<br>
     * </p>
     *
     * @param string $idClasse ID 
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean Classe foi salvo com sucesso
     * @throws Exception
     */
    public function salvar($idClasse, $nome, $idClassePai = null)
    {
        $idClasse = trim($idClasse);
        $nome = trim ($nome);

        if (empty($idClasse)) throw new Exception("O ID da classe é obrigatório");
        if (empty($nome)) throw new Exception("O nome da classe é obrigatório");

        $service = $this->__getServiceLocator()->getService('Produto\Classe');
        $entity = $service->find($idClasse);

        //novo Classe
        $op = ($entity == null) ? $this->inserir($idClasse, $nome, $idClassePai) :
                $this->alterar($idClasse, $nome, $idClassePai);

        if (!$op)
            throw new \Exception('Houve um erro ao salvar uma Classe');
        
        return true;
    }

    /**
     * Método para excluir uma Classe do WMS
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idClasse</b> - OBRIGATÓRIO
     * </p>
     * 
     * @param string $idClasse
     * @return boolean
     * @throws Exception
     */
    public function excluir($idClasse)
    {

        $idClasse = trim ($idClasse);
        if (empty($idClasse)) throw new Exception("O ID da classe é obrigatório");

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $service = $this->__getServiceLocator()->getService('Produto\Classe');
            $produtoClasse = $service->find($idClasse);

            if (!$produtoClasse)
                throw new Exception("Não existe classe de Produto com esse codigo '$idClasse' no sistema");

            $em->remove($produtoClasse);
            $em->flush();
            
            $em->commit();
        } catch (Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }
    
    /**
     * Método para listar todas as Classes cadastradas no WMS Imperium
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     * 
     * @return classes
     * @throws Exception
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

    /**
     * Adiciona um Classe no WMS
     *
     * @param string $idClasse ID
     * @param string $nome Nome ou Nome Fantasia
     * @param string $idClassePai ID pai
     * @return boolean se o Classe foi inserida com sucesso ou não
     * @throws Exception
     */
    private function inserir($idClasse, $nome, $idClassePai = null)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $service = $this->__getServiceLocator()->getService('Produto\Classe');
        $em->beginTransaction();

        try {
            $produtoClasse = $service->find($idClasse);

            if (!$produtoClasse)
                $produtoClasse = new \Wms\Domain\Entity\Produto\Classe;

            $produtoClasse->setId($idClasse)
                ->setNome($nome);

            if ($idClassePai != null) {
                $classePai = $service->find($idClassePai);

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
     * @return boolean se o Classe foi inserida com sucesso ou não
     * @throws Exception
     */
    private function alterar($idClasse, $nome, $idClassePai = null)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();

        try {
            $service = $this->__getServiceLocator()->getService('Produto\Classe');
            $produtoClasse = $service->find($idClasse);

            $produtoClasse->setId($idClasse)
                ->setNome($nome);

            if ($idClassePai != null) {
                $classePai = $service->find($idClassePai);

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

}