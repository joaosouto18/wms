<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository;
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
        $codBarras = Coletor::adequaCodigoBarras($codBarras);
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));

        if (empty($embalagemEn)) {
            throw new \Exception('Embalagem não encontrada');
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

    public function setNormaPaletizacaoEmbalagem($codBarras, $numLastro, $numCamadas, $unitizador)
    {
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $codBarras = Coletor::adequaCodigoBarras($codBarras);
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));
        $produtoDadoLogisticoRepo = $this->getEntityManager()->getRepository('wms:Produto\DadoLogistico');
        $produtoDadoLogisticoEn = $produtoDadoLogisticoRepo->findOneBy(array('embalagem' => $embalagemEn));
        if (!$produtoDadoLogisticoEn)
            throw new \Exception('Dado Logistico nao cadastrado! Verifique com o PCE.');

        $unitizadorRepo = $this->_em->getRepository('wms:Armazenagem\Unitizador');
        $normaPaletizacaoEn = $produtoDadoLogisticoEn->getNormaPaletizacao();

        $unitizadorEn = $unitizadorRepo->find($unitizador);

        if (empty($embalagemEn)) {
            throw new \Exception('Embalagem não encontrada');
        }

        if ($normaPaletizacaoEn) {
            $normaPaletizacaoEn->setNumLastro($numLastro);
            $normaPaletizacaoEn->setNumCamadas($numCamadas);
            $normaPaletizacaoEn->setNumNorma($numLastro * $numCamadas);
            $normaPaletizacaoEn->setUnitizador($unitizadorEn);
            $this->_em->persist($normaPaletizacaoEn);
        }
        $this->_em->flush();
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
                }
            }
            if (!empty($qtdRestante) && !empty($embFracDefault)) {
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
                WHERE PE.COD_PRODUTO = $codProduto AND PE.DSC_GRADE = '$dscGrade'
                GROUP BY PE.COD_PRODUTO, PE.DSC_GRADE";

        $result = $this->_em->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);

        return (!empty($result))? $result['NORMA'] : null;
    }
}
