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

        $totalOcupados = 0;
        $totalVazios = 0;
        $totalExistentes = 0;

        foreach ($estoqueAtualResumido as $rua) {
            $percentual = $rua['PERCENTUAL_OCUPADOS'];
            $numRua = $rua['RUA'];
            $qtdExistentes = $rua['PALETES_EXISTENTES'];
            $qtdOcupados = $rua['PALETES_OCUPADOS'];
            $qtdVazios = $qtdExistentes - $qtdOcupados;

            $totalOcupados = $totalOcupados + $qtdOcupados;
            $totalVazios = $totalVazios + $qtdVazios;
            $totalExistentes = $totalExistentes + $qtdExistentes;

            $posicao_estoque = new PosicaoEstoqueResumido();
            $posicao_estoque->setPercentualOcupacao($percentual);
            $posicao_estoque->setQtdExistentes($qtdExistentes);
            $posicao_estoque->setQtdVazios($qtdVazios);
            $posicao_estoque->setQtdOcupados($qtdOcupados);
            $posicao_estoque->setRua($numRua);
            $posicao_estoque->setDtEstoque($dtEstoque);
            $this->getEntityManager()->persist($posicao_estoque);
        }

        $this->getEntityManager()->flush();
    }
}
