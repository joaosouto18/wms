<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository;

class EmbalagemRepository extends EntityRepository {

    /**
     * @param $novaEmbalagem \Wms\Domain\Entity\Produto\Embalagem
     * @return bool|\Exception
     */
    public function checkEmbalagemDefault($novaEmbalagem) {
        try {
            if (!empty($novaEmbalagem) && is_a($novaEmbalagem, '\Wms\Domain\Entity\Produto\Embalagem')) {
                $criterio = array(
                    'codProduto' => $novaEmbalagem->getProduto()->getId(),
                    'grade' => $novaEmbalagem->getProduto()->getGrade(),
                    'isPadrao' => 'S'
                );

                $result = $this->findBy($criterio);

                if (count($result) > 1) {
                    if (($key = array_search($novaEmbalagem, $result)) !== false) {
                        unset($result[$key]);
                    }

                    /** @var \Wms\Domain\Entity\Produto\Embalagem $obj */
                    foreach ($result as $key => $obj) {
                        $obj->setIsPadrao('N');
                        $this->_em->persist($obj);
                    }

                    $this->_em->flush();
                }

                return true;
            } else {
                throw new \Exception("A variavel passada não é válida");
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function setPickingEmbalagem($codBarras, $enderecoEn, $capacidadePicking, $embalado) {
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));

        if (empty($embalagemEn)) {
            throw new \Exception('Produto não encontrado');
        }

        $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getCodProduto(), 'grade' => $embalagemEn->getGrade()));

        foreach ($embalagemEntities as $embalagem) {
            $embalagem->setEndereco($enderecoEn);
            $this->getEntityManager()->persist($embalagemEn);
        }

        $embalagemEn->setCapacidadePicking($capacidadePicking);
        $embalagemEn->setEmbalado($embalado);
        $this->getEntityManager()->persist($embalagemEn);
        $this->getEntityManager()->flush();
    }

    public function checkEstoqueReservaById($id) {
        $dql = $this->_em->createQueryBuilder()
            ->select('NVL(e.id, rep.id)')
            ->from('wms:Produto\Embalagem', 'pe')
            ->leftJoin('wms:Enderecamento\Estoque', 'e', 'WITH', 'pe.id = e.produtoEmbalagem')
            ->leftJoin('wms:Ressuprimento\ReservaEstoqueProduto', 'rep', 'WITH','pe.id = rep.codProdutoEmbalagem')
            ->leftJoin("rep.reservaEstoque", 're')
            ->where("pe.id = :id and re.atendida = 'N'")
            ->setParameter('id',$id);

        $result = $dql->getQuery()->getResult();
        $msg = null;
        $status = 'ok';
        foreach ($result as $item) {
            foreach ($item as $id) {
                if (!empty($id)) {
                    $status = 'error';
                    $msg = 'Não é permitido excluir embalagens com estoque ou reserva de estoque!';
                }
                if ($status === 'error')
                    break;
            }
            if ($status === 'error')
                break;
        }
        return array($status, $msg);
    }

    /**
     * Retorna quantidade usada para cada embalagem
     * @param int $codProduto Description
     * @param int $grade Description
     * @param int $qtd Description
     * @return array Description
     */
    public function getQtdEmbalagensProduto($codProduto, $grade, $qtd) {
        $arrayQtds = array();
        $embalagensEn = $this->findBy(array('codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null), array('quantidade' => 'DESC'));
        $qtdRestante = $qtd;

        foreach ($embalagensEn as $embalagem) {
            $qtdEmbalagem = $embalagem->getQuantidade();
            if ($qtdRestante >= $qtdEmbalagem) {
                $qtdSeparar = (int) ($qtdRestante / $qtdEmbalagem);
                $qtdRestante = $qtdRestante - ($qtdSeparar * $qtdEmbalagem);
                if ($embalagem->getDescricao() != null) {
                    $arrayQtds[] = $qtdSeparar . ' Emb:' . $embalagem->getDescricao() . "(" . $embalagem->getQuantidade() . ")";
                } else {
                    $arrayQtds[] = $qtd;
                }
            }
        }
        return $arrayQtds;
    }

}
