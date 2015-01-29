<?php

use Wms\Domain\Entity\Produto as ProdutoEntity;

class Wms_WebService_Produto extends Wms_WebService {

    /**
     * Retorna um Produto específico no WMS pelo seu ID
     *
     * @param string $idProduto ID do Produto
     * @param string $grade Grade do Produto
     * @return array|Exception
     */
    public function buscar($idProduto, $grade) {

        $produtoService = $this->__getServiceLocator()->getService('Produto');
        $produto = $produtoService->findOneBy(array('id' => $idProduto, 'grade'=> $grade));

        if ($produto == null) {
            throw new \Exception('Produto não encontrado');
        }

        return array(
            'idProduto' => $idProduto,
            'descricao' => $produto->getDescricao(),
            'grade' => $produto->getGrade(),
            'idFabricante' => $produto->getFabricante()->getId(),
            'tipo' => $produto->getTipoComercializacao()->getId(),
            'idClasse' => $produto->getClasse()->getId(),
            'nomeFabricante' => $produto->getFabricante()->getNome(),
        );
    }

    /**
     * Checa se um fabricante existe
     * 
     * @param string $idFabricante
     * @return boolean|Wms\Domain\Entity\Fabricante
     */
    private function getFabricante($idFabricante) {
        $fabricante = $this->__getServiceLocator()
                ->getService('Fabricante')
                ->get($idFabricante);

        return ($fabricante == null) ? false : $fabricante;
    }

    /**
     * Checa se uma classe existe
     * 
     * @param string $idClasse
     * @return boolean|Wms\Domain\Entity\Produto\Classe
     */
    private function getClasse($idClasse) {
        $classe = $this->__getServiceLocator()
                ->getService('Produto\Classe')
                ->get($idClasse);

        return ($classe == null) ? false : $classe;
    }

    /**
     * Salva um Produto no WMS. Se o Produto não existe, insere, senão, altera
     *
     * @param string $idProduto ID do produto
     * @param string $descricao Descrição
     * @param string $grade Grade
     * @param string $idFabricante ID do fabricante
     * @param string $tipo 1 => Unitário, 2 => Composto, 3 => Kit | Hoje não está sendo utilizado
     * @param string $idClasse ID da classe do produto
     * @throws Exception
     * @return boolean Se o produto foi inserido com sucesso ou não
     */
    public function salvar($idProduto, $descricao, $grade, $idFabricante, $tipo, $idClasse) {
        $service = $this->__getServiceLocator()->getService('Produto');
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $em->beginTransaction();

        try {
            $produto = $service->findOneBy(array('id' => $idProduto, 'grade' => $grade));

            if (!$produto) {
                $produtoNovo = true;
            } else {
                $produtoNovo = false;
            }

            if (!$produto)
                $produto = new ProdutoEntity;

            $fabricante = $this->getFabricante($idFabricante);

            if (!$fabricante)
                throw new \Exception('Fabricante inexistente');

            $classe = $this->getClasse($idClasse);

            if (!$classe)
                throw new \Exception('Classe do produto de codigo ' . $idClasse . ' inexistente');

            // define numero de volume e tipo de comercializacao do produto
            $tipoComercializacaoEntity = $em->getReference('wms:Produto\TipoComercializacao', $tipo);
            $numVolumes = ($produto->getNumVolumes()) ? $produto->getNumVolumes() : 1;

            $produto->setId($idProduto)
                ->setDescricao($descricao)
                ->setGrade($grade)
                ->setFabricante($fabricante)
                ->setClasse($classe);

            if ($produtoNovo == true) {
                $produto
                    ->setTipoComercializacao($tipoComercializacaoEntity)
                    ->setNumVolumes($numVolumes);
            }

            $em->persist($produto);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Exclui um Produto do WMS
     * 
     * @param string $idProduto ID do produto
     * @param string $grade Grade
     * @return boolean|Exception
     */
    public function excluir($idProduto, $grade) {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $service = $this->__getServiceLocator()->getService('Produto');
        $em->beginTransaction();

        try {
            $produto = $service->findOneBy(array('id' => $idProduto, 'grade' => $grade));

            if (!$produto)
                throw new \Exception('Não existe produto com esse codigo no sistema');

            $em->remove($produto);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Lista todos os Produtos cadastrados no sistema
     * 
     * @return array|Exception
     */
    public function listar() {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('p.id as idProduto, p.descricao, p.grade, f.id as idFabricante, p.tipo, c.id as idClasse, f.nome as nomeFabricante')
                ->from('wms:Produto', 'p')
                ->innerJoin('p.fabricante', 'f')
                ->innerJoin('p.classe', 'c')
                ->orderBy('p.descricao')
                ->getQuery()
                ->getArrayResult();

        return $result;
    }

    /**
     * @param string $idProduto
     * @param string $descricao
     * @param int $idFabricante
     * @param int $tipo
     * @param int $idClasse
     * @param array $grades
     * @param array $classes
     * @param array $fabricante
     * @return boolean
     */
    public function salvarCompleto($idProduto, $descricao, $idFabricante, $tipo, $idClasse, array $grades, array $classes, array $fabricante)
    {
        $wsClasse = new Wms_WebService_ProdutoClasse();
        foreach($classes as $classe)
            $wsClasse->salvar($classe['idClasse'], $classe['nome'], $classe['idClassePai']);
        unset($wsClasse);

        $wsFabricante  = new Wms_WebService_Fabricante();
        $wsFabricante->salvar($fabricante['idFabricante'], $fabricante['nome']);
        unset($wsFabricante);

        if( empty($grades) )
            throw new \Exception('O array de grades deve ser informado.');

        foreach($grades as $grade)
            $this->salvar($idProduto, $descricao, $grade, $idFabricante, $tipo, $idClasse);

        return true;
    }

}