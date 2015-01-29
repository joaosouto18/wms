<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;

class RelatorioPickingRepository extends EntityRepository
{
    public function getSelecionados() {
        $em = $this->getEntityManager();
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $em->getRepository("wms:Deposito\Endereco");

        $enderecosSelecionados = $this->getDescricaoSelecionadosOrdenado();
        $result = array();

        /** @var \Wms\Domain\Entity\Enderecamento\RelatorioPicking $relatorioEn */
        foreach ($enderecosSelecionados as $selecionado) {

            $produtos = $enderecoRepo->getProdutoByEndereco($selecionado['DESCRICAO'], false);

            //var_dump($selecionado);exit;
            foreach ($produtos as $produto) {
                $linha = array();
                $linha['codProduto'] = $produto['codProduto'];
                $linha['grade']      = $produto['grade'];
                $linha['descricao']  = $produto['descricao'];
                $linha['endereco']   = $selecionado['DESCRICAO'];
                $result[] = $linha;
            }
            if (count($produtos)<= 0 ) {
                $linha = array();
                $linha['codProduto'] = "-";
                $linha['grade']      = "-";
                $linha['descricao']  = "NENHUM PRODUTO";
                $linha['endereco']   = $selecionado['DESCRICAO'];
                $result[] = $linha;
            }
        }

       return $result;
       // var_dump($result); exit;

    }

    public function clearSelecionados(){
        $em = $this->getEntityManager();
        $enderecosSelecionados = $this->findAll();
        foreach ($enderecosSelecionados as $relatorioEn) {
            $em->remove($relatorioEn);
        }
        $em->flush();
    }

    public function getDescricaoSelecionadosOrdenado() {
        $sql = "
            SELECT DESCRICAO
            FROM (
                SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO as DESCRICAO, MOD(DE.NUM_PREDIO,2) as LADO, DE.NUM_RUA, DE.NUM_PREDIO, DE.NUM_APARTAMENTO
                  FROM RELATORIO_PICKING RP
                 INNER JOIN DEPOSITO_ENDERECO DE ON RP.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
            ) a
            ORDER BY NUM_RUA, LADO , NUM_PREDIO, NUM_APARTAMENTO
        ";

        $array = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;

    }
}
