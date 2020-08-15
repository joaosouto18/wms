<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto,
    Wms\Domain\Entity\Produto\Embalagem as EmbalagemEntity,
    Wms\Domain\Entity\Produto\NormaPaletizacao as NormaPaletizacaoEntity,
    Doctrine\Common\Persistence\ObjectRepository,
    Doctrine\ORM\Id\SequenceGenerator,
    Wms\Util\CodigoBarras,
    Wms\Util\Endereco as EnderecoUtil,
    Wms\Util\Coletor as ColetorUtil,
    Core\Util\Produto as ProdutoUtil,
    DoctrineExtensions\Versionable\Exception,
    Wms\Domain\Entity\CodigoFornecedor\Referencia,
    Wms\Domain\Entity\Deposito\Endereco,
    Wms\Domain\Entity\Produto\Embalagem;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\Enderecamento\Modelo;
use Wms\Math;

/**
 *
 */
class ProdutoRepository extends EntityRepository implements ObjectRepository {

    /**
     * Persiste dados produto no sistema
     *
     * @param Produto $produtoEntity
     * @param array $values valores vindo de um formulário
     */
    public function atualizaPesoProduto($codProduto, $dscGrade) {

        $procedureSQL = "CALL PROC_ATUALIZA_PESO_PRODUTO('$codProduto','$dscGrade')";

        $procedure = $this->getEntityManager()->getConnection()->prepare($procedureSQL);
        $procedure->execute();
        $this->getEntityManager()->flush();
    }

    public function getProdutosSemPickingByExpedicoes($expedicoes) {
        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();

        $produtosRessuprir = $this->getEntityManager()->getRepository("wms:Expedicao")->getProdutosSemOnda($expedicoes, $central);
        $produtosSemPicking = array();

        foreach ($produtosRessuprir as $produto) {
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];

            $produtoEn = $this->findOneBy(array('id' => $codProduto, 'grade' => $grade));
            $idPicking = $this->getEnderecoPicking($produtoEn, "ID");
            if ($idPicking == NULL) {
                $produtoSp = array();
                $produtoSp['Codigo'] = $codProduto;
                $produtoSp['Grade'] = $grade;
                $produtoSp['Produto'] = $produtoEn->getDescricao();
                $produtoSp['Quantidade'] = $produto['QTD'];
                $produtosSemPicking[] = $produtoSp;
            }
        }

