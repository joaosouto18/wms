<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Core\Util\Produto,
    Wms\Domain\Entity\Deposito\Endereco,
    Wms\Domain\Entity\Armazenagem\Unitizador as unitizadorEntity,
    Wms\Domain\Entity\Deposito\Endereco as depositoEntity;


class PosicaoEstoqueRepository extends EntityRepository
{

    public function gravarEstoque()
    {
        $dtEstoque = new \DateTime();

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('prod.id produto, prod.grade grade, estq.dtPrimeiraEntrada, estq.qtd, estq.uma, u.id unitizador, dep.id deposito')
            ->from("wms:Enderecamento\Estoque", "estq")
            ->leftJoin('estq.unitizador', 'u')
            ->leftJoin('estq.produto', 'prod')
            ->leftJoin('estq.depositoEndereco', 'dep');

        $resultado = $source->getQuery()->getResult();

        foreach ($resultado as $estoque) {

            $produto = $estoque['produto'];
            $grade = $estoque['grade'] ;
            $deposito = $estoque['deposito'];
            $dtPrimeiraEntrada = $estoque['dtPrimeiraEntrada'];
            $qtd = $estoque['qtd'] ;
            $unitizador = $estoque['unitizador'];
            $uma = $estoque['uma'];

            $posicao_estoque = new PosicaoEstoque();
                $posicao_estoque->setCodProduto($produto);
                $posicao_estoque->setGrade($grade);
                $posicao_estoque->setCodEndereco($deposito);
                $posicao_estoque->setCodUnitizador($unitizador);
                $posicao_estoque->setDtPrimeiraEntrada($dtPrimeiraEntrada);
                $posicao_estoque->setQtd($qtd);
                $posicao_estoque->setUma($uma);
                $posicao_estoque->setDtEstoque($dtEstoque);
            $this->getEntityManager()->persist($posicao_estoque);
        }

        $this->getEntityManager()->flush();

    }

    public function verificarEstoque()
    {
        $dateTime = new \DateTime();
        $dataAtual = $dateTime->format('d-m-Y');
        $data = new \DateTime($dataAtual);

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("count(p) as qtd")
            ->from("wms:Enderecamento\PosicaoEstoque","p")
            ->where("(TRUNC(p.dtEstoque) >= ?1 AND TRUNC(p.dtEstoque) <= ?2)")
            ->setParameter(1, $data)
            ->setParameter(2, $data);
        $result = $source->getQuery()->getResult();

        return $result[0]['qtd'];
    }

    public function removerEstoqueAtual()
    {
        $dateTime = new \DateTime();
        $dataAtual = $dateTime->format('d-m-Y');
        $data = new \DateTime($dataAtual);

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("p")
            ->from("wms:Enderecamento\PosicaoEstoque","p")
            ->where("(TRUNC(p.dtEstoque) >= ?1 AND TRUNC(p.dtEstoque) <= ?2)")
            ->setParameter(1, $data)
            ->setParameter(2, $data);
        $result = $source->getQuery()->getResult();

        $em = $this->getEntityManager();
        foreach ($result as $itemPosicaoEstoque) {
            $em->remove($itemPosicaoEstoque);
        }

        $em->flush();
        return true;
    }

}
