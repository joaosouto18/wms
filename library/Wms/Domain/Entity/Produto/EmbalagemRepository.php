<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Produto;
use Wms\Math;
use Wms\Util\Coletor;

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
            throw new \Exception('Embalagem não encontrada');
        }

        $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getCodProduto(), 'grade' => $embalagemEn->getGrade()));
        $capacidadePicking = $capacidadePicking * $embalagemEn->getQuantidade();

        foreach ($embalagemEntities as $embalagem) {
            $embalagem->setEndereco($enderecoEn);
            $embalagem->setCapacidadePicking($capacidadePicking);
            $this->getEntityManager()->persist($embalagemEn);
        }

        $embalagemEn->setEmbalado($embalado);
        $this->getEntityManager()->persist($embalagemEn);
    }

    public function setNormaPaletizacaoEmbalagem($codBarras, $numLastro, $numCamadas, $unitizador)
    {
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $pesoRepo = $this->getEntityManager()->getRepository('wms:Produto\Peso');
        $produtoDadoLogisticoRepo = $this->getEntityManager()->getRepository('wms:Produto\DadoLogistico');
        $unitizadorRepo = $this->_em->getRepository('wms:Armazenagem\Unitizador');

        $codBarras = Coletor::adequaCodigoBarras($codBarras);
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));
        if (empty($embalagemEn)) {
            throw new \Exception('Embalagem não encontrada');
        }

        $peso = 0;
        $pesoEn = $pesoRepo->findOneBy(array(
            'produto'=>$embalagemEn->getProduto()->getId(),
            'grade'=> $embalagemEn->getProduto()->getGrade()
        ));
        if ($pesoEn != null) $peso = $pesoEn->getPeso();

        $unitizadorEn = $unitizadorRepo->find($unitizador);
        if (empty($unitizadorEn)) {
            throw new \Exception('Unitizador não encontrado');
        }

        $dql = $this->_em->createQueryBuilder()
            ->select ('pdl.id as id')
            ->from('wms:Produto\DadoLogistico','pdl')
            ->leftJoin('pdl.embalagem','e')
            ->leftJoin('e.produto','p')
            ->where("e.codProduto = :codProduto")
            ->andWhere("e.grade = :grade")
            ->setParameter('codProduto', $embalagemEn->getProduto()->getId())
            ->setParameter('grade', $embalagemEn->getProduto()->getGrade());
        $result = $dql->getQuery()->getResult();

        if (count($result) >0) {
            $produtoDadoLogisticoEn = $produtoDadoLogisticoRepo->find($result[0]['id']);
            $normaPaletizacaoEn = $produtoDadoLogisticoEn->getNormaPaletizacao();

            $produtoDadoLogisticoEn->setEmbalagem($embalagemEn);
            $normaPaletizacaoEn->setNumLastro($numLastro);
            $normaPaletizacaoEn->setNumCamadas($numCamadas);
            $normaPaletizacaoEn->setNumNorma($numLastro * $numCamadas);
            $normaPaletizacaoEn->setUnitizador($unitizadorEn);
            $normaPaletizacaoEn->setNumPeso($numLastro*$numCamadas*$peso*$embalagemEn->getQuantidade());
            $this->_em->persist($normaPaletizacaoEn);
        } else {

            $normaPaletizacaoEn = new NormaPaletizacao();
            $normaPaletizacaoEn->setNumLastro($numLastro);
            $normaPaletizacaoEn->setNumCamadas($numCamadas);
            $normaPaletizacaoEn->setNumNorma($numLastro * $numCamadas);
            $normaPaletizacaoEn->setNumPeso($numLastro*$numCamadas*$peso*$embalagemEn->getQuantidade());
            $normaPaletizacaoEn->setUnitizador($unitizadorEn);
            $normaPaletizacaoEn->setIsPadrao("S");
            $this->_em->persist($normaPaletizacaoEn);

            $produtoDadoLogisticoEn = new DadoLogistico();
            $produtoDadoLogisticoEn->setAltura(0);
            $produtoDadoLogisticoEn->setCubagem(0);
            $produtoDadoLogisticoEn->setEmbalagem($embalagemEn);
            $produtoDadoLogisticoEn->setLargura(0);
            $produtoDadoLogisticoEn->setNormaPaletizacao($normaPaletizacaoEn);
            $produtoDadoLogisticoEn->setPeso(0);
            $produtoDadoLogisticoEn->setProfundidade(0);
            $this->_em->persist($produtoDadoLogisticoEn);
        }
    }

    public function checkEstoqueReservaById($id) {
        $dql = $this->_em->createQueryBuilder()
                ->select('NVL(e.id, rep.id)')
                ->from('wms:Produto\Embalagem', 'pe')
                ->leftJoin('wms:Enderecamento\Estoque', 'e', 'WITH', 'pe.id = e.produtoEmbalagem')
                ->leftJoin('wms:Ressuprimento\ReservaEstoqueProduto', 'rep', 'WITH', 'pe.id = rep.codProdutoEmbalagem')
                ->leftJoin("rep.reservaEstoque", 're')
                ->where("pe.id = :id and re.atendida = 'N'")
                ->setParameter('id', $id);

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
    public function getQtdEmbalagensProduto($codProduto, $grade, $qtd, $array = 0) {
        $arrayQtds = array();
        $embalagensEn = $this->findBy(array('codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null), array('quantidade' => 'DESC'));
        $qtdRestante = $qtd;
        $return = $qtd;
        $embFracDefault = null;
        if (!empty($embalagensEn)) {
            /**
             * @var int $key
             * @var Embalagem $embalagem
             */
            foreach ($embalagensEn as $key => $embalagem) {
                if ($embalagem->isEmbFracionavelDefault() == "S") {
                    $embFracDefault = $embalagem;
                }
                $qtdEmbalagem = $embalagem->getQuantidade();
                if (Math::compare(abs($qtdRestante), abs($qtdEmbalagem), '>=')) {
                    $resto = Math::resto($qtdRestante, $qtdEmbalagem);
                    $qtdSeparar = Math::dividir(Math::subtrair($qtdRestante, $resto), $qtdEmbalagem);
                    $qtdRestante = $resto;
                    if ($array === 0) {
                        if ($embalagem->getDescricao() != null) {
                            if ($embalagem->isEmbFracionavelDefault() != "S") {
                                $fatorEmb = $embalagem->getDescricao(). "(" . $embalagem->getQuantidade() . ")";
                            } else {
                                $fatorEmb = Produto::$listaUnidadeMedida[$embalagem->getProduto()->getUnidadeFracao()] . "S";
                            }
                            $arrayQtds[$embalagem->getId()] = $qtdSeparar . ' ' . $fatorEmb;
                        } else {
                            $arrayQtds[$embalagem->getId()] = $qtd;
                        }
                    } else {
                        if ($embalagem->getDescricao() == null) {
                            $qtdSeparar = $qtd;
                        }
                        $arrayQtds[$key]['idEmbalagem'] = $embalagem->getId();
                        $arrayQtds[$key]['qtd'] = $qtdSeparar;
                        $arrayQtds[$key]['dsc'] = $embalagem->getDescricao();
                        $arrayQtds[$key]['qtdEmbalagem'] = $embalagem->getQuantidade();
                    }
                } elseif ($qtd == 0) {
                    $arrayQtds[$embalagem->getId()] = 0; break;
                }
            }
            if (!empty($qtdRestante)) {

                if (!empty($embFracDefault)) {
                    if (isset($arrayQtds[$embFracDefault->getId()])) {
                        $pref = $arrayQtds[$embFracDefault->getId()];
                        $args = explode(' ', $pref);
                        $args[0] = Math::adicionar($args[0], $qtdRestante) ;
                        $arrayQtds[$embFracDefault->getId()] = implode(' ', $args);
                    } else {
                        if ($embalagem->isEmbFracionavelDefault() != "S") {
                            $fatorEmb = $embalagem->getDescricao(). "(" . $embalagem->getQuantidade() . ")";
                        } else {
                            $fatorEmb = Produto::$listaUnidadeMedida[$embalagem->getProduto()->getUnidadeFracao()] . "S";
                        }
                        if (Math::compare($qtdRestante, 1, '<')) {
                            $qtdRestante = (float) $qtdRestante;
                        }
                        $arrayQtds[$embFracDefault->getId()] = $qtdRestante . ' ' . $fatorEmb;
                    }
                } else {
                    $arrayQtds[0] = $qtdRestante . ' UN (1)';
                }


            }

            $return = $arrayQtds;
        }
        return $return;
    }

    public function getEmbalagemByCodigo($codigo) {
        $dql = $this->_em->createQueryBuilder()
                ->select('pe.id, pe.quantidade, de.descricao, pe.capacidadePicking, pe.embalado, p.referencia, p.descricao descricaoProduto')
                ->from('wms:Produto\Embalagem', 'pe')
                ->leftJoin('pe.endereco', 'de')
                ->innerJoin('wms:Produto', 'p', 'WITH', 'p.id = pe.codProduto AND p.grade = pe.grade')
                ->where("pe.codProduto = '$codigo'")
                ->orWhere("pe.codigoBarras = '$codigo'");

        return $dql->getQuery()->getResult();
    }

    public function getNormaPD($codProduto, $dscGrade)
    {
        $sql = "SELECT MAX(PE.QTD_EMBALAGEM * NP.NUM_NORMA) as NORMA, PE.COD_PRODUTO, PE.DSC_GRADE
                FROM PRODUTO_DADO_LOGISTICO PDL
                INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM
                INNER JOIN NORMA_PALETIZACAO NP ON NP.COD_NORMA_PALETIZACAO = PDL.COD_NORMA_PALETIZACAO
                WHERE PE.COD_PRODUTO = '$codProduto' AND PE.DSC_GRADE = '$dscGrade'
                GROUP BY PE.COD_PRODUTO, PE.DSC_GRADE";

        $result = $this->_em->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);

        return (!empty($result))? $result['NORMA'] : null;
    }

    /**
     * @param $idProduto
     * @param $grade
     * @return array
     * @throws \Exception
     */
    public function getCapacidadeAndPickingEmb($idProduto, $grade) {

        $sql = "SELECT DISTINCT COD_DEPOSITO_ENDERECO, CAPACIDADE_PICKING 
                FROM PRODUTO_EMBALAGEM WHERE COD_PRODUTO = '$idProduto' AND DSC_GRADE = '$grade' AND DTH_INATIVACAO IS NULL";

        $result = $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result))
            throw new \Exception("O produto $idProduto grade $grade não tem embalagem ativa");

        $idPicking = $result[0]['COD_DEPOSITO_ENDERECO'];
        $capacidade = $result[0]['CAPACIDADE_PICKING'];

        if (empty($idPicking))
            throw new \Exception("O produto $idProduto grade $grade não tem picking definido");

        if (empty($capacidade))
            throw new \Exception("O produto $idProduto grade $grade não tem capacidade de picking definida");

        $pickingEn = $this->_em->find("wms:Deposito\Endereco", $idPicking);

        return [$pickingEn, $capacidade];
    }

}
