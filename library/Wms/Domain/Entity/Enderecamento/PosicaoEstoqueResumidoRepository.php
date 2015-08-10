<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Endereco,
    Wms\Domain\Entity\Deposito\Endereco as depositoEntity;

class PosicaoEstoqueResumidoRepository extends EntityRepository
{
    public function verificarResumoEstoque()
    {
        $dateTime = new \DateTime();
        $dataAtual = $dateTime->format('d-m-Y');
        $data = new \DateTime($dataAtual);

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("count(p) as qtd")
            ->from("wms:Enderecamento\PosicaoEstoqueResumido","p")
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
            ->from("wms:Enderecamento\PosicaoEstoqueResumido","p")
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

    public function gravarResumoEstoque(){
        $em = $this->getEntityManager();
        $dtEstoque = new \DateTime();

        $params = array();
        $params['ruaInicial'] ='';
        $params['ruaFinal'] = '';

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $EnderecoRepo */
        $EnderecoRepo = $em->getRepository('wms:Deposito\Endereco');
        $estoqueAtualResumido = $EnderecoRepo->getOcupacaoRuaReport($params);

        foreach ($estoqueAtualResumido as $rua) {
            $numRua = $rua['NUM_RUA'];
            $posExistentes = $rua['POS_EXISTENTES'];
            $posOcupadas = ($rua['POS_EXISTENTES'] - $rua['POS_DISPONIVEIS']);
            $posDisponives = $rua['POS_DISPONIVEIS'];
            $percentualOcupacao = ($posOcupadas/$posExistentes) * 100;

            $posicao_estoque = new PosicaoEstoqueResumido();
                $posicao_estoque->setPercentualOcupacao($percentualOcupacao);
                $posicao_estoque->setQtdExistentes($posExistentes);
                $posicao_estoque->setQtdVazios($posDisponives);
                $posicao_estoque->setQtdOcupados($posOcupadas);
                $posicao_estoque->setRua($numRua);
                $posicao_estoque->setDtEstoque($dtEstoque);
            $this->getEntityManager()->persist($posicao_estoque);
        }

        $this->getEntityManager()->flush();
    }
}
