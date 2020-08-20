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
     * Método para consultar no WMS o Fabricante específico pelo ID informado
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFabricante</b> - OBRIGATÓRIO
     * </p>
     *
     * @param string $idFabricante ID do Fabricante a ser consultado
     * @return fabricante
     * @throws Exception
     */
    public function buscar($idFabricante)
    {
        $idFabricante = trim($idFabricante);
        if (empty($idFabricante)) throw new Exception("O ID do fabricante é obrigatório");

        $fabricanteEntity = $this->__getServiceLocator()->getService('Fabricante')->find($idFabricante);

        if ($fabricanteEntity == null)
            throw new \Exception('Fabricante não encontrado');

        $fabricante = new fabricante();
        $fabricante->idFabricante = $idFabricante;
        $fabricante->nome = $fabricanteEntity->getNome();
        return $fabricante;
    }

    /**
     * Método para salvar um Fabricante no WMS. Se o Fabricante já existe atualiza, se não, registra
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFabricante</b> - OBRIGATÓRIO<br>
     * <b>nome</b> - OBRIGATÓRIO
     * </p>
     *
     * @param string $idFabricante ID 
     * @param string $nome Nome do fabricante
     * @return boolean se o Fabricante foi salvo com sucesso ou não
     * @throws Exception
     */
    public function salvar($idFabricante, $nome)
    {
        $idFabricante = trim($idFabricante);
        $nome = trim($nome);

        if (empty($idFabricante)) throw new Exception("O ID do fabricante é obrigatório");
        if (empty($nome)) throw new Exception("O nome do fabricante é obrigatório");

        $service = $this->__getServiceLocator()->getService('Fabricante');
        $entity = $service->find($idFabricante);
        //novo Fabricante
        $op = ($entity == null) ? $this->inserir($idFabricante, $nome) :
                $this->alterar($idFabricante, $nome);

        if (!$op)
            throw new \Exception('Houve um erro ao salvar um novo Fabricante');

        return true;
    }

    /**
     * Método para excluir um Fabricante do WMS
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFabricante</b> - OBRIGATÓRIO
     * </p>
     *
     * @param string $idFabricante ID do fabricante a ser excluído
     * @return boolean
     * @throws Exception
     */
    public function excluir($idFabricante)
    {
        $idFabricante = trim($idFabricante);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();
        
        try {
            $service = $this->__getServiceLocator()->getService('Fabricante');
            $fabricante = $service->find($idFabricante);
            
            
            
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
     * Método para listar todos os Fabricantes cadastrados no WMS Imperium
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * @return fabricantes
     * @throws Exception
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
            $fabricante = $service->find($idFabricante);

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
}