        return $produtosSemPicking;
    }

    private function setParamEndAutomatico($produtoEn, $values, $tipo) {
        if ($tipo == 'AreaArmazenagem') {
            $repo = $this->getEntityManager()->getRepository('wms:Produto\EnderecamentoAreaArmazenagem');
        }
        if ($tipo == 'TipoEndereco') {
            $repo = $this->getEntityManager()->getRepository('wms:Produto\EnderecamentoTipoEndereco');
        }
        if ($tipo == 'TipoEstrutura') {
            $repo = $this->getEntityManager()->getRepository('wms:Produto\EnderecamentoTipoEstrutura');
        }
        if ($tipo == 'CaracteristicaEndereco') {
            $repo = $this->getEntityManager()->getRepository('wms:Produto\EnderecamentoCaracteristicaEndereco');
        }

        $registros = $repo->findBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade()));
        foreach ($registros as $registro) {
            $this->getEntityManager()->remove($registro);
        }

        foreach ($values as $key => $value) {
            if (($value != "") && (is_numeric($value))) {
                if ($tipo == 'AreaArmazenagem') {
                    $sequencia = new Produto\EnderecamentoAreaArmazenagem();
                    $sequencia->setCodAreaArmazenagem($key);
                }
                if ($tipo == 'TipoEndereco') {
                    $sequencia = new Produto\EnderecamentoTipoEndereco();
                    $sequencia->setCodTipoEndereco($key);
                }
                if ($tipo == 'TipoEstrutura') {
                    $sequencia = new Produto\EnderecamentoTipoEstrutura();
                    $sequencia->setCodTipoEstrutura($key);
                }
                if ($tipo == 'CaracteristicaEndereco') {
                    $sequencia = new Produto\EnderecamentoCaracteristicaEndereco();
                    $sequencia->setCodCaracteristica($key);
                }
                $sequencia->setCodProduto($produtoEn->getId());
                $sequencia->setGrade($produtoEn->getGrade());
                $sequencia->setPrioridade($value);
                $this->getEntityManager()->persist($sequencia);
            }
        }
    }

	protected  function saveFornecedorReferencia($em, $dados, $produtoEntity)
	{
		$idProduto = $produtoEntity->getIdProduto();
		$fornecedorRefRepo  = $this->_em->getRepository('wms:CodigoFornecedor\Referencia');

        foreach ($dados['fornecedor'] as $key => $fornecedorRef) {

            /** @var Embalagem $embalagem */
            $fornRefEntity = $fornecedorRefRepo->findBy(array('fornecedor' => $fornecedorRef['id'], 'idProduto' => $idProduto));
            if (!$fornRefEntity) {
                $fornRefEntity = new Referencia();
                $fornRefEntity->setIdProduto($idProduto);
                $fornRefEntity->setEmbalagem($em->getReference('wms:Produto\Embalagem', $fornecedorRef['embalagem']));
                $fornRefEntity->setFornecedor($em->getReference('wms:Pessoa\Papel\Fornecedor', $fornecedorRef['id']));
            }
            $fornRefEntity->setDscReferencia($fornecedorRef['cod']);

            $em->persist($fornRefEntity);
        }
    }

    public function save(Produto $produtoEntity, array $values) {

        extract($values['produto']);

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {

            $dscEndereco = $values['enderecamento']['enderecoReferencia'];
            if ($dscEndereco != "") {
                $enderecoEn = $this->getEntityManager()->getRepository("wms:Deposito\Endereco")->findOneBy(array('descricao' => $dscEndereco));
                if ($enderecoEn == null) {
                    throw new \Exception("Endereço de referencia para endereçamento automático inválido");
                } else {
                    $produtoEntity->setEnderecoReferencia($enderecoEn);
                }
            } else {
                $produtoEntity->setEnderecoReferencia(null);
            }

            if (isset($values['areaArmazenagem']) && !empty($values['areaArmazenagem']))
                $this->setParamEndAutomatico($produtoEntity, $values['areaArmazenagem'], 'AreaArmazenagem');

            if (isset($values['estruturaArmazenagem']) && !empty($values['estruturaArmazenagem']))
                $this->setParamEndAutomatico($produtoEntity, $values['estruturaArmazenagem'], 'TipoEstrutura');

            if (isset($values['tipoEndereco']) && !empty($values['tipoEndereco']))
                $this->setParamEndAutomatico($produtoEntity, $values['tipoEndereco'], 'TipoEndereco');

            if (isset($values['caracteristicaEndereco']) && !empty($values['caracteristicaEndereco']))
                $this->setParamEndAutomatico($produtoEntity, $values['caracteristicaEndereco'], 'CaracteristicaEndereco');

            $linhaSeparacaoEntity = $em->getReference('wms:Armazenagem\LinhaSeparacao', $idLinhaSeparacao);
            $tipoComercializacaoEntity = $em->getReference('wms:Produto\TipoComercializacao', $idTipoComercializacao);

            $produtoEntity->setLinhaSeparacao($linhaSeparacaoEntity);
            $produtoEntity->setTipoComercializacao($tipoComercializacaoEntity);
            $produtoEntity->setNumVolumes($numVolumes);
            $produtoEntity->setReferencia($referencia);
            $produtoEntity->setCodigoBarrasBase($codigoBarrasBase);
            $produtoEntity->setPossuiPesoVariavel((isset($possuiPesoVariavel) && !empty($possuiPesoVariavel)) ? $possuiPesoVariavel : "N");
            $produtoEntity->setIndFracionavel((isset($indFracionavel) && !empty($indFracionavel))? $indFracionavel : 'N');
            $produtoEntity->setIndControlaLote((isset($indControlaLote) && !empty($indControlaLote))? $indControlaLote : 'N');
            $produtoEntity->setForcarEmbVenda((isset($forcarEmbVenda))? $forcarEmbVenda : null);

            if ($produtoEntity->getId() == null) {
                $sqcGenerator = new SequenceGenerator("SQ_PRODUTO_01", 1);
                $produtoEntity->setIdProduto($sqcGenerator->generate($em, $produtoEntity));
            }

            if (isset($values['fornecedor']) && !empty($values['fornecedor']))
                $this->saveFornecedorReferencia($em, $values, $produtoEntity);

            $em->persist($produtoEntity);

            switch ($idTipoComercializacao) {
                case Produto::TIPO_UNITARIO:
                    // limpo os volumes se houver
                    /** @var Produto\VolumeRepository $volumeRepo */
                    $volumeRepo = $em->getRepository('wms:Produto\Volume');
                    $volumes = $volumeRepo->findBy(array('codProduto' => $produtoEntity->getId(), 'grade' => $produtoEntity->getGrade()));

                    /** @var Produto\Volume $volumeEntity */
                    foreach ($volumes as $volumeEntity) {
                        list($status, $msg) = $volumeRepo->checkEstoqueReservaById($volumeEntity->getId());
                        if ($status === 'error')
                            throw new \Exception($msg);
                        $em->remove($volumeEntity);
                    }

                    // gravo embalagens
                    $result = $this->persistirEmbalagens($produtoEntity, $values);

                    if (is_string($result)) {
                        $em->rollback();
                        return $result;
                    }

                    //aguardando teste por parte da simonetti
                    //WebService da Simonetti para persistir os dados logisticos
                    if ($this->getSystemParameterValue("CONSOME_WEBSERVICE_PRODUTOS") == "S") {
                        $retorno = $this->enviaDadosLogisticosEmbalagem($produtoEntity);

                        //Se estiver em ambiente de desenvolvimento lanço o debug na tela
                        if (array_key_exists('erro', $retorno) && APPLICATION_ENV == 'development')
                            throw new \Exception($retorno['debug']);

                        //Se não for ambiente de desenvolvimento lanço apenas a mensagem de erro
                        if (array_key_exists('erro', $retorno) && APPLICATION_ENV !== 'development')
                            throw new \Exception($retorno['debug']);
                    }

                    break;
                case Produto::TIPO_COMPOSTO:
                    // limpo os embalagens se houver
                    /** @var Produto\EmbalagemRepository $embalagemRepo */
                    $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
                    $embalagens = $embalagemRepo->findBy(array('codProduto' => $produtoEntity->getId(), 'grade' => $produtoEntity->getGrade()));

                    /** @var Embalagem $embalagemEntity */
                    foreach ($embalagens as $embalagemEntity) {
                        list($status, $msg) = $embalagemRepo->checkEstoqueReservaById($embalagemEntity->getId());
                        if ($status === 'error')
                            throw new \Exception($msg);
                        $em->remove($embalagemEntity);
                    }

                    // gravo volumes
                    $this->persistirVolumes($produtoEntity, $values);

                    //aguardando teste por parte da simonetti
                    //WebService da Simonetti para persistir os dados logisticos
                    if ($this->getSystemParameterValue("CONSOME_WEBSERVICE_PRODUTOS") == "S") {
                        $retorno = $this->enviaDadosLogisticosVolumes($produtoEntity, $values);

                        //Se estiver em ambiente de desenvolvimento lanço o debug na tela
                        if (array_key_exists('erro', $retorno) && APPLICATION_ENV == 'development')
                            throw new Exception($retorno['debug']);

                        //Se não for ambiente de desenvolvimento lanço apenas a mensagem de erro
                        if (array_key_exists('erro', $retorno) && APPLICATION_ENV !== 'development')
                            throw new Exception($retorno['erro']);
                    }

                    break;
            }

            $em->flush();
            $this->atualizaPesoProduto($produtoEntity->getId(), $produtoEntity->getGrade());
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    /**
     * Persiste as embalagens do produto
     *
     * @param Produto $produtoEntity
     * @param array $values
     * @return boolean
     */
    public function persistirEmbalagens(Produto $produtoEntity, array &$values, $webservice = false, $flush = true, $repositorios = null) {
        try {

            $em = $this->getEntityManager();
            if ($webservice == true) {
                $idUsuario = null;
            } else {
                $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
            }

            if ($repositorios == null) {
                /** @var \Wms\Domain\Entity\Produto\AndamentoRepository $andamentoRepo */
                $andamentoRepo = $em->getRepository('wms:Produto\Andamento');

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');

                /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
                $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
            } else {
                /** @var \Wms\Domain\Entity\Produto\AndamentoRepository $andamentoRepo */
                $andamentoRepo = $repositorios['produtoAndamentoRepo'];

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $repositorios['enderecoRepo'];

                /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
                $embalagemRepo = $repositorios['embalagemRepo'];
            }

            $usarCaracterEspecial = ($this->getSystemParameterValue("CARACTERE_ESPECIAL_COD_BARRAS") == 'S');

            //embalagens do produto
            if (!(isset($values['embalagens']) && (count($values['embalagens']) > 0)))
                return false;

            $embsEditadas = [];
            $arrItens = [];

            $removeExponential = function ($val) {
                $exponentialPos = strpos($val, "E-");
                if ($exponentialPos) {
                    $exp = substr($val, ( $exponentialPos + 2));
                    return number_format($val, $exp, ",", ".");
                } else  {
                    return str_replace(".", ",", floatval(number_format($val, 11)));
                }
            };

            $menorEmb = new \stdClass();
            $menorEmb->id = null;
            $menorEmb->qtd = 9999999999999;

            foreach ($values['embalagens'] as $id => $itemEmbalagem) {
                if (isset($itemEmbalagem['quantidade']))
                    $itemEmbalagem['quantidade'] = str_replace(',', '.', $itemEmbalagem['quantidade']);

                $altura = 0;
                $largura = 0;
                $profundidade = 0;
                $peso = 0;

                extract($itemEmbalagem);

                if ($itemEmbalagem['acao'] != 'excluir') {
                    $check = self::checkCodBarrasRepetido($codigoBarras, Produto::TIPO_UNITARIO, $id);
                    if (!empty($check)) {
                        foreach ($check as $produto) {
                            if (!isset($embsEditadas[$produto['id_emb']]) || (isset($embsEditadas[$produto['id_emb']]) && $embsEditadas[$produto['id_emb']] == $codigoBarras))
                                $arrItens[$produto['id_emb']][$codigoBarras][] = "item $produto[idProduto] / $produto[grade] ($produto[dsc_elemento])";
                        }
                    } elseif (isset($arrItens[$id]) && !isset($arrItens[$id][$codigoBarras])) {
                        unset($arrItens[$id]);
                    }
                }
                $embalagemEntity = null;
                switch ($itemEmbalagem['acao']) {
                    case 'incluir':
                        $dadosEmbalagem = $embalagemRepo->findOneBy(array('codProduto' => $produtoEntity->getId(), 'grade' => $produtoEntity->getGrade()));

                        if(!empty($dadosEmbalagem)) {
                            $pontoReposicao = !empty($pontoReposicao) ? $pontoReposicao : $dadosEmbalagem->getPontoReposicao();
                            $capacidadePicking = !empty($capacidadePicking) ? $capacidadePicking : $dadosEmbalagem->getCapacidadePicking();
                            $endereco = $dadosEmbalagem->getEndereco();
                            $endereco = !empty($endereco) ? $endereco->getDescricao() : null;
                            $altura = !empty($altura) ? $altura : str_replace('.', ',', Math::multiplicar(Math::dividir(str_replace(',', '.', $dadosEmbalagem->getAltura()), str_replace(',', '.', $dadosEmbalagem->getQuantidade())), str_replace(',', '.', $quantidade)));
                            $largura = !empty($largura) ? $largura : str_replace('.', ',', Math::multiplicar(Math::dividir(str_replace(',', '.', $dadosEmbalagem->getLargura()), str_replace(',', '.', $dadosEmbalagem->getQuantidade())), str_replace(',', '.', $quantidade)));
                            $profundidade = !empty($profundidade) ? $profundidade : str_replace('.', ',', Math::multiplicar(Math::dividir(str_replace(',', '.', $dadosEmbalagem->getProfundidade()), str_replace(',', '.', $dadosEmbalagem->getQuantidade())), str_replace(',', '.', $quantidade)));
                            $peso = !empty($peso) ? $peso : str_replace('.', ',', Math::multiplicar(Math::dividir(str_replace(',', '.', $dadosEmbalagem->getPeso()), str_replace(',', '.', $dadosEmbalagem->getQuantidade())), str_replace(',', '.', $quantidade)));
                        }

                        $embalagemEntity = new EmbalagemEntity;
                        $embalagemEntity->generateId($em);
                        $embalagemEntity->setProduto($produtoEntity);
                        $embalagemEntity->setGrade($produtoEntity->getGrade());
                        $embalagemEntity->setDescricao($descricao);
                        $embalagemEntity->setQuantidade($quantidade);
                        $embalagemEntity->setIsPadrao($isPadrao);
                        $embalagemEntity->setCBInterno($CBInterno);
                        $embalagemEntity->setImprimirCB($imprimirCB);
                        $embalagemEntity->setEmbalado($embalado);
                        $embalagemEntity->setCapacidadePicking($capacidadePicking);
                        $embalagemEntity->setPontoReposicao($pontoReposicao);
                        $embalagemEntity->setIsEmbExpDefault((isset($isEmbExpDefault) && !empty($isEmbExpDefault))?$isEmbExpDefault: 'N');
                        $embalagemEntity->setIsEmbFracionavelDefault((isset($isEmbFracionavelDefault) && !empty($isEmbFracionavelDefault))?$isEmbFracionavelDefault: 'N');

                        if (isset($isEmbFracionavelDefault) && $isEmbFracionavelDefault == "S") {
                            $codigoBarras = $produtoEntity->getId();
                            if ($usarCaracterEspecial) {
                                $codigoBarras .="#";
                            }
                        } elseif ($CBInterno == 'S') {
                            $codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem("20" . $embalagemEntity->getId());
                        }
                        $embalagemEntity->setCodigoBarras(trim($codigoBarras));

                        if (isset($largura) && !empty($largura)) {
                            $embalagemEntity->setLargura($removeExponential($largura));
                        }
                        if (isset($altura) && !empty($altura)) {
                            $embalagemEntity->setAltura($removeExponential($altura));
                        }
                        if (isset($peso) && !empty($peso)) {
                            $embalagemEntity->setPeso($removeExponential($peso));
                        }
                        if (isset($profundidade) && !empty($profundidade)) {
                            $embalagemEntity->setProfundidade($removeExponential($profundidade));
                        }
                        $cubagem = Math::multiplicar(Math::multiplicar(str_replace(',', '.', $altura), str_replace(',', '.', $largura)), str_replace(',', '.', $profundidade));
                        if (isset($cubagem) && !empty($cubagem)) {
                            $embalagemEntity->setCubagem($removeExponential($cubagem));
                        }

                        //valida o endereco informado
                        if (!empty($endereco)) {
                            $endereco = EnderecoUtil::separar($endereco);
                            /** @var Endereco $enderecoEntity */
                            $enderecoEntity = $enderecoRepo->findOneBy($endereco);

                            if (!$enderecoEntity) {
                                throw new \Exception('Não existe o Endereço informado na embalagem ' . $descricao);
                            }

                            if ($enderecoEntity->liberadoPraSerPicking()) {
                                $embalagemEntity->setEndereco($enderecoEntity);
                            }

                        }

                        if (isset($itemEmbalagem['ativarDesativar']) && !empty($itemEmbalagem['ativarDesativar'])) {
                            if ($webservice == true) {
                                $embalagemEntity->setDataInativacao(null);
                                $embalagemEntity->setUsuarioInativacao($idUsuario);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Desativado com sucesso', true, $webservice);
                            } elseif (is_null($embalagemEntity->getDataInativacao())) {
                                $embalagemEntity->setDataInativacao(new \DateTime());
                                $embalagemEntity->setUsuarioInativacao($idUsuario);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Desativado com sucesso', false, $webservice);
                            }
                        } else {
                            if (!is_null($embalagemEntity->getDataInativacao())) {
                                $embalagemEntity->setDataInativacao(null);
                                $embalagemEntity->setUsuarioInativacao(null);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Ativado com sucesso', false, $webservice);
                            }
                        }

                        $em->persist($embalagemEntity);
                        if ($flush == true)
                            $em->flush();

                        if ($embalagemEntity->getIsPadrao() === 'S') {
                            $result = $embalagemRepo->checkEmbalagemDefault($embalagemEntity);
                            if (!is_bool($result))
                                throw $result;
                        }

                        $produtoEntity->addEmbalagem($embalagemEntity);

                        $idEmbalagem = $embalagemEntity->getId();
                        $values['embalagens'][$id]['id'] = $idEmbalagem;
                        if (!empty($values['dadosLogisticos'])) {
                            foreach ($values['dadosLogisticos'] as $key => $dadoLogistico) {
                                $values['dadosLogisticos'][$key]['idEmbalagem'] = $idEmbalagem;
                            }
                        }

                        if ($embalagemEntity->getQuantidade() < $menorEmb->qtd) {
                            $menorEmb->qtd = $embalagemEntity->getQuantidade();
                            $menorEmb->id = $idEmbalagem;
                        }

                        break;
                    case 'alterar':

                        $embalagemEntity = $em->getReference('wms:Produto\Embalagem', $id);

                        Configurator::configure($embalagemEntity, $itemEmbalagem);

                        $embalagemEntity->setEndereco(null);

                        //valida o endereco informado
                        if (!empty($endereco)) {
                            $endereco = EnderecoUtil::separar($endereco);
                            $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
                            $enderecoEntity = $enderecoRepo->findOneBy($endereco);

                            if (!$enderecoEntity) {
                                throw new \Exception('Não existe o Endereço informado na embalagem ' . $descricao);
                            }

                            if ($enderecoEntity->liberadoPraSerPicking()) {
                                $embalagemEntity->setEndereco($enderecoEntity);
                            }
                        }

                        // verifica se o codigo de barras é automatico
                        if ($CBInterno == 'S') {
                            $codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem("20" . $id);
                        }

                        $embalagemEntity->setEmbalado($embalado);
                        $embalagemEntity->setCapacidadePicking($capacidadePicking);
                        $embalagemEntity->setPontoReposicao($pontoReposicao);
                        $embalagemEntity->setCodigoBarras(trim($codigoBarras));

                        if (isset($largura) && !empty($largura)) {
                            $embalagemEntity->setLargura($removeExponential($largura));
                        }
                        if (isset($altura) && !empty($altura)) {
                            $embalagemEntity->setAltura($removeExponential($altura));
                        }
                        if (isset($peso) && !empty($peso)) {
                            $embalagemEntity->setPeso($removeExponential($peso));
                        }
                        if (isset($profundidade) && !empty($profundidade)) {
                            $embalagemEntity->setProfundidade($removeExponential($profundidade));
                        }

                        if (isset($largura) && isset($altura) && isset($peso) && isset($profundidade)) {
                            $cubagem = Math::multiplicar(Math::multiplicar(str_replace(',', '.', $altura), str_replace(',', '.', $largura)), str_replace(',', '.', $profundidade));
                            if (!empty($cubagem)) {
                                $embalagemEntity->setCubagem($removeExponential($cubagem));
                            }
                        }

                        if (isset($itemEmbalagem['ativarDesativar']) && !empty($itemEmbalagem['ativarDesativar'])) {
                            if ($webservice == true) {
                                $embalagemEntity->setDataInativacao(null);
                                $embalagemEntity->setUsuarioInativacao($idUsuario);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto ativado com sucesso', false, $webservice);
                            } elseif (is_null($embalagemEntity->getDataInativacao())) {
                                $embalagemEntity->setDataInativacao(new \DateTime());
                                $embalagemEntity->setUsuarioInativacao($idUsuario);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Desativado com sucesso', false, $webservice);
                            }
                        } else {
                            if ($webservice == true) {
                                if (is_null($embalagemEntity->getDataInativacao())) {
                                    $embalagemEntity->setDataInativacao(new \DateTime());
                                    $embalagemEntity->setUsuarioInativacao(null);
                                    $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto desativado com sucesso', false, $webservice);
                                }
                            } else {
                                if (!is_null($embalagemEntity->getDataInativacao())) {
                                    $embalagemEntity->setDataInativacao(null);
                                    $embalagemEntity->setUsuarioInativacao(null);
                                    $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Ativado com sucesso', false, $webservice);
                                }
                            }
                        }

                        if (isset($descricao) && ($descricao != null)) {
                            $embalagemEntity->setDescricao($descricao);
                        }

                        $em->persist($embalagemEntity);

                        if ($embalagemEntity->getIsPadrao() === 'S') {
                            $result = $embalagemRepo->checkEmbalagemDefault($embalagemEntity);
                            if (!is_bool($result))
                                throw $result;
                        }

                        break;
                    case 'excluir':

                        $embalagemEntity = $em->getRepository('wms:Produto\Embalagem')->find($id);

                        if (!$embalagemEntity) {
                            throw new \Exception('Codigo da Embalagem inválido.');
                        }
                        try {
                            $em->remove($embalagemEntity);
                            if ($flush == true)
                                $em->flush();
                        } catch (\Exception $e) {
                            $previus = $e->getPrevious();
                            if ($previus->getCode() == 2292) {
                                $return = "A embalagem com código de barras " . $embalagemEntity->getCodigoBarras() . ' não pode ser excluida por estar ligada à um fornecedor.';
                                return $return;
                            }
                        }
                        break;

                    default:
                        $embalagemEntity = $em->getReference('wms:Produto\Embalagem', $id);

                        if (isset($itemEmbalagem['ativarDesativar']) && !empty($itemEmbalagem['ativarDesativar'])) {
                            if (is_null($embalagemEntity->getDataInativacao())) {
                                $embalagemEntity->setDataInativacao(new \DateTime());
                                $embalagemEntity->setUsuarioInativacao($idUsuario);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Desativado com sucesso', false, true);
                            }
                        } else {
                            if (!is_null($embalagemEntity->getDataInativacao())) {
                                $embalagemEntity->setDataInativacao(null);
                                $embalagemEntity->setUsuarioInativacao(null);
                                $andamentoRepo->save($embalagemEntity->getProduto()->getId(), $embalagemEntity->getGrade(), $idUsuario, 'Produto Ativado com sucesso', false, true);
                            }
                        }

                        $em->persist($embalagemEntity);
                        break;
                }

                if ($itemEmbalagem['acao'] != 'excluir') {
                    $embsEditadas[$embalagemEntity->getId()] = $embalagemEntity->getCodigoBarras();
                }

                $altura = null;
                $largura = null;
                $profundidade = null;
                $cubagem = null;
                $peso = null;
            }
            if (!empty($arrItens)) {
                $arrStr = [];
                foreach($arrItens as $cods) {
                    foreach ($cods as $codigoBarras => $eStr) {
                        $arrStr[] = "O codigo de barras $codigoBarras já está cadastrado: " . implode(", ", $eStr);
                    }
                }

                if (!empty($arrStr)) {
                    throw new \Exception("Houve os seguintes problemas: " . implode(", ", $arrStr));
                }
            }

            // Se não vier dado logístico ou norma de paletizacao no array e não existir uma válida cadastrada cria uma padrão
            if ((empty($values['normasPaletizacao']) || empty($values['dadosLogisticos']))
                && empty($this->checkTemNormaAndDadoLogistico($produtoEntity->getId(), $produtoEntity->getGrade()))) {

                $unitizador = $this->_em->getRepository("wms:Armazenagem\Unitizador")->findBy([], ['capacidade' => 'DESC'])[0];
                if (empty($unitizador)) throw new \Exception("Não foi encontrado nenhum unitizador para o cadastro de dado logistico padrão");

                $embsArray = $embalagemRepo->findBy(['codProduto' => $produtoEntity->getId(), 'grade' => $produtoEntity->getGrade(), 'dataInativacao' => null], ['quantidade' => 'ASC']);
                if (!empty($embsArray)) {
                    $menorEmb->id = $embsArray[0]->getId();
                    $menorEmb->qtd = $embsArray[0]->getQuantidade();
                }

                $idDefaultPDL = "-default" . time();
                $idDefaultNP = "-default" . time();
                $values['dadosLogisticos'][$idDefaultPDL] = [
                    'acao' => 'incluir',
                    'id' => $idDefaultPDL,
                    'idEmbalagem' => $menorEmb->id,
                    'qtdEmbalagem' => $menorEmb->qtd,
                    'idNormaPaletizacao' => $idDefaultNP,
                    'largura' => 0,
                    'altura' => 0,
                    'profundidade' => 0,
                    'cubagem' => 0,
                    'peso' => 0
                ];
                $values['normasPaletizacao'][$idDefaultNP] = [
                    "acao" => "incluir",
                    "id" => $idDefaultNP,
                    "idUnitizadorTemp" => $unitizador->getId(),
                    "idUnitizador" => $unitizador->getId(),
                    'isPadrao' => "S",
                    'numLastro' => 999,
                    'numCamadas' => 999,
                    'numNorma' => 998001,
                    'numPeso' => 0
                ];
            }
            // gravo dados logisticos
            $this->persistirDadosLogisticos($values, $produtoEntity);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Persiste as volumes do produto
     *
     * @param Produto $produtoEntity
     * @param array $values
     * @return boolean
     */
    public function persistirVolumes(Produto $produtoEntity, array &$values, $webservice = false) {
        $em = $this->getEntityManager();
        extract($values);

        // volumes
        if (!isset($volumes))
            return false;

        $normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

        // normas de paletizacao
        if (isset($normasPaletizacao)) {

            $andamentoRepo = $this->_em->getRepository('wms:Produto\Andamento');
            $idProduto = $produtoEntity->getID();
            $grade = $produtoEntity->getGrade();

            foreach ($normasPaletizacao as $key => $normaPaletizacao) {
                extract($normaPaletizacao);

                if (!isset($acao))
                    continue;

                switch ($acao) {
                    case 'incluir':
                        $normaPaletizacaoEntity = new NormaPaletizacaoEntity;
                        $en = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
                        $normasPaletizacao[$key]['id'] = $en->getId();

                        if ($normaPaletizacaoEntity != $en) {
                            $andamentoRepo->save($idProduto, $grade, false, 'Norma de paletização incluida. Unitizador:' . $normaPaletizacaoEntity->getUnitizador()->getDescricao() . ' Norma:' . $normaPaletizacaoEntity->getNumNorma());
                        }
                        break;
                    case 'alterar':

                        $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $id);
                        $en = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
                        $normasPaletizacao[$key]['id'] = $en->getId();

                        if ($en != $normaPaletizacaoEntity) {
                            $andamentoRepo->save($idProduto, $grade, false, 'Norma de paletização alterada. Unitizador:' . $normaPaletizacaoEntity->getUnitizador()->getDescricao() . ' Norma:' . $normaPaletizacaoEntity->getNumNorma());
                        }
                        break;
                }
            }
        }

        $volumeRepo = $em->getRepository('wms:Produto\Volume');

        foreach ($volumes as $id => $itemVolume) {
            extract($itemVolume);

            if (!isset($acao))
                continue;

            if ($itemVolume['acao'] != 'excluir') {
                $check = self::checkCodBarrasRepetido($codigoBarras, Produto::TIPO_COMPOSTO, $id);
                if(!empty($check)){
                    $arrItens = [];
                    foreach ($check as $produto) {
                        $arrItens[] = "item $produto[idProduto] / $produto[grade] ($produto[dsc_elemento])";
                    }
                    $str = implode(", ", $arrItens);
                    throw new \Exception("O codigo de barras $codigoBarras já está cadastrado: $str");
                }
            }
            // id
//            $itemVolume['id'] = $id;
            // pega infos de normas de produtos
            if (!empty($itemVolume['idNormaPaletizacao']))
                $itemVolume['idNormaPaletizacao'] = $normasPaletizacao[$itemVolume['idNormaPaletizacao']]['id'];

            switch ($acao) {
                case 'incluir':
                    $volumeRepo->save($produtoEntity, $itemVolume, $webservice);
                    break;
                case 'alterar':
                    $volumeRepo->save($produtoEntity, $itemVolume, $webservice);
                    break;
                case 'excluir':
                    $volumeRepo->remove($id);
                    break;
            }
        }

        // normas de paletizacao
        if (isset($normasPaletizacao)) {

            foreach ($normasPaletizacao as $key => $normaPaletizacao) {
                extract($normaPaletizacao);

                if (!isset($acao))
                    continue;

                switch ($acao) {
                    case 'excluir':
                        $andamentoRepo->save($idProduto, $grade, false, 'Norma de paletização excluida. Unitizador:
			  ' . $normaPaletizacaoEntity->getUnitizador()->getDescricao() . 'Norma:' . $normaPaletizacaoEntity->getNumNorma());
                        $normaPaletizacaoEntity = $normaPaletizacaoRepo->remove($id);
                        break;
                }
            }
        }

        return true;
    }

    /**
     * Persiste as dadosLogisticos do produto
     * @param Produto $produtoEntity
     * @param array $values
     */
    public function persistirDadosLogisticos(array &$values, $produtoEntity) {
        $em = $this->getEntityManager();
        extract($values);
        // dadosLogisticos
        if (!isset($dadosLogisticos))
            return false;

        $normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');
        $dadoLogisticoRepo = $em->getRepository('wms:Produto\DadoLogistico');
        $normasExistentes = $dadoLogisticoRepo->getDadoNorma($produtoEntity->getId(), $produtoEntity->getGrade());
        // normas de paletizacao
        if (isset($normasPaletizacao)) {
            foreach ($normasPaletizacao as $id => $normaPaletizacao) {
                if(isset($arrayNP[$normaPaletizacao['id']])){
                    if($normaPaletizacao['acao'] != 'excluir'){
                        unset($normasPaletizacao[$id]);
                    }
                }else{
                    $arrayNP[$normaPaletizacao['id']] = 1;
                }
            }
            if(!empty($normasExistentes) && isset($normasPaletizacao)){
                foreach ($normasExistentes as $key => $value){
                    if(isset($normaAturizada[$value['COD_NORMA_PALETIZACAO']])){
                        $dadoLogisticoRepo->remove($value['COD_PRODUTO_DADO_LOGISTICO']);
                    }
                    if(!isset($normasPaletizacao[$value['COD_NORMA_PALETIZACAO']])){
                        $dadoLogisticoRepo->remove($value['COD_PRODUTO_DADO_LOGISTICO']);
                        if($value['COD_NORMA_PALETIZACAO'] != null) {
                            $normaPaletizacaoRepo->remove($value['COD_NORMA_PALETIZACAO']);
                        }
                    }
                    $normaAturizada[$value['COD_NORMA_PALETIZACAO']] = 1;
                }
            }
            $andamentoRepo = $this->_em->getRepository('wms:Produto\Andamento');
            $idProduto = $produtoEntity->getID();
            $grade = $produtoEntity->getGrade();
            foreach ($normasPaletizacao as $key => $normaPaletizacao) {
                extract($normaPaletizacao);
                if (!isset($acao))
                    continue;
                switch ($acao) {
                    case 'incluir':
                        $normaPaletizacaoEntity = new NormaPaletizacaoEntity;
                        $en = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
                        $normasPaletizacao[$key]['id'] = $en->getId();
                        if ($en != $normaPaletizacaoEntity) {
                            $andamentoRepo->save($idProduto, $grade, false, 'Norma de paletização incluida. Unitizador:' . $normaPaletizacaoEntity->getUnitizador()->getDescricao() . ' Norma:' . $normaPaletizacaoEntity->getNumNorma());
                        }
                        if (strpos($key, "-default") !== false) {
                            $normasPaletizacao[$en->getId()] = $normasPaletizacao[$key];
                        }
                        break;
                    case 'alterar':
                        $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $id);
                        $en = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
                        $normasPaletizacao[$key]['id'] = $en->getId();
                        if ($en != $normaPaletizacaoEntity) {
                            $andamentoRepo->save($idProduto, $grade, false, 'Norma de paletização alterada. Unitizador:' . $normaPaletizacaoEntity->getUnitizador()->getDescricao() . ' Norma:' . $normaPaletizacaoEntity->getNumNorma());
                        }
                        break;
                }
            }
        }
        foreach ($dadosLogisticos as $id => $itemDadoLogistico) {
            if(isset($arrayE[number_format($itemDadoLogistico['qtdEmbalagem'], 3, '.', '')]) && ($normasPaletizacao[$itemDadoLogistico['idNormaPaletizacao']]['idUnitizador'] == $arrayE[number_format($itemDadoLogistico['qtdEmbalagem'], 3, '.', '')])){
                if($itemDadoLogistico['acao'] == 'alterar'){
                    $dadoLogisticoRepo->remove($id);
                }
                if($itemDadoLogistico['acao'] != 'excluir'){
                    unset($dadosLogisticos[$id]);
                }
            }
            if(isset($arrayD[$itemDadoLogistico['idNormaPaletizacao']])){
                if($itemDadoLogistico['acao'] != 'excluir'){
                    unset($dadosLogisticos[$id]);
                }
                if($itemDadoLogistico['acao'] == 'alterar'){
                    $normaPaletizacaoRepo->remove($itemDadoLogistico['idNormaPaletizacao']);
                }
            }else{
                $arrayD[$itemDadoLogistico['idNormaPaletizacao']] = 1;
                $arrayE[number_format($itemDadoLogistico['qtdEmbalagem'], 3, '.', '')] = $normasPaletizacao[$itemDadoLogistico['idNormaPaletizacao']]['idUnitizador'];
            }
        }
        foreach ($dadosLogisticos as $id => $itemDadoLogistico) {
            extract($itemDadoLogistico);
            if (!isset($acao))
                continue;
            // id
            $itemDadoLogistico['id'] = $id;
            // pega infos de normas de produtos
            if (!empty($itemDadoLogistico['idNormaPaletizacao'])) {
                $itemDadoLogistico['idNormaPaletizacao'] = $normasPaletizacao[$itemDadoLogistico['idNormaPaletizacao']]['id'];
            }

            switch ($acao) {
                case 'incluir':
                    if($dadoLogisticoRepo->verificaDadoLogistico($itemDadoLogistico)) {
                        $dadoLogisticoRepo->save($itemDadoLogistico);
                    }
                    break;
                case 'alterar':
                    $dadoLogisticoRepo->save($itemDadoLogistico);
                    break;
                case 'excluir':
                    if (!strpos($id, "-new"))
                        $dadoLogisticoRepo->remove($id);
                    break;
            }
        }
        return true;
    }

    /**
     *
     * @param string $id
     * @param string $grade
     * @return array
     */
    public function buscarDadoLogistico($id, $grade = false)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('p, tc.id idTipoComercializacao, tc.descricao tipoComercializacao')
                ->addSelect("
                        (
                            SELECT COUNT(pe)
                            FROM wms:Produto\Embalagem pe
                            WHERE pe.codProduto = p.id AND pe.grade = p.grade
                        )
                        AS qtdEmbalagem
                    ")
                ->addSelect("
                        (
                            SELECT COUNT(pv)
                            FROM wms:Produto\Volume pv
                            WHERE pv.codProduto = p.id AND pv.grade = p.grade
                        )
                        AS qtdVolume
                    ")
                ->from('wms:Produto', 'p')
                ->innerJoin('p.tipoComercializacao', 'tc')
                ->where('p.id = ?1')
                ->setParameter(1, $id);

        if ($grade)
            $dql->andWhere("p.grade = '" . $grade . "'");

        return $dql->getQuery()->getResult(\PDO::FETCH_ASSOC);
    }

    /**
     * Migra dados logisticos de uma grade para outra em um mesmo produto
     *
     * @param string $id Codigo do Produto
     * @param string $gradeOrigem Grade de origem dos dados
     * @param string $gradeDestino
     * @param Usuario $usuario
     */
    public function migrarDadoLogistico($id, $gradeOrigem, $gradeDestino, $usuario, $andamentoRepository) {
        $em = $this->getEntityManager();

        /** @var Produto $produtoOrigemEntity */
        $produtoOrigemEntity = $this->findOneBy(array('id' => $id, 'grade' => $gradeOrigem));
        /** @var Produto $produtoDestinoEntity */
        $produtoDestinoEntity = $this->findOneBy(array('id' => $id, 'grade' => $gradeDestino));

        $tipoComercializacao = $produtoOrigemEntity->getTipoComercializacao();
        $numVolumes = $produtoOrigemEntity->getNumVolumes();
        $linhaSeparacao = $produtoOrigemEntity->getLinhaSeparacao();

        $produtoDestinoEntity->setTipoComercializacao($tipoComercializacao)
                ->setCodigoBarrasBase('')
                ->setNumVolumes($numVolumes)
                ->setLinhaSeparacao($linhaSeparacao);

        $em->persist($produtoDestinoEntity);
        $em->flush();

        if ($tipoComercializacao->getId() == Produto::TIPO_COMPOSTO) {
            $this->migrarVolume($id, $gradeOrigem, $gradeDestino, $usuario, $andamentoRepository);
        } elseif($tipoComercializacao->getId() == Produto::TIPO_UNITARIO) {
            $this->migrarEmbalagem($id, $gradeOrigem, $gradeDestino, $usuario, $andamentoRepository);
        }
    }

    /**
     * Migro os volumes
     *
     * @param string $id Codigo do produto
     * @param string $gradeOrigem Grade de Origem
     * @param string $gradeDestino Grade de destinho
     * @param Usuario $usuario
     * @param Produto\AndamentoRepository $andamentoRepository
     */
    public function migrarVolume($id, $gradeOrigem, $gradeDestino, $usuario, $andamentoRepository) {
        $em = $this->getEntityManager();

        $volumeRepo = $em->getRepository('wms:Produto\Volume');
        $normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

        $volumesOrigem = $volumeRepo->findBy(array('codProduto' => $id, 'grade' => $gradeOrigem));

        $idsNormaPaletizacao = array();

        /** @var Produto $produtoDestinoEntity */
        $produtoDestinoEntity = $this->findOneBy(array('id' => $id, 'grade' => $gradeDestino));
        /** @var Produto\Volume[] $volumesDestino */
        $volumesDestino = $volumeRepo->findBy(array('codProduto' => $id, 'grade' => $gradeDestino));

        // limpo os volumes existentes
        /** @var Produto\Volume $volumeEntity */
        foreach ($volumesDestino as $volumeEntity) {
            $dth = new \DateTime();
            $volumeEntity->setDataInativacao($dth);
            $volumeEntity->setUsuarioInativacao($usuario);
            $this->_em->persist($volumeEntity);
            $observacao = "Volume " . $volumeEntity->getDescricao() . " código de barras: ". $volumeEntity->getCodigoBarras() . " inativado por migração de dados logísticos da grade $gradeOrigem.";
            $andamentoRepository->save($id, $gradeDestino,true, $observacao, false);
        }

        // clono os de origem
        foreach ($volumesOrigem as $key => $volumeEntity) {
            // novo volume
            $novoVolumeEntity = clone $volumeEntity;
            $idsNormaPaletizacao[$volumeEntity->getNormaPaletizacao()->getId()] = null;
            // alterando dados do volume
            $novoVolumeEntity->setGrade($produtoDestinoEntity->getGrade())
                    ->setProduto($produtoDestinoEntity)
                    ->setCodigoBarras('')
                    ->setEndereco(null);

            $produtoDestinoEntity->addVolume($novoVolumeEntity);
        }

        $em->persist($produtoDestinoEntity);
        $em->flush();
        $em->clear();

        foreach ($idsNormaPaletizacao as $idNormaPaletizacao => $valor) {
            $normaPaletizacaoEntity = $normaPaletizacaoRepo->find($idNormaPaletizacao);
            $novaNormaPaletizacaoEntity = clone $normaPaletizacaoEntity;

            $em->persist($novaNormaPaletizacaoEntity);
            $em->flush();

            $query = $em->createQuery('
                UPDATE wms:Produto\Volume pv 
                SET pv.normaPaletizacao =  ' . $novaNormaPaletizacaoEntity->getId() . ' 
                    WHERE pv.codProduto = :produto
                        AND pv.grade = :grade 
                        AND pv.normaPaletizacao = :normaPaletizacao
                    ')
                    ->setParameters(array(
                'produto' => $id,
                'grade' => $gradeDestino,
                'normaPaletizacao' => $idNormaPaletizacao,
            ));

            $query->execute();
        }

        $em->flush();
    }

    /**
     * Migro as embalagens
     *
     * @param string $id Codigo do produto
     * @param string $gradeOrigem Grade de Origem
     * @param string $gradeDestino Grade de destinho
     */
    public function migrarEmbalagem($id, $gradeOrigem, $gradeDestino, $usuario, $andamentoRepository) {
        $em = $this->getEntityManager();

        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
        $dadoLogisticoRepo = $em->getRepository('wms:Produto\DadoLogistico');
        $normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

        $embalagemsOrigem = $embalagemRepo->findBy(array('codProduto' => $id, 'grade' => $gradeOrigem));

        /** @var \Wms\Domain\Entity\Produto $produtoDestinoEntity */
        $produtoDestinoEntity = $this->findOneBy(array('id' => $id, 'grade' => $gradeDestino));
        $embalagemsDestino = $embalagemRepo->findBy(array('codProduto' => $id, 'grade' => $gradeDestino));

        // limpo os embalagems existentes
        /** @var Embalagem $embalagemEntity */
        foreach ($embalagemsDestino as $embalagemEntity) {
            $dth = new \DateTime();
            $embalagemEntity->setDataInativacao($dth);
            $embalagemEntity->setUsuarioInativacao($usuario);
            /** @var Produto\DadoLogistico[] $dadosLogisticos */
            $dadosLogisticos = $embalagemEntity->getDadosLogisticos();

            foreach($dadosLogisticos as $dadosLogistico){
                $this->_em->remove($dadosLogistico->getNormaPaletizacao());
                $this->_em->remove($dadosLogistico);
            }

            $this->_em->persist($embalagemEntity);
            $observacao = "Embalagem " . $embalagemEntity->getDescricao() . " código de barras: ". $embalagemEntity->getCodigoBarras() . " inativada por migração de dados logísticos da grade $gradeOrigem.";
            $andamentoRepository->save($id, $gradeDestino,true, $observacao, false);
        }

        // clono os de origem
        foreach ($embalagemsOrigem as $embalagemEntity) {
            // novo embalagem
            $novoEmbalagemEntity = clone $embalagemEntity;

            // alterando dados do embalagem
            $novoEmbalagemEntity->generateId($em)
                ->setGrade($produtoDestinoEntity->getGrade())
                ->setProduto($produtoDestinoEntity)
                ->setCodigoBarras('')
                ->setEndereco(null);

            $em->persist($novoEmbalagemEntity);

            foreach ($embalagemEntity->getDadosLogisticos() as $dadoLogisticoEntity) {
                $novoDadoLogisticoEntity = clone $dadoLogisticoEntity;

                $novoDadoLogisticoEntity->setEmbalagem($novoEmbalagemEntity);
                $em->persist($novoDadoLogisticoEntity);
            }
        }

        $em->persist($produtoDestinoEntity);
        $em->flush();
        $em->clear();

        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('dl.id idDadoLogistico, np.id idNormaPaletizacao')
                ->from('wms:Produto\Embalagem', 'pe')
                ->innerJoin('pe.dadosLogisticos', 'dl')
                ->innerJoin('dl.normaPaletizacao', 'np')
                ->where('pe.codProduto = :produto AND pe.grade = :grade')
                ->setParameters(array(
            'produto' => $id,
            'grade' => $gradeDestino,
        ));

        $resultSet = $dql->getQuery()->getResult(\PDO::FETCH_ASSOC);

        // clono os de origem
        foreach ($resultSet as $row) {

            $normaPaletizacaoEntity = $normaPaletizacaoRepo->find($row['idNormaPaletizacao']);
            $novaNormaPaletizacaoEntity = clone $normaPaletizacaoEntity;
            $em->persist($novaNormaPaletizacaoEntity);

            $dadoLogisticoEntity = $dadoLogisticoRepo->find($row['idDadoLogistico']);

            $dadoLogisticoEntity->setNormaPaletizacao($novaNormaPaletizacaoEntity);
            $em->persist($dadoLogisticoEntity);
        }
        $em->flush();
    }

    /**
     * Busca todos os dados de produto, produto volume, produto embalagem, dados logisticos e norma paletizacao
     *
     * @param array $params
     * @return array
     */
    public function buscarDadosProduto(array $params) {
        extract($params);

        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('p, c, f, ls, pe, pdl, pv ')
                ->from('wms:Produto', 'p')
                ->innerJoin('p.classe', 'c')
                ->innerJoin('p.fabricante', 'f')
                ->leftJoin('p.linhaSeparacao', 'ls')
                ->leftJoin('p.embalagens', 'pe')
                ->leftJoin('pe.dadosLogisticos', 'pdl')
                ->leftJoin('p.volumes', 'pv')
                ->where('p.id = :id AND p.grade = :grade')
                ->setParameters(array(
                    'id' => $id,
                    'grade' => $grade,
                ))
                ->orderBy('p.id', 'DESC');

        return $dql->getQuery()->getResult();
    }

    /**
     * Verifica se existe produtos com impressão automática do código de barras
     * @param int $idRecebimento
     */
    public function verificarProdutosImprimirCodigoBarras($idRecebimento) {
        $sql = "SELECT PRODUTO_IMPRIMIR_CODIGO_BARRAS($idRecebimento) AS IND_IMPRIMIR_CB FROM DUAL";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetch();
        return $result['IND_IMPRIMIR_CB'];
    }

    /**
     * Busca a quantidade de produtos com ou sem dados logisticos.
     */
    public function buscarQtdProdutosDadosLogisticos() {

        $sql = "SELECT COUNT(*) as QTD, POSSUI
               FROM (SELECT DISTINCT
                       CASE WHEN NVL(PE.COD_PRODUTO_EMBALAGEM, PV.COD_PRODUTO_VOLUME) IS NULL THEN 'NAO' ELSE 'SIM' END as POSSUI,
                            P.COD_PRODUTO,
                            P.DSC_GRADE
                       FROM PRODUTO P
                       LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                       LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE)
                      GROUP BY POSSUI
                      ORDER BY POSSUI";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $sim = 0;
        $nao = 0;
        foreach ($result as $row) {
            if ($row['POSSUI'] == 'SIM') {
                $sim = $row['QTD'];
            } else {
                $nao = $row['QTD'];
            }
        }

        return array('SIM' => $sim,
            'NAO' => $nao);
    }

    private function enviaDadosLogisticosEmbalagem(Produto $produtoEntity) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('pe.descricao, pe.altura, pe.cubagem, pe.largura, pe.peso, pe.profundidade, pe.quantidade, pe.codigoBarras ')
                ->from('wms:Produto\Embalagem', 'pe')
                ->where('pe.codProduto = ?1')
                ->andWhere('pe.grade = ?2')
                ->andWhere('pe.isPadrao like ?3')
                ->setParameters(array(
            1 => $produtoEntity->getId(),
            2 => $produtoEntity->getGrade(),
            3 => 'N'
        ));

        $dadosLogisticosEmbalagens = $dql->getQuery()->getResult();

        if (empty($dadosLogisticosEmbalagens)) {
            $dql = $this->getEntityManager()->createQueryBuilder()
                    ->select('pe.descricao, pe.altura, pe.cubagem, pe.largura, pe.peso, pe.profundidade, pe.quantidade, pe.codigoBarras ')
                    ->from('wms:Produto\Embalagem', 'pe')
                    ->where('pe.codProduto = ?1')
                    ->andWhere('pe.grade = ?2')
                    ->andWhere('pe.isPadrao like ?3')
                    ->setParameters(array(
                1 => $produtoEntity->getId(),
                2 => $produtoEntity->getGrade(),
                3 => 'S'
            ));

            $dadosLogisticosEmbalagens = $dql->getQuery()->getResult();
        }

        $dadosLogisticos = array();

        $client = $this->getSoapClient();

        $i = 0;
        foreach ($dadosLogisticosEmbalagens as $embalagem) {

            $dadosLogisticos[$i] = array(
                'altura' => $embalagem['altura'],
                'largura' => $embalagem['largura'],
                'profundidade' => $embalagem['profundidade'],
                'cubagem' => $embalagem['cubagem'],
                'peso' => $embalagem['peso'],
                'descricao' => $embalagem['descricao'],
                'quantidade' => $embalagem['quantidade'],
                'codigoBarras' => $embalagem['codigoBarras']
            );

            $i++;
        }

        return $client->salvar((string) $produtoEntity->getId(), $produtoEntity->getGrade(), $dadosLogisticos);
    }

    private function enviaDadosLogisticosVolumes($produtoEntity, array $values = array()) {

        extract($values);
        // volumes
        if (!isset($volumes))
            return false;

        $client = $this->getSoapClient();

        $dadosLogisticosVolume = array();

        $i = 0;
        foreach ($volumes as $id => $itemVolume) {
            extract($itemVolume);

            $dadosLogisticosVolume[$i] = array(
                'largura' => \Core\Util\Converter::brToEn($largura, 3),
                'altura' => \Core\Util\Converter::brToEn($altura, 3),
                'profundidade' => \Core\Util\Converter::brToEn($profundidade, 3),
                'cubagem' => \Core\Util\Converter::brToEn($cubagem, 4),
                'peso' => \Core\Util\Converter::brToEn($peso, 3),
                'descricao' => $descricao,
                'quantidade' => 1,
                'codigoBarras' => $codigoBarras
            );
            $i++;
        }
        return $client->salvar((string) $produtoEntity->getId(), $produtoEntity->getGrade(), $dadosLogisticosVolume);
    }

    private function getSoapClient() {
        $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/webservices.ini', APPLICATION_ENV);

        $dadosLogisticosUrl = 'integrarDadosLogisticos';

        return new \Zend_Soap_Client($conf->soap->$dadosLogisticosUrl->url, array('soapVersion' => SOAP_1_2, 'uri' => $conf->soap->$dadosLogisticosUrl->url)); //,
    }

    public function buscarProdutosImprimirCodigoBarras($codProduto, $grade, $idEmbalagens = null) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("
                      p.id as idProduto,
                      p.grade,
                      p.descricao as dscProduto,
                      p.validade,
                      ls.descricao as dscLinhaSeparacao,
                      fb.nome as fabricante,
                      tc.descricao as dscTipoComercializacao,
                      pe.id as idEmbalagem,
                      pe.descricao as dscEmbalagem,
                      pe.quantidade,
                      pv.id as idVolume,
                      pv.codigoSequencial as codSequencialVolume,
                      pv.descricao as dscVolume,
                      NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras,
                      NVL(de.descricao, 'N/D') picking")
                ->from('wms:Produto', 'p')
                ->innerJoin('p.tipoComercializacao', 'tc')
                ->leftJoin('p.linhaSeparacao', 'ls')
                ->leftJoin('p.fabricante', 'fb');

        if (isset($codProduto) && !empty($codProduto)) {
            $dql->leftJoin('p.embalagens', 'pe');
        } else {
            $dql->leftJoin('p.embalagens', 'pe', 'WITH', "pe.isPadrao = 'S'");
        }

            $dql->leftJoin('p.volumes', 'pv')
                ->leftJoin(Endereco::class, 'de', 'WITH', '(de = pv.endereco or de = pe.endereco)')
                ->where('p.id = :codProduto')
                ->andWhere("p.grade = :grade")
                ->setParameter('codProduto', $codProduto)
                ->setParameter('grade', $grade);
        if (!empty($idEmbalagens)) {
            $dql->andWhere("pe.id in ( $idEmbalagens )");
        }
            $dql->andWhere('((pe.codigoBarras IS NOT NULL and pe.dataInativacao IS NULL) OR (pv.codigoBarras IS NOT NULL and pv.dataInativacao IS NULL))')
                ->orderBy('pe.quantidade', 'desc');

        return $dql->getQuery()->getResult();
    }

    public function buscaGradesProduto($codProduto) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('p.grade')
                ->from('wms:Produto', 'p')
                ->where('p.id = :codProduto')
                ->setParameter('codProduto', ProdutoUtil::formatar($codProduto));

        $produtos = $queryBuilder->getQuery()->getArrayResult();
        $grades = array();
        foreach ($produtos as $produto) {
            $grades[] = $produto['grade'];
        }
        return $grades;
    }

    public function getProdutoByCodBarrasOrCodProduto($codigo) {

        $codigoBarrasProduto = ColetorUtil::adequaCodigoBarras($codigo);

        $info = $this->getProdutoByCodBarras($codigoBarrasProduto);
        $produtoEn = null;
        if ($info) {
            $produtoEn = $this->findOneBy(array('id' => $info[0]['idProduto'], 'grade' => $info[0]['grade']));
        } else {
            $produtoEn = $this->findOneBy(array('id' => $codigo, 'grade' => 'UNICA'));
        }

        if (!isset($produtoEn)) {
            throw new \Exception("Produto não encontrado pelo código $codigo");
        }

        return $produtoEn;
    }

    public function getEnderecoPicking($produtoEntity, $tipoRetorno = "DSC") {
        $enderecoPicking = null;
        if ($produtoEntity == null) {
            return $enderecoPicking;
        }

        if (count($produtoEntity->getEmbalagens()) > 0) {
            $embalagemEn = $produtoEntity->getEmbalagens();
        } else {
            $embalagemEn = $produtoEntity->getVolumes();
        }

        if ($embalagemEn[0] == null) {
            return array();
        }

        $enderecoPicking = array();
        /**
         * @var  $key
         * @var  Embalagem $embalagem */
        foreach ($embalagemEn as $key => $embalagem) {
            $dataInativacao = $embalagem->getDataInativacao();
            if (!is_null($dataInativacao)) {
                continue;
            } elseif ($embalagem->getEndereco() != null) {
                if ($tipoRetorno == "DSC") {
                    $enderecoPicking[$key] = $embalagem->getEndereco()->getDescricao();
                } else {
                    $enderecoPicking[$key] = $embalagem->getEndereco()->getId();
                }
            } else {
                $enderecoPicking = array();
                break;
            }
        }
        return $enderecoPicking;
    }

    public function getDadosLogisticos($codProduto, $grade) {
        $SQL = "
            SELECT PE.COD_BARRAS,
                   PE.DSC_EMBALAGEM,
                   PE.QTD_EMBALAGEM,
                   NVL(PE.NUM_ALTURA,0) as NUM_ALTURA,
                   NVL(PE.NUM_LARGURA,0) as NUM_LARGURA,
                   NVL(PE.NUM_PROFUNDIDADE,0) as NUM_PROFUNDIDADE,
                   NVL(PE.NUM_CUBAGEM,0) as NUM_CUBAGEM,
                   NVL(PE.NUM_PESO,0) as NUM_PESO
              FROM PRODUTO_EMBALAGEM PE
             WHERE PE.COD_PRODUTO = '$codProduto'
               AND PE.DSC_GRADE = '$grade'";
        $embalagens = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $SQL = "
        SELECT PV.COD_BARRAS,
               PV.DSC_VOLUME,
               PV.NUM_ALTURA,
               PV.NUM_LARGURA,
               PV.NUM_PROFUNDIDADE,
               PV.NUM_CUBAGEM,
               PV.NUM_PESO
          FROM PRODUTO_VOLUME PV
         WHERE PV.COD_PRODUTO = '$codProduto'
           AND PV.DSC_GRADE = '$grade'";
        $volumes = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $SQL = "
        SELECT PP.NUM_CUBAGEM,
               PP.NUM_PESO
          FROM PRODUTO_PESO PP
         WHERE PP.COD_PRODUTO = '$codProduto'
           AND PP.DSC_GRADE = '$grade'";
        $dadosPeso = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $cubagem = 0;
        $peso = 0;
        if (count($dadosPeso) > 0) {
            $cubagem = $dadosPeso[0]['NUM_CUBAGEM'];
            $peso = $dadosPeso[0]['NUM_PESO'];
        }
        return array(
            'NUM_PESO' => $peso,
            'NUM_CUBAGEM' => $cubagem,
            'VOLUMES' => $volumes,
            'EMBALAGENS' => $embalagens
        );
    }

    public function getEmbalagensOrVolumesByProduto($codProduto, $grade = "UNICA") {
        $sql = "SELECT PV.COD_PRODUTO_VOLUME,
                   PE.COD_PRODUTO_EMBALAGEM
              FROM PRODUTO P
              LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
              LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE AND PE.IND_PADRAO = 'S'
            WHERE P.COD_PRODUTO = '$codProduto'
            AND P.DSC_GRADE = '$grade'
            AND NOT (PV.COD_PRODUTO_VOLUME IS NULL AND PE.COD_PRODUTO_EMBALAGEM IS NULL)
            ";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function getNormaPaletizacaoPadrao($codProduto, $grade, $norma = null) {

        $sql = $this->getEntityManager()->createQueryBuilder()
                ->select('e.descricao unidade, u.descricao unitizador, np.numLastro lastro, np.numCamadas camadas, np.numNorma qtdNorma, u.id idUnitizador, np.id idNorma, p.descricao dscProduto')
                ->from('wms:Produto\NormaPaletizacao', 'np')
                ->innerJoin('wms:Armazenagem\Unitizador', 'u', 'WITH', 'u.id = np.unitizador')
                ->innerJoin('wms:Produto\DadoLogistico', 'pdl', 'WITH', 'pdl.normaPaletizacao = np.id')
                ->innerJoin('wms:Produto\Embalagem', 'e', 'WITH', 'e.id = pdl.embalagem')
                ->innerJoin('wms:Produto', 'p', 'WITH', 'p.id = e.codProduto AND p.grade = e.grade')
                ->where("e.codProduto = '$codProduto' AND e.grade = '$grade'");

        if (isset($norma) && !is_null($norma)) {
            $sql->andWhere("np.id = $norma");
        }
        $result = $sql->getQuery()->getResult();
        if (count($result) > 0)
            return $result;

        $produtoEntity = $this->findOneBy(array('id' => $codProduto, 'grade' => $grade));
        if (empty($produtoEntity))
            throw new \Exception("Nenhum produto ou norma foi encontrado para $codProduto de grade $grade");
        $volumes = $produtoEntity->getVolumes();

        $idNorma = NULL;
        $idUnitizador = 0;
        $unidadePadrao = "";
        $unitizador = "";
        $qtdNorma = 0;
        $lastro = 0;
        $camadas = 0;
        $dscProduto = $produtoEntity->getDescricao();

        foreach ($volumes as $volume) {
            $result = array();
            $norma = $volume->getNormaPaletizacao()->getId();
            if ($norma != NULL) {
                $unidadePadrao = $volume->getDescricao();
                $qtdNorma = $volume->getNormaPaletizacao()->getNumNorma();
                $lastro = $volume->getNormaPaletizacao()->getNumLastro();
                $camadas = $volume->getNormaPaletizacao()->getNumCamadas();
                $unitizador = $volume->getNormaPaletizacao()->getUnitizador()->getDescricao();
                $idUnitizador = $volume->getNormaPaletizacao()->getUnitizador()->getId();
                $idNorma = $norma;
                break;
            }
        }

        $result[0]['idNorma'] = $idNorma;
        $result[0]['unidade'] = $unidadePadrao;
        $result[0]['idUnitizador'] = $idUnitizador;
        $result[0]['unitizador'] = $unitizador;
        $result[0]['qtdNorma'] = $qtdNorma;
        $result[0]['lastro'] = $lastro;
        $result[0]['camadas'] = $camadas;
        $result[0]['dscProduto'] = $dscProduto;

        return $result;
    }

    public function getPesoProduto($params) {
        $sql = "SELECT
                 COD_PRODUTO,
                 DSC_GRADE,
                 NUM_PESO,
                 NUM_CUBAGEM
                FROM
                 PRODUTO_PESO
                WHERE
                  COD_PRODUTO = '$params[COD_PRODUTO]'
                  AND DSC_GRADE = '$params[DSC_GRADE]'
           ";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function getDadosProdutos($params) {
        $sql = "SELECT
                 P.COD_PRODUTO \"COD.PRODUTO\",
                 P.DSC_GRADE \"GRADE\",
                 P.DSC_PRODUTO \"PRODUTO\",
                 PC.NOM_PRODUTO_CLASSE \"CLASSE\",
                 L.DSC_LINHA_SEPARACAO \"LINHA SEPARACAO\",
                 F.NOM_FABRICANTE \"FABRICANTE\",
                 S.DSC_SIGLA as \"TIPO COMERCIALIZACAO\",
                 P.NUM_VOLUMES as \"QTD.VOLUMES\",
                 NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) \"EMBALAGEM/VOLUME\",
                 PE.QTD_EMBALAGEM as \"FATOR EMBALAGEM\",
                 PV.COD_SEQUENCIAL_VOLUME as \"NUM VOLUME\",
                 P.COD_BARRAS_BASE as \"COD.BARRAS BASE\",
                 NVL(PE.COD_BARRAS, PV.COD_BARRAS) as \"COD.BARRAS\",
                 NVL(DEE.DSC_DEPOSITO_ENDERECO, DEV.DSC_DEPOSITO_ENDERECO) as \"END.PICKING\",
                 NVL(PV.NUM_PESO, PDL.NUM_PESO) \"PESO EMBALAGEM/VOLUME\",
                 NVL(PV.NUM_ALTURA, PDL.NUM_ALTURA) \"ALTURA\",
                 NVL(PV.NUM_LARGURA, PDL.NUM_LARGURA) \"LARGURA\",
                 NVL(PV.NUM_PROFUNDIDADE, PDL.NUM_PROFUNDIDADE) \"PROFUNDIDADE\",
                 NVL(PV.NUM_CUBAGEM, PDL.NUM_CUBAGEM) \"CUBAGEM\",
                 NVL(U1.DSC_UNITIZADOR, U2.DSC_UNITIZADOR) UNITIZADOR,
                 NVL(NP1.NUM_LASTRO, NP2.NUM_LASTRO) \"LASTRO\",
                 NVL(NP1.NUM_CAMADAS, NP2.NUM_CAMADAS) \"CAMADAS\",
                 NVL(NP1.NUM_NORMA, NP2.NUM_NORMA) as \"NORMA DE PALETIZACAO\",
                 NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING) as \"CAPACIDADE DE PICKING\",
                 NVL(PE.PONTO_REPOSICAO, PV.PONTO_REPOSICAO) as \"PONTO REPOSICAO\",
                 PE.IND_EMBALADO as \"EMBALADO\",
                 CASE WHEN (PV.COD_NORMA_PALETIZACAO IS NULL AND PDL.COD_NORMA_PALETIZACAO IS NULL) THEN 'SEM NORMA DE PALETIZACAO CADASTRADA'
                      WHEN (PE.COD_BARRAS IS NULL AND PV.COD_BARRAS IS NULL) THEN 'SEM CODIGO DE BARRAS'
                      WHEN (NP1.NUM_NORMA = 0 OR NP2.NUM_NORMA = 0) THEN 'SEM LASTRO OU CAMADAS DEFINIDOS'
                      WHEN (DEE.COD_DEPOSITO_ENDERECO IS NULL AND DEV.COD_DEPOSITO_ENDERECO IS NULL) THEN 'SEM ENDERECO DE PICKING'
                      ELSE 'CADASTRO CORRETO'
                 END AS \"PROBLEMA CADASTRAL\",
                 PE.COD_PRODUTO_EMBALAGEM,
                 PE.IND_PADRAO,
                 NVL(PE.IND_IMPRIMIR_CB,PV.IND_IMPRIMIR_CB) IND_IMPRIMIR_CB,                 
                 CASE WHEN PE.DTH_INATIVACAO IS NULL THEN 'SIM'
                      ELSE 'NÃO'
                 END AS ATIVO ,
                 GP.GRUPO               
                 FROM PRODUTO P
           LEFT JOIN LINHA_SEPARACAO L ON P.COD_LINHA_SEPARACAO = L.COD_LINHA_SEPARACAO
           INNER JOIN FABRICANTE F ON P.COD_FABRICANTE = F.COD_FABRICANTE
           INNER JOIN PRODUTO_CLASSE PC ON P.COD_PRODUTO_CLASSE = PC.COD_PRODUTO_CLASSE
            LEFT JOIN SIGLA S ON (P.COD_TIPO_COMERCIALIZACAO = S.COD_REFERENCIA_SIGLA AND S.COD_TIPO_SIGLA = 52)
            LEFT JOIN PRODUTO_VOLUME PV ON (P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE)
            LEFT JOIN PRODUTO_EMBALAGEM PE ON (P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
            LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM
            LEFT JOIN NORMA_PALETIZACAO NP1 ON PV.COD_NORMA_PALETIZACAO = NP1.COD_NORMA_PALETIZACAO
            LEFT JOIN NORMA_PALETIZACAO NP2 ON PDL.COD_NORMA_PALETIZACAO = NP2.COD_NORMA_PALETIZACAO
            LEFT JOIN (SELECT PV1.COD_PRODUTO, PV1.DSC_GRADE, PV1.COD_NORMA_PALETIZACAO, LISTAGG(PV1.DSC_VOLUME, ': ') WITHIN GROUP (ORDER BY PV1.DSC_VOLUME) GRUPO 
                        FROM PRODUTO_VOLUME PV1
                        GROUP BY PV1.COD_PRODUTO, PV1.DSC_GRADE, PV1.COD_NORMA_PALETIZACAO ) GP ON PV.COD_PRODUTO = GP.COD_PRODUTO AND PV.DSC_GRADE = GP.DSC_GRADE AND PV.COD_NORMA_PALETIZACAO = GP.COD_NORMA_PALETIZACAO
            LEFT JOIN UNITIZADOR U1 ON NP1.COD_UNITIZADOR = U1.COD_UNITIZADOR
            LEFT JOIN UNITIZADOR U2 ON NP2.COD_UNITIZADOR = U2.COD_UNITIZADOR
            LEFT JOIN DEPOSITO_ENDERECO DEE ON DEE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
            LEFT JOIN DEPOSITO_ENDERECO DEV ON DEV.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO";
        $queryWhere = "";
        if (!empty($params['grandeza'])) {
            $queryWhere = " WHERE ";
            $grandeza = $params['grandeza'];
            $grandeza = implode(',', $grandeza);
            $queryWhere = $queryWhere . " P.COD_LINHA_SEPARACAO IN ($grandeza) ";
        }
        $sql = $sql . $queryWhere . " ORDER BY P.COD_PRODUTO, P.DSC_GRADE, NVL(PV.COD_SEQUENCIAL_VOLUME,PE.QTD_EMBALAGEM)";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function relatorioListagemProdutos(array $params = array()) {
        extract($params);
        $linhaseparacao = $params['idLinhaSeparacao'];

        $sql = "SELECT DISTINCT P.COD_PRODUTO,
                       P.DSC_PRODUTO,
                       P.DSC_GRADE,
                       P.NUM_VOLUMES,
                       LS.DSC_LINHA_SEPARACAO,
                       NVL(PE.COD_BARRAS,PV.COD_BARRAS) as CODIGO_BARRAS,
                       NVL(DE1.DSC_DEPOSITO_ENDERECO,DE2.DSC_DEPOSITO_ENDERECO) as PICKING
                FROM PRODUTO P
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                LEFT JOIN DEPOSITO_ENDERECO DE1 ON PE.COD_DEPOSITO_ENDERECO = DE1.COD_DEPOSITO_ENDERECO
                LEFT JOIN DEPOSITO_ENDERECO DE2 ON PV.COD_DEPOSITO_ENDERECO = DE2.COD_DEPOSITO_ENDERECO
                LEFT JOIN LINHA_SEPARACAO   LS ON P.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                WHERE NOT(PV.COD_DEPOSITO_ENDERECO IS NULL AND PE.COD_DEPOSITO_ENDERECO IS NULL)
                      AND P.COD_LINHA_SEPARACAO = $linhaseparacao
                ORDER BY PICKING";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEmbalagensByCodBarras($codBarras) {
        $embalagenEn = null;
        $volumeEn = null;
        $produtoEn = null;
        $embalagenEn = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findOneBy(array('codigoBarras' => $codBarras, 'dataInativacao' => null));
        if ($embalagenEn == null) {
            $volumeEn = $this->getEntityManager()->getRepository("wms:Produto\Volume")->findOneBy(array('codigoBarras' => $codBarras, 'dataInativacao' => null));
            if ($volumeEn == null) {
                throw new \Exception("Produto não encontrado para o código de barras $codBarras.");
            } else {
                $produtoEn = $volumeEn->getProduto();
            }
        } else {
            $produtoEn = $embalagenEn->getProduto();
        }

        return array('embalagem' => $embalagenEn,
            'volume' => $volumeEn,
            'produto' => $produtoEn);
    }

    public function getProdutoByCodBarras($codigoBarras) {
        // busco produto
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('  p.id idProduto, p.descricao, p.grade,
                        pe.id idEmbalagem, pv.id idVolume, p.numVolumes, ls.descricao as linhaSeparacao,
                        NVL(pv.codigoBarras, pe.codigoBarras) codigoBarras,
                        NVL(unitizador_embalagem.id, unitizador_volume.id) idUnitizador,
                        NVL(np_embalagem.numLastro, np_volume.numLastro) numLastro,
                        NVL(unitizador_volume.descricao, unitizador_embalagem.descricao) unitizador,
                        NVL(np_embalagem.numCamadas, np_volume.numCamadas) numCamadas,
                        NVL(np_embalagem.numPeso, np_volume.numPeso) numPeso,
                        NVL(np_embalagem.numNorma, np_volume.numNorma) numNorma,
                        NVL(np_embalagem.id, np_volume.id) idNorma,
                        NVL(pe.descricao, \'\') descricaoEmbalagem,
                        NVL(pe.quantidade, \'0\') quantidadeEmbalagem,
                        NVL(pe.capacidadePicking, \'0\') capacidadePicking,
                        NVL(pv.descricao, \'\') descricaoVolume,
                        NVL(de1.descricao, de2.descricao) picking,
                        NVL(pv.codigoSequencial, \'\') sequenciaVolume,
                        NVL(p.diasVidaUtil, \'0\') diasVidaUtil'
                )
                ->from('wms:Produto', 'p')
                ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade AND pe.dataInativacao is null')
                ->leftJoin('p.linhaSeparacao', 'ls')
                ->leftJoin('pe.dadosLogisticos', 'dl')
                ->leftJoin('pe.endereco', 'de1')
                ->leftJoin('dl.normaPaletizacao', 'np_embalagem')
                ->leftJoin('np_embalagem.unitizador', 'unitizador_embalagem')
                ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade AND pv.dataInativacao is null')
                ->leftJoin('pv.endereco', 'de2')
                ->leftJoin('pv.normaPaletizacao', 'np_volume')
                ->leftJoin('np_volume.unitizador', 'unitizador_volume')
                ->where('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras OR p.id = :codigoBarras)')
                ->setParameters(array('codigoBarras' => $codigoBarras));

        return $dql->getQuery()->getArrayResult();
    }

    public function getEmbalagemByCodBarras($codigoBarras) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("p.id idProduto, p.descricao, p.grade,
                        pe.id idEmbalagem, pv.id idVolume, p.numVolumes,
                        NVL(pv.codigoBarras, pe.codigoBarras) codigoBarras,
                        NVL(pe.descricao, pv.descricao) descricaoEmbalagem,
                        NVL(pe.quantidade, 1) quantidadeEmbalagem,
                        p.indControlaLote, 
                        p.indFracionavel, 
                        p.validade controlaValidade,
                        NVL(pv.normaPaletizacao, 0) norma,
                        NVL(de.descricao, 'N/D')  picking"
                )
                ->from('wms:Produto', 'p')
                ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade AND pe.dataInativacao is null')
                ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade AND pv.dataInativacao is null')
                ->leftJoin('wms:Deposito\Endereco', 'de', 'WITH', 'de = pv.endereco OR de = pe.endereco')
                ->where('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
                ->setParameters(array('codigoBarras' => $codigoBarras));

        return $dql->getQuery()->getArrayResult();
    }

    public function relatorioProdutosSemPicking(array $params = array()) {
        extract($params);
        $cond = "";
        if (!empty($params['rua'])) {
            $rua = $params['rua'];
            $cond = " AND  DE.NUM_RUA = " . $rua . " ";
        }

        $sql = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO as \"descricao\",
                       DE.COD_DEPOSITO_ENDERECO as \"codigo\",
                       DE.COD_AREA_ARMAZENAGEM as \"areaArmazenagem\",
                       DE.IND_ATIVO  as \"ativo\",
                       CASE 
                       WHEN DE.BLOQUEADA_ENTRADA = 1 and DE.BLOQUEADA_SAIDA = 1 THEN 'Entrada/Saída' 
                       WHEN DE.BLOQUEADA_ENTRADA = 1 and DE.BLOQUEADA_SAIDA = 0 THEN 'Entrada' 
                       WHEN DE.BLOQUEADA_ENTRADA = 0 and DE.BLOQUEADA_SAIDA = 1 THEN 'Saída' 
                       ELSE 'Nada' END as \"bloqueada\"
                FROM DEPOSITO_ENDERECO DE
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_DEPOSITO_ENDERECO=DE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_DEPOSITO_ENDERECO=DE.COD_DEPOSITO_ENDERECO
                WHERE (PV.COD_DEPOSITO_ENDERECO IS NULL AND PE.COD_DEPOSITO_ENDERECO IS NULL)
                    AND DE.COD_CARACTERISTICA_ENDERECO <> 37 AND DE.BLOQUEADA_SAIDADE = 0 AND DE.BLOQUEADA_ENTRADA = 0 $cond
                ORDER BY \"descricao\"";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaSeEProdutoComposto($idProduto) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('p.numVolumes')
                ->from('wms:Produto', 'p')
                ->where('p.id = :codProduto')
                ->setParameter('codProduto', $idProduto);

        return $dql->getQuery()->getResult();
    }

    public function getProdutoEmbalagem() {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('pe.descricao, IDENTITY(pe.produto) AS produto, pe.grade, pe.id')
                ->from('wms:Produto\Embalagem', 'pe')
                ->orderBy('pe.isPadrao', 'desc');

        return $dql->getQuery()->getResult();
    }

    public function getSequenciaEndAutomaticoCaracEndereco($codProduto, $grade, $inner = false) {
        if ($inner == true) {
            $join = " INNER ";
        } else {
            $join = " LEFT ";
        }

        $SQL = "  SELECT TP.COD_CARACTERISTICA_ENDERECO as ID, TP.DSC_CARACTERISTICA_ENDERECO as DESCRICAO, P.NUM_PRIORIDADE as VALUE
                    FROM CARACTERISTICA_ENDERECO TP
                    $join JOIN PRODUTO_END_CARACT_END P
                      ON P.COD_CARACTERISTICA_ENDERECO = TP.COD_CARACTERISTICA_ENDERECO
                     AND P.COD_PRODUTO = '$codProduto'
                     AND P.DSC_GRADE = '$grade'
                   ORDER BY TP.DSC_CARACTERISTICA_ENDERECO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getSequenciaEndAutomaticoTpEndereco($codProduto, $grade, $inner = false) {
        if ($inner == true) {
            $join = " INNER ";
        } else {
            $join = " LEFT ";
        }

        $SQL = "  SELECT TP.COD_TIPO_ENDERECO as ID, TP.DSC_TIPO_ENDERECO as DESCRICAO, P.NUM_PRIORIDADE as VALUE
                    FROM TIPO_ENDERECO TP
                    $join JOIN PRODUTO_END_TIPO_ENDERECO P
                      ON P.COD_TIPO_ENDERECO = TP.COD_TIPO_ENDERECO
                     AND P.COD_PRODUTO = '$codProduto'
                     AND P.DSC_GRADE = '$grade'
                   ORDER BY TP.DSC_TIPO_ENDERECO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getSequenciaEndAutomaticoAreaArmazenagem($codProduto, $grade, $inner = false) {
        if ($inner == true) {
            $join = " INNER ";
        } else {
            $join = " LEFT ";
        }

        $SQL = "  SELECT TP.COD_AREA_ARMAZENAGEM as ID, TP.DSC_AREA_ARMAZENAGEM as DESCRICAO, P.NUM_PRIORIDADE as VALUE
                    FROM AREA_ARMAZENAGEM TP
                   $join JOIN PRODUTO_END_AREA_ARMAZENAGEM P
                      ON P.COD_AREA_ARMAZENAGEM = TP.COD_AREA_ARMAZENAGEM
                     AND P.COD_PRODUTO = '$codProduto'
                     AND P.DSC_GRADE = '$grade'
                   ORDER BY TP.DSC_AREA_ARMAZENAGEM";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getSequenciaEndAutomaticoTpEstrutura($codProduto, $grade, $inner = false) {

        if ($inner == true) {
            $join = " INNER ";
        } else {
            $join = " LEFT ";
        }
        $SQL = "  SELECT TP.COD_TIPO_EST_ARMAZ as ID, TP.DSC_TIPO_EST_ARMAZ as DESCRICAO, P.NUM_PRIORIDADE as VALUE
                    FROM TIPO_EST_ARMAZ TP
                   $join JOIN PRODUTO_END_TIPO_EST_ARMAZ P
                      ON P.COD_TIPO_EST_ARMAZ = TP.COD_TIPO_EST_ARMAZ
                     AND P.COD_PRODUTO = '$codProduto'
                     AND P.DSC_GRADE = '$grade'
                   ORDER BY TP.DSC_TIPO_EST_ARMAZ";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param $produtoEn Produto
     * @param $modeloEnderecamentoEn Modelo
     * @return Endereco|null
     */
    public function getEnderecoReferencia($produtoEn, $modeloEnderecamentoEn) {
        $enderecoReferencia = null;

        //PRIMEIRO VERIFICO SE O PRODUTO TEM ENDEREÇO DE REFERENCIA
        $enderecoReferencia = $produtoEn->getEnderecoReferencia();


        if ($enderecoReferencia != null) if ($enderecoReferencia->isBloqueadaEntrada()) $enderecoReferencia = null;

        //SE NÂO TIVER ENDEREÇO DE REFERNECIA ENTÃO USO O PIKCING COMO ENDEREÇO DE REFERENCIA
        if ($enderecoReferencia == null) {
            $embalagens = $produtoEn->getEmbalagens();
            foreach ($embalagens as $embalagem) {
                if ($embalagem->getEndereco() != null) {
                    $enderecoReferencia = $embalagem->getEndereco();
                    break;
                }
            }
        }
        if ($enderecoReferencia == null) {
            $volumes = $produtoEn->getVolumes();
            foreach ($volumes as $volume) {
                if ($volume->getEndereco() != null) {
                    $enderecoReferencia = $volume->getEndereco();
                    break;
                }
            }
        }

        //SE O PRODUTO NÂO TIVER PICKING NEM ENDEREÇO DE REFERENCIA, ENTÂO VEJO O ENDEREÇO DO MODELO
        if ($enderecoReferencia == null) {
            $enderecoReferencia = $modeloEnderecamentoEn->getCodReferencia();
        }

        if (!empty($enderecoReferencia) && $enderecoReferencia->isBloqueadaEntrada()) $enderecoReferencia = null;

        return $enderecoReferencia;
    }

    public function getProdutoByParametroVencimento($params) {
        $where = " WHERE (E.DTH_VALIDADE <= TO_DATE('$params[dataReferencia]','DD/MM/YYYY') OR E.DTH_VALIDADE IS NULL)";
        if (isset($params['codProduto']) && !empty($params['codProduto'])) {
            $where .= " AND P.COD_PRODUTO = '$params[codProduto]' ";
        }
        if (isset($params['linhaSeparacao']) && !empty($params['linhaSeparacao'])) {
            $where .= "AND P.COD_LINHA_SEPARACAO = '$params[linhaSeparacao]' ";
        }
        if (isset($params['descricao']) && !empty($params['descricao'])) {
            $where .= "AND LOWER(P.DSC_PRODUTO) LIKE LOWER('%$params[descricao]%') ";
        }
        if (isset($params['idFabricante']) && !empty($params['idFabricante'])) {
            $where .= " AND P.COD_FABRICANTE = $params[idFabricante] ";
        }

        $picking = Endereco::PICKING;
        $pulmao = Endereco::PULMAO;
        if (isset($params['endereco']) && !empty($params['endereco'])) {
            if ($params['endereco'] == $picking) {
                $where .= "AND DE.COD_CARACTERISTICA_ENDERECO = $picking";
            } elseif ($params['endereco'] == $pulmao) {
                $where .= "AND DE.COD_CARACTERISTICA_ENDERECO = $pulmao";
            }
        }

        $query = "SELECT 
                      P.COD_PRODUTO AS cod_produto, 
                      P.DSC_GRADE AS grade, 
                      P.DSC_PRODUTO AS descricao, 
                      NVL(L.DSC_LINHA_SEPARACAO,'PADRAO') AS linha_separacao, 
                      NVL(F.NOM_FABRICANTE,'NÃO IDENTIFICADO') AS FABRICANTE, 
                      DE.DSC_DEPOSITO_ENDERECO AS endereco, 
                      TO_CHAR(E.DTH_VALIDADE,'DD/MM/YYYY') AS VALIDADE,
                      SUM(E.QTD) AS qtd,
                      PICKING.DSC_DEPOSITO_ENDERECO PICKING,
                      CASE WHEN TO_CHAR(E.DTH_VALIDADE - SYSDATE) < 0
                        THEN 'VENCIDO'
                      ELSE
                        TO_CHAR(TO_DATE(TO_CHAR(E.DTH_VALIDADE,'dd/mm/yyyy'),'dd/mm/yyyy') - TO_DATE(TO_CHAR(SYSDATE,'dd/mm/yyyy'),'dd/mm/yyyy'))
                      END AS DIASVENCER,
                      NVL(E.DSC_LOTE, 'N/D') LOTE
                  FROM ESTOQUE E 
                  INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  INNER JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE AND P.POSSUI_VALIDADE = 'S'
                  LEFT JOIN LINHA_SEPARACAO L ON L.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
                  LEFT JOIN PALETE PLT ON PLT.UMA = E.UMA AND PLT.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = E.COD_PRODUTO_EMBALAGEM
                  LEFT JOIN FABRICANTE F ON F.COD_FABRICANTE = P.COD_FABRICANTE
                  LEFT JOIN (
                      SELECT COD_RECEBIMENTO, MAX(COD_EMISSOR) AS COD_EMISSOR 
                      FROM NOTA_FISCAL 
                      GROUP BY COD_RECEBIMENTO) NF ON NF.COD_RECEBIMENTO = PLT.COD_RECEBIMENTO
                  LEFT JOIN PESSOA PES ON PES.COD_PESSOA = NF.COD_EMISSOR
                  LEFT JOIN DEPOSITO_ENDERECO PICKING ON PICKING.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                  $where
                  GROUP BY 
                      P.COD_PRODUTO, 
                      P.DSC_GRADE, 
                      P.DSC_PRODUTO,
                      NVL(E.DSC_LOTE, 'N/D'),
                      L.DSC_LINHA_SEPARACAO, 
                      F.NOM_FABRICANTE, 
                      DE.DSC_DEPOSITO_ENDERECO,
                      E.DTH_VALIDADE,
                      PICKING.DSC_DEPOSITO_ENDERECO
                  ORDER BY TO_DATE(VALIDADE, 'DD/MM/YYYY')";

        return $this->_em->getConnection()->query($query)->fetchAll();
    }

    public function getProdutos($codProduto)
    {
        $sql = "SELECT COD_PRODUTO, DSC_GRADE, DSC_PRODUTO
					FROM PRODUTO P
					WHERE P.COD_PRODUTO IN ($codProduto)";
        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param $produto
     * @param string $validade
     * @return boolean
     */
	public function checkShelfLifeProduto($produto, $validade)
    {
        if (is_a($produto,'Produto')) {
            $produtoEn = $this->findOneBy(array('id' => $produto->getId(), 'grade' => $produto->getGrade()));
        } else {
            $produtoEn = $this->findOneBy(array('id' => $produto->id, 'grade' => $produto->grade));
        }
        if (!empty($produtoEn)) {
            $dias = $produtoEn->getDiasVidaUtil();
            $periodoUtil = date('Y-m-d', strtotime("+ $dias days"));
            $vetValidade = explode('/', $validade);
            return ((strtotime("$vetValidade[2]-$vetValidade[1]-$vetValidade[0]") >= strtotime($periodoUtil))) ? true : false;
        }
        return false;
    }

    public function getProdutosEstoqueSemCapacidade()
    {
        $sql = "SELECT DISTINCT
                       E.COD_PRODUTO as CODIGO,
                       E.DSC_GRADE as GRADE,
                       P.DSC_PRODUTO as PRODUTO,
                       DE.DSC_DEPOSITO_ENDERECO as PICKING
                 FROM ESTOQUE E
                 LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = E.COD_PRODUTO AND PE.DSC_GRADE = E.DSC_GRADE
                 LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND PE.DSC_GRADE = E.DSC_GRADE
                 LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                 WHERE PE.CAPACIDADE_PICKING = 0";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Método destinado para listar todos os cortes por dia / produto
     *
     * @param array $params
     *
     * @return array
     */
    public function getCortePorDiaProduto($params) {
        $sql = "SELECT TO_CHAR(E.DTH_INICIO, 'DD/MM/YYYY') as DTH_INICIO, E.COD_EXPEDICAO, C.COD_CARGA_EXTERNO as COD_CARGA, PP.COD_PRODUTO, PP.DSC_GRADE, PROD.DSC_PRODUTO, PP.QUANTIDADE, PP.QTD_CORTADA, PP.QUANTIDADE - PP.QTD_CORTADA as QTD_ATENDIDA,
                CASE WHEN (PP.QUANTIDADE - PP.QTD_CORTADA) = 0 THEN 'CORTE TOTAL' ELSE 'CORTE PARCIAL' END AS TIPO_CORTE, MC.DSC_MOTIVO_CORTE
                FROM PEDIDO_PRODUTO PP
                LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO
                LEFT JOIN MOTIVO_CORTE MC ON MC.COD_MOTIVO_CORTE = PP.COD_MOTIVO_CORTE
                WHERE PP.QTD_CORTADA > 0";

        if (isset($params['idExpedicao']) && !empty($params['idExpedicao'])) {
            $sql .= " AND E.COD_EXPEDICAO = " . $params['idExpedicao'] . "";
        }

        if (isset($params['grade']) && !empty($params['grade'])) {
            $sql .= " AND PP.DSC_GRADE = '" . $params['grade'] . "'";
        }

        if (isset($params['descricao']) && !empty($params['descricao'])) {
            $sql .= " AND PROD.DSC_PRODUTO LIKE UPPER('%" . $params['descricao'] . "%')";
        }

        if (isset($params['dataInicial1']) && !empty($params['dataInicial1'])) {
            $sql .= " AND TO_DATE(E.DTH_INICIO) >= TO_DATE('" . $params['dataInicial1'] . " 00:00:00','DD/MM/YYYY HH24:MI:SS')";
        }

        if (isset($params['dataInicial2']) && !empty($params['dataInicial2'])) {
            $sql .= " AND TO_DATE(E.DTH_INICIO) <= TO_DATE('" . $params['dataInicial2'] . " 00:00:00','DD/MM/YYYY HH24:MI:SS')";
        }

        if (isset($params['dataFinal1']) && !empty($params['dataFinal1'])) {
            $sql .= " AND TO_DATE(E.DTH_FINALIZACAO) >= TO_DATE('" . $params['dataFinal1'] . " 00:00:00','DD/MM/YYYY HH24:MI:SS')";
        }

        if (isset($params['dataFinal2']) && !empty($params['dataFinal2'])) {
            $sql .= " AND TO_DATE(E.DTH_FINALIZACAO) <= TO_DATE('" . $params['dataFinal2'] . " 00:00:00','DD/MM/YYYY HH24:MI:SS')";
        }

        if (isset($params['status']) && (!empty($params['status']))) {
            $sql .= " AND E.COD_STATUS = " . $params['status'] . "";
        }

        $sql .= " ORDER BY E.DTH_INICIO DESC, PP.COD_PRODUTO";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getProdDadoLog(){
        ini_set('memory_limit', '1024M');
        $sql = "SELECT PE.COD_PRODUTO_EMBALAGEM, PE.COD_DEPOSITO_ENDERECO, PE.QTD_EMBALAGEM, PE.COD_PRODUTO, PE.DSC_GRADE, PD.NUM_ALTURA, PD.NUM_CUBAGEM, PD.NUM_LARGURA, PD.NUM_PESO, PD.NUM_PROFUNDIDADE
                FROM PRODUTO_EMBALAGEM PE LEFT JOIN PRODUTO_DADO_LOGISTICO PD ON PE.COD_PRODUTO_EMBALAGEM = PD.COD_PRODUTO_EMBALAGEM 
                ORDER BY PE.COD_PRODUTO, PE.DSC_GRADE DESC";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $preenchidos = array();
        $vazios = array();
        foreach($result as $key => $value){
            if($value['NUM_ALTURA'] != 0 && $value['NUM_ALTURA'] != null){
                $preenchidos[$value['COD_PRODUTO']] = $result[$key];
            }
            if($value['COD_DEPOSITO_ENDERECO'] != null){
                $preenchidos[$value['COD_PRODUTO']]['COD_DEPOSITO_ENDERECO'] = $value['COD_DEPOSITO_ENDERECO'];
            }
            $vazios[$value['COD_PRODUTO']][$value['DSC_GRADE']][] = $result[$key];
        }
        $em = $this->getEntityManager();
        $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
        foreach ($preenchidos as $key => $value){
            foreach ($vazios[$key][$value['DSC_GRADE']] as $key2 => $value2){
                $altura = ($value['NUM_ALTURA'] / $value['QTD_EMBALAGEM']) * $vazios[$key][$value['DSC_GRADE']][$key2]['QTD_EMBALAGEM'];
                $largura = ($value['NUM_LARGURA'] / $value['QTD_EMBALAGEM']) * $vazios[$key][$value['DSC_GRADE']][$key2]['QTD_EMBALAGEM'];
                $profundidade = ($value['NUM_PROFUNDIDADE'] / $value['QTD_EMBALAGEM']) * $vazios[$key][$value['DSC_GRADE']][$key2]['QTD_EMBALAGEM'];
                $peso = ($value['NUM_PESO'] / $value['QTD_EMBALAGEM']) * $vazios[$key][$value['DSC_GRADE']][$key2]['QTD_EMBALAGEM'];
                $cubagem = ( $altura *  $largura *  $profundidade);
                $codProdutoEmbalagem = $value2['COD_PRODUTO_EMBALAGEM'];
                if($largura > 0 && $peso > 0) {
                    $produtoEmbEntity = $embalagemRepo->find($codProdutoEmbalagem);
                    $produtoEmbEntity->setAltura(number_format($altura, 3, ',', ''));
                    $produtoEmbEntity->setLargura(number_format($largura, 3, ',', ''));
                    $produtoEmbEntity->setProfundidade(number_format($profundidade, 3, ',', ''));
                    $produtoEmbEntity->setCubagem(number_format($cubagem, 4, ',', ''));
                    $produtoEmbEntity->setPeso(number_format($peso, 3, ',', ''));
                    if($value['COD_DEPOSITO_ENDERECO'] != null){
                        $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
                        $produtoEmbEntity->setEndereco($enderecoRepo->find($value['COD_DEPOSITO_ENDERECO']));
                    }
                    $em->persist($produtoEmbEntity);
                }
            }
        }
        $em->flush();
    }

    public function checkCodBarrasRepetido($codigoBarras, $tipoComercializacao, $idElemento){
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id idProduto, p.grade, NVL(pe.descricao, pv.descricao) dsc_elemento, pe.id id_emb, pv.id id_vol')
            ->from('wms:Produto', 'p')
            ->leftJoin('p.embalagens', 'pe')
            ->leftJoin('p.volumes', 'pv')
            ->where('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
            ->setParameter('codigoBarras', $codigoBarras);

        if ($tipoComercializacao == Produto::TIPO_UNITARIO && !strpos($idElemento, "-new")) {
            $dql->andWhere("pe.id != :idElemento")
                ->setParameter('idElemento', $idElemento);
        } elseif ($tipoComercializacao == Produto::TIPO_COMPOSTO && !strpos($idElemento, "-new")) {
            $dql->andWhere("pv.id != :idElemento")
                ->setParameter('idElemento', $idElemento);
        }

        return $dql->getQuery()->getResult();
    }

    public function checkTemNormaAndDadoLogistico($idProduto, $grade)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select('pdl')
            ->from('wms:Produto\DadoLogistico', 'pdl')
            ->innerJoin('pdl.embalagem', 'pe')
            ->innerJoin('pdl.normaPaletizacao', 'np')
            ->where("pe.codProduto = '$idProduto' AND pe.grade = '$grade'");

        return $dql->getQuery()->getResult();
    }
}
