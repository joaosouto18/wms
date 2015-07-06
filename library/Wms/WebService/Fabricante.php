<?php

class fabricante {
    /** @var string */
    public $idFabricante;
    /** @var string */
    public $nome;
}

class fabricantes {

    /** @var fabricante[] */
    public $fabricantes = array();
}

/**
 * 
 */
class Wms_WebService_Fabricante extends Wms_WebService
{

    /**
     * Retorna uma matriz contendo os dados de um Fabricante específico no WMS
     *
     * @param string $idFabricante ID do Fabricante a ser consultado
     * @return fabricante
     */
    public function buscar($idFabricante)
    {
        $idFabricante = trim($idFabricante);

        $fabricanteEntity = $this->__getServiceLocator()->getService('Fabricante')->get($idFabricante);

        if ($fabricanteEntity == null)
            throw new \Exception('Fabricante não encontrado');

        $fabricante = new fabricante();
        $fabricante->idFabricante = $idFabricante;
        $fabricante->nome = $fabricanteEntity->getNome();
        return $fabricante;
    }

    /**
     * Adiciona um Fabricante no WMS
     * 
     * @param string $idFabricante ID 
     * @param string $nome Nome ou Nome Fantasia
     * @return boolean|Exception se o Fabricante foi inserido com sucesso ou não
     */
    private function inserir($idFabricante, $nome)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $fabricanteEntity = new \Wms\Domain\Entity\Fabricante;

            $fabricanteEntity->setId($idFabricante)
                    ->setNome($nome);

            $em->persist($fabricanteEntity);
            $em->flush();
            
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }

    /**
     * Altera um Fabricante no WMS
     * 
     * @param string $idFabricante ID 
     * @param string $nome Nome do fabricante
     * @return boolean|Exception se o Fabricante foi inserido com sucesso ou não
     */
    private function alterar($idFabricante, $nome)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $service = $this->__getServiceLocator()->getService('Fabricante');
            $fabricante = $service->get($idFabricante);

            $fabricante->setId($idFabricante)
                    ->setNome($nome);

            $em->persist($fabricante);
            $em->flush();
            
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }

    /**
     * Salva um Fabricante no WMS. Se o Fabricante não existe, insere, senão, altera 
     * 
     * @param string $idFabricante ID 
     * @param string $nome Nome do fabricante
     * @return boolean se o Fabricante foi salvo com sucesso ou não
     */
    public function salvar($idFabricante, $nome)
    {
        $idFabricante = trim($idFabricante);
        $nome = trim($nome);

        $service = $this->__getServiceLocator()->getService('Fabricante');
        $entity = $service->get($idFabricante);
        //novo Fabricante
        $op = ($entity == null) ? $this->inserir($idFabricante, $nome) :
                $this->alterar($idFabricante, $nome);

        if (!$op)
            throw new \Exception('Houve um erro ao salvar um novo Fabricante');

        return true;
    }

    /**
     * Exclui um Fabricante do WMS
     * 
     * @param string $id ID do fabricante a ser excluído
     * @return boolean|Exception
     */
    public function excluir($idFabricante)
    {
        $idFabricante = trim($idFabricante);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $service = $this->__getServiceLocator()->getService('Fabricante');
            $fabricante = $service->get($idFabricante);
            
            
            
            if (!$fabricante)
                throw new \Exception('Não existe fabricante com esse codigo no sistema');

            $em->remove($fabricante);
            $em->flush();
            
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }

    /**
     * Retorna uma matriz com todos os fabricantes cadastrados no WMS
     * 
     * @return fabricantes|Exception
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('f.id as idFabricante, f.nome')
                ->from('wms:Fabricante', 'f')
                ->orderBy('f.nome')
                ->getQuery()
                ->getArrayResult();

        $fabricantes = new fabricantes();
        $arrayFabricantes = array();
        foreach ($result as $line) {
            $fabricante = new fabricante();
            $fabricante->idFabricante = $line['idFabricante'];
            $fabricante->nome = $line['nome'];
            $arrayFabricantes[] = $fabricante;
        }
        $fabricantes->fabricantes = $arrayFabricantes;

        return $fabricantes;
    }

}