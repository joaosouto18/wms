<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;
use Wms\Math;

class PaleteProdutoRepository extends EntityRepository
{
    public function getQtdTotalEnderecadaByRecebimento($idRecebimento, $codProduto, $grade, $codBarras = null)
    {
        $volumeRepository = $this->getEntityManager()->getRepository('wms:Produto\Volume');
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(pp.qtd) qtd')
            ->from('wms:Enderecamento\Palete', 'p')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = p.id')
            ->where("p.recebimento = $idRecebimento 
                 AND pp.codProduto = '$codProduto' 
                 AND pp.grade = '$grade'
                 AND (p.codStatus in (".Palete::STATUS_ENDERECADO.",".Palete::STATUS_EM_ENDERECAMENTO.",".Palete::STATUS_RECEBIDO.") OR p.impresso = 'S')");

        if (!is_null($codBarras)) {
            $volumeEntity = $volumeRepository->findOneBy(array('codigoBarras' => $codBarras));
            $codNormaPaletizacao = $volumeEntity->getNormaPaletizacao()->getId();
            $sql->andWhere("pp.codNormaPaletizacao = $codNormaPaletizacao")
                ->groupBy("pp.codNormaPaletizacao");
        }


        return $sql->getQuery()->getResult();
    }

    public function getProdutoByUma($uma)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('prod')
            ->from('wms:Enderecamento\Palete', 'p')
            ->innerJoin('wms:Enderecamento\PaleteProduto', 'pp', 'WITH', 'pp.uma = p.id')
            ->innerJoin('wms:Produto', 'prod', 'WITH', 'prod.id = pp.codProduto AND prod.grade = pp.grade')
            ->where("p.id = $uma");

        return $sql->getQuery()->getResult();

    }

    public static function getQuantidadeEnderecarPicking($capacidadePicking, $saldoPickingVirtual, $quantidadePalete)
    {
        if ((Math::compare($quantidadePalete, $capacidadePicking)) xor
            (Math::compare($quantidadePalete, $capacidadePicking, '<') && Math::compare($quantidadePalete, Math::subtrair($capacidadePicking, $saldoPickingVirtual), '>='))) {
            /*
             * se a quantidade do palete for maior q a capacidade de picking OU EXCLUSIVAMENTE
             * se a quantidade do palete for menor q a capacidade de picking POREM
             * a quantidade do palete for maior que capacidade do picking menos o saldo do picking
             * e a capacidade de picking for maior q o saldo do picking
             * endereça no picking a capacidade do picking menos o saldo do picking,
             * caso contrario, irá retornar valor negativo com mensagem abaixo
            */
            if (Math::subtrair($capacidadePicking, $saldoPickingVirtual) <= 0) {
                return 'Não é possível endereçar o produto no picking, imprima a UMA e faça o endereçamento manual!';
            }
            return floatval(Math::subtrair($capacidadePicking, $saldoPickingVirtual));
        } elseif (Math::compare($quantidadePalete, $capacidadePicking, '<')) {
            /*
             * se a quantidade do palete for menor q a capacidade do picking
             * endereça no picking a quantidade do palete
             */
            return floatval($quantidadePalete);
        } elseif (Math::compare($saldoPickingVirtual, $capacidadePicking, '>=')) {
            return 'O saldo do picking já está no limite da capacidade suportada!';
        } else {
            /*
             * esse ponto foi feito para caso exista alguma situação q ainda nao foi prevista
             */
            return 'Erro de endereçamento. Entre em contato com o suporte!';
        }

    }


}
