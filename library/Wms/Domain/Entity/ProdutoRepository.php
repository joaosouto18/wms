<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
	Wms\Domain\Entity\Produto as ProdutoEntity,
	Wms\Domain\Entity\Produto\Embalagem as EmbalagemEntity,
	Wms\Domain\Entity\Produto\Volume as VolumeEntity,
	Wms\Domain\Entity\Produto\NormaPaletizacao as NormaPaletizacaoEntity,
	Doctrine\Common\Persistence\ObjectRepository,
	Doctrine\ORM\Id\SequenceGenerator,
	Wms\Util\CodigoBarras,
	Wms\Util\Endereco as EnderecoUtil,
    Core\Util\Produto as ProdutoUtil;

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

  public function getProdutosSemPickingByExpedicoes($expedicoes) {
      $sessao = new \Zend_Session_Namespace('deposito');
      $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
      $central = $deposito->getFilial()->getCodExterno();

      $produtosRessuprir = $this->getEntityManager()->getRepository("wms:Expedicao")->getProdutosSemOnda($expedicoes, $central);
      $produtosSemPicking = array();

      foreach ($produtosRessuprir as $produto){
          $codProduto = $produto['COD_PRODUTO'];
          $grade = $produto['DSC_GRADE'];

          $produtoEn = $this->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
          $idPicking = $this->getEnderecoPicking($produtoEn,"ID");
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

  public function save(ProdutoEntity $produtoEntity, array $values) {

	extract($values['produto']);

	$em = $this->getEntityManager();
	$em->beginTransaction();

	try {
	  $linhaSeparacaoEntity = $em->getReference('wms:Armazenagem\LinhaSeparacao', $idLinhaSeparacao);
	  $tipoComercializacaoEntity = $em->getReference('wms:Produto\TipoComercializacao', $idTipoComercializacao);

      $produtoEntity->setLinhaSeparacao($linhaSeparacaoEntity);
      $produtoEntity->setTipoComercializacao($tipoComercializacaoEntity);
      $produtoEntity->setNumVolumes($numVolumes);
      $produtoEntity->setReferencia($referencia);
      $produtoEntity->setCodigoBarrasBase($codigoBarrasBase);

	  $em->persist($produtoEntity);

	  switch ($idTipoComercializacao) {
		case ProdutoEntity::TIPO_UNITARIO:
		  // gravo embalagens
		  $this->persistirEmbalagens($produtoEntity, $values);
		  // gravo dados logisticos
		  $this->persistirDadosLogisticos($values);

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

		  // limpo os volumes se houver
		  $volumeRepo = $em->getRepository('wms:Produto\Volume');
		  $volumes = $volumeRepo->findBy(array('codProduto' => $produtoEntity->getId(), 'grade' => $produtoEntity->getGrade()));

		  foreach ($volumes as $volumeEntity)
			$em->remove($volumeEntity);

		  break;
		case ProdutoEntity::TIPO_COMPOSTO:
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

		  // limpo os embalagens se houver
		  $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
		  $embalagens = $embalagemRepo->findBy(array('codProduto' => $produtoEntity->getId(), 'grade' => $produtoEntity->getGrade()));

		  foreach ($embalagens as $embalagemEntity)
			$em->remove($embalagemEntity);
		  break;
	  }

	  $em->commit();
	  $em->flush();
	} catch (\Exception $e) {
	  $em->rollback();
	  throw new \Exception($e->getMessage());
	}
  }

  /**
   * Persiste as embalagens do produto
   * 
   * @param ProdutoEntity $produtoEntity
   * @param array $values
   * @return boolean 
   */
  public function persistirEmbalagens(ProdutoEntity $produtoEntity, array &$values) {
	$em = $this->getEntityManager();

	//embalagens do produto
	if (!(isset($values['embalagens']) && (count($values['embalagens']) > 0)))
	  return false;

	foreach ($values['embalagens'] as $id => $itemEmbalagem) {
	  extract($itemEmbalagem);

	  if (!isset($itemEmbalagem['acao']))
		continue;

	  switch ($itemEmbalagem['acao']) {
		case 'incluir':

		  $embalagemEntity = new EmbalagemEntity;

            $embalagemEntity->setProduto($produtoEntity);
            $embalagemEntity->setGrade($produtoEntity->getGrade());
            $embalagemEntity->setDescricao($descricao);
            $embalagemEntity->setQuantidade($quantidade);
            $embalagemEntity->setIsPadrao($isPadrao);
            $embalagemEntity->setCBInterno($CBInterno);
            $embalagemEntity->setImprimirCB($imprimirCB);
            $embalagemEntity->setCodigoBarras($codigoBarras);
            $embalagemEntity->setEmbalado($embalado);
            $embalagemEntity->setCapacidadePicking($capacidadePicking);
            $embalagemEntity->setPontoReposicao($pontoReposicao);
            $embalagemEntity->setEndereco(null);

		  //valida o endereco informado
		  if (!empty($endereco)) {
			$endereco = EnderecoUtil::separar($endereco);
			$enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
			$enderecoEntity = $enderecoRepo->findOneBy(array('rua' => $endereco['RUA'], 'predio' => $endereco['PREDIO'], 'nivel' => $endereco['NIVEL'], 'apartamento' => $endereco['APTO']));

			if (!$enderecoEntity) {
			  throw new \Exception('Não existe o Endereço informado na embalagem ' . $descricao);
			}

			$embalagemEntity->setEndereco($enderecoEntity);
		  }

		  $em->persist($embalagemEntity);
		  $em->flush();

		  $produtoEntity->addEmbalagem($embalagemEntity);

		  $values['embalagens'][$id]['id'] = $embalagemEntity->getId();

		  if ($CBInterno == 'S') {
			$codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem($embalagemEntity->getId());
			$embalagemEntity->setCodigoBarras($codigoBarras);
		  }

		  break;
		case 'alterar':

		  $embalagemEntity = $em->getReference('wms:Produto\Embalagem', $id);

		  \Zend\Stdlib\Configurator::configure($embalagemEntity, $itemEmbalagem);

		  $embalagemEntity->setEndereco(null);

		  //valida o endereco informado
		  if (!empty($endereco)) {
			$endereco = EnderecoUtil::separar($endereco);
			$enderecoRepo = $em->getRepository('wms:Deposito\Endereco');
			$enderecoEntity = $enderecoRepo->findOneBy(array('rua' => $endereco['RUA'], 'predio' => $endereco['PREDIO'], 'nivel' => $endereco['NIVEL'], 'apartamento' => $endereco['APTO']));

			if (!$enderecoEntity) {
			  throw new \Exception('Não existe o Endereço informado na embalagem ' . $descricao);
			}

			$embalagemEntity->setEndereco($enderecoEntity);
		  }

		  // verifica se o codigo de barras é automatico
		  if ($CBInterno == 'S') {
			$codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem($id);
			$embalagemEntity->setCodigoBarras($codigoBarras);
		  }
          $embalagemEntity->setEmbalado($embalado);
          $embalagemEntity->setCapacidadePicking($capacidadePicking);
          $embalagemEntity->setPontoReposicao($pontoReposicao);

		  $em->persist($embalagemEntity);
		  break;
		case 'excluir':

		  $embalagemEntity = $em->getRepository('wms:Produto\Embalagem')->find($id);

		  if (!$embalagemEntity) {
			throw new \Exception('Codigo da Embalagem inválido.');
		  }

		  $em->remove($embalagemEntity);
		  $em->flush();
		  break;
	  }
	}

	return true;
  }

  /**
   * Persiste as volumes do produto
   * 
   * @param ProdutoEntity $produtoEntity
   * @param array $values
   * @return boolean 
   */
  public function persistirVolumes(ProdutoEntity $produtoEntity, array &$values) {
	$em = $this->getEntityManager();
	extract($values);

	// volumes
	if (!isset($volumes))
	  return false;

	$normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

	// normas de paletizacao
	if (isset($normasPaletizacao)) {

	  foreach ($normasPaletizacao as $key => $normaPaletizacao) {
		extract($normaPaletizacao);

		if (!isset($acao))
		  continue;

		switch ($acao) {
		  case 'incluir':
			$normaPaletizacaoEntity = new NormaPaletizacaoEntity;
			$normasPaletizacao[$key]['id'] = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
			break;
		  case 'alterar':
			$normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $id);
			$normasPaletizacao[$key]['id'] = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
			break;
		}
	  }
	}

	$volumeRepo = $em->getRepository('wms:Produto\Volume');

	foreach ($volumes as $id => $itemVolume) {
	  extract($itemVolume);

	  if (!isset($acao))
		continue;

	  // id
	  $itemVolume['id'] = $id;
	  // pega infos de normas de produtos
	  if (!empty($itemVolume['idNormaPaletizacao']))
		$itemVolume['idNormaPaletizacao'] = $normasPaletizacao[$itemVolume['idNormaPaletizacao']]['id'];

	  switch ($acao) {
		case 'incluir':
		  $volumeRepo->save($produtoEntity, $itemVolume);
		  break;
		case 'alterar':
		  $volumeRepo->save($produtoEntity, $itemVolume);
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
			$normaPaletizacaoEntity = $normaPaletizacaoRepo->remove($id);
			break;
		}
	  }
	}

	return true;
  }

  /**
   * Persiste as dadosLogisticos do produto
   * @param ProdutoEntity $produtoEntity
   * @param array $values 
   */
  public function persistirDadosLogisticos(array &$values) {
	$em = $this->getEntityManager();
	extract($values);

	// dadosLogisticos
	if (!isset($dadosLogisticos))
	  return false;

	$normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

	// normas de paletizacao
	if (isset($normasPaletizacao)) {

	  foreach ($normasPaletizacao as $key => $normaPaletizacao) {
		extract($normaPaletizacao);

		if (!isset($acao))
		  continue;

		switch ($acao) {
		  case 'incluir':
			$normaPaletizacaoEntity = new NormaPaletizacaoEntity;
			$normasPaletizacao[$key]['id'] = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
			break;
		  case 'alterar':
			$normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $id);
			$normasPaletizacao[$key]['id'] = $normaPaletizacaoRepo->save($normaPaletizacaoEntity, $normaPaletizacao);
			break;
		}
	  }
	}

	$dadoLogisticoRepo = $em->getRepository('wms:Produto\DadoLogistico');

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
	  // pego id da embalagem
	  if (!empty($itemDadoLogistico['idEmbalagem'])) {
		$itemDadoLogistico['idEmbalagem'] = $values['embalagens'][$itemDadoLogistico['idEmbalagem']]['id'];
	  }

	  switch ($acao) {
		case 'incluir':
		  $dadoLogisticoRepo->save($itemDadoLogistico);
		  break;
		case 'alterar':
		  $dadoLogisticoRepo->save($itemDadoLogistico);
		  break;
		case 'excluir':
		  $dadoLogisticoRepo->remove($id);
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
			$normaPaletizacaoEntity = $normaPaletizacaoRepo->remove($id);
			break;
		}
	  }
	}


//        foreach ($values['embalagens'] as $id => $itemEmbalagem) {
//            extract($itemEmbalagem);
//
//            if (!isset($itemEmbalagem['acao']))
//                continue;
//
//            if ($itemEmbalagem['acao'] == 'excluir') {
//                $embalagemEntity = $em->getRepository('wms:Produto\Embalagem')->find($id);
//                //$embalagemEntity = $em->getReference('wms:Produto\Embalagem', $id);
//                $em->remove($embalagemEntity);
//                $em->flush($embalagemEntity);
//            }
//        }

	return true;
  }

  /**
   *
   * @param type $id
   * @param type $grade
   * @return type 
   */
  public function buscarDadoLogistico($id, $grade = false) {
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
   */
  public function migrarDadoLogistico($id, $gradeOrigem, $gradeDestino) {
	$em = $this->getEntityManager();
	$produtoRepo = $em->getRepository('wms:Produto');

	$produtoOrigemEntity = $produtoRepo->findOneBy(array('id' => $id, 'grade' => $gradeOrigem));
	$produtoDestinoEntity = $produtoRepo->findOneBy(array('id' => $id, 'grade' => $gradeDestino));

	$tipoComercializacao = $produtoOrigemEntity->getTipoComercializacao();
	$codigoBarrasBase = $produtoOrigemEntity->getCodigoBarrasBase();
	$numVolumes = $produtoOrigemEntity->getNumVolumes();
	$linhaSeparacao = $produtoOrigemEntity->getLinhaSeparacao();

	$produtoDestinoEntity->setTipoComercializacao($tipoComercializacao)
			->setCodigoBarrasBase('')
			->setNumVolumes($numVolumes)
			->setLinhaSeparacao($linhaSeparacao);

	$em->persist($produtoDestinoEntity);
	$em->flush();

	$this->migrarVolume($id, $gradeOrigem, $gradeDestino);
	$this->migrarEmbalagem($id, $gradeOrigem, $gradeDestino);
  }

  /**
   * Migro os volumes
   * 
   * @param string $id Codigo do produto
   * @param string $gradeOrigem Grade de Origem
   * @param string $gradeDestino Grade de destinho
   */
  public function migrarVolume($id, $gradeOrigem, $gradeDestino) {
	$em = $this->getEntityManager();

	$produtoRepo = $em->getRepository('wms:Produto');
	$volumeRepo = $em->getRepository('wms:Produto\Volume');
	$normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

	$volumesOrigem = $volumeRepo->findBy(array('codProduto' => $id, 'grade' => $gradeOrigem));

	$idsNormaPaletizacao = array();

	$produtoDestinoEntity = $produtoRepo->findOneBy(array('id' => $id, 'grade' => $gradeDestino));
	$volumesDestino = $volumeRepo->findBy(array('codProduto' => $id, 'grade' => $gradeDestino));

	// limpo os volumes existentes
	foreach ($volumesDestino as $volumeEntity) {
	  $em->remove($volumeEntity);
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
  public function migrarEmbalagem($id, $gradeOrigem, $gradeDestino) {
	$em = $this->getEntityManager();

	$produtoRepo = $em->getRepository('wms:Produto');
	$embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
	$dadoLogisticoRepo = $em->getRepository('wms:Produto\DadoLogistico');
	$normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');

	$embalagemsOrigem = $embalagemRepo->findBy(array('codProduto' => $id, 'grade' => $gradeOrigem));

	$produtoDestinoEntity = $produtoRepo->findOneBy(array('id' => $id, 'grade' => $gradeDestino));
	$embalagemsDestino = $embalagemRepo->findBy(array('codProduto' => $id, 'grade' => $gradeDestino));

	// limpo os embalagems existentes
	foreach ($embalagemsDestino as $embalagemEntity) {
	  $em->remove($embalagemEntity);
	}

	// clono os de origem
	foreach ($embalagemsOrigem as $key => $embalagemEntity) {
	  // novo embalagem
	  $novoEmbalagemEntity = clone $embalagemEntity;

	  // alterando dados do embalagem
	  $novoEmbalagemEntity->setGrade($produtoDestinoEntity->getGrade())
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
   * @param string $indDadosLogisticos
   */
  public function buscarQtdProdutosDadosLogisticos($indDadosLogisticos) {

	$sql = "SELECT COUNT(P.COD_PRODUTO) AS QTD_PRODUTO
                      FROM PRODUTO P";

	if ($indDadosLogisticos == 'S') {
	  //produtos com dados logisticos - embalagem ou volumes
	  $sql .= " WHERE (EXISTS (SELECT 'X'
                                       FROM PRODUTO_VOLUME PVX 
                                      WHERE PVX.COD_PRODUTO = P.COD_PRODUTO
                                        AND PVX.DSC_GRADE = P.DSC_GRADE)
                          OR EXISTS (SELECT 'X'
                                       FROM PRODUTO_EMBALAGEM PEX 
                                      WHERE PEX.COD_PRODUTO = P.COD_PRODUTO
                                        AND PEX.DSC_GRADE = P.DSC_GRADE))";
	} else {
	  //produtos sem dados logisticos - embalagem e volumes
	  $sql .= " WHERE (NOT EXISTS (SELECT 'X'
                                           FROM PRODUTO_VOLUME PVX 
                                          WHERE PVX.COD_PRODUTO = P.COD_PRODUTO
                                            AND PVX.DSC_GRADE = P.DSC_GRADE)
                          AND NOT EXISTS (SELECT 'X'
                                           FROM PRODUTO_EMBALAGEM PEX 
                                          WHERE PEX.COD_PRODUTO = P.COD_PRODUTO
                                            AND PEX.DSC_GRADE = P.DSC_GRADE))";
	}

	return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
  }

  private function enviaDadosLogisticosEmbalagem(ProdutoEntity $produtoEntity) {
	$dql = $this->getEntityManager()->createQueryBuilder()
			->select('pe.descricao, pdl.altura, pdl.cubagem, pdl.largura, pdl.peso, pdl.profundidade, pe.quantidade ')
			->from('wms:Produto\DadoLogistico', 'pdl')
			->innerJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.id = pdl.embalagem')
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
			->select('pe.descricao, pdl.altura, pdl.cubagem, pdl.largura, pdl.peso, pdl.profundidade, pe.quantidade ')
			->from('wms:Produto\DadoLogistico', 'pdl')
			->innerJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.id = pdl.embalagem')
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
	  );

	  $i++;
	}

	return $client->salvar((string) $produtoEntity->getId(), $dadosLogisticos);
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
          'quantidade' => 1
	  );
	  $i++;
	}
	return $client->salvar((string) $produtoEntity->getId(), $dadosLogisticosVolume);
  }

  private function getSoapClient() {
	$conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/webservices.ini', APPLICATION_ENV);

	$dadosLogisticosUrl = 'integrarDadosLogisticos';

	return new \Zend_Soap_Client($conf->soap->$dadosLogisticosUrl->url, array('soapVersion' => SOAP_1_2, 'uri' => $conf->soap->$dadosLogisticosUrl->url)); //, 
  }

    public function buscarProdutosImprimirCodigoBarras($codProduto, $grade)
    {
    $dql = $this->getEntityManager()->createQueryBuilder()
        ->select('
                      1 as qtdItem,
                      p.id as idProduto,
                      p.grade,
                      p.descricao as dscProduto,
                      ls.descricao as dscLinhaSeparacao,
                      fb.nome as fabricante,
                      tc.descricao as dscTipoComercializacao,
                      pe.id as idEmbalagem,
                      pe.descricao as dscEmbalagem,
                      pv.id as idVolume,
                      pv.codigoSequencial as codSequencialVolume,
                      pv.descricao as dscVolume,
                      NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras')
        ->from('wms:Produto', 'p')
        ->innerJoin('p.tipoComercializacao', 'tc')
        ->leftJoin('p.linhaSeparacao', 'ls')
        ->leftJoin('p.fabricante', 'fb')
        ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade AND pe.isPadrao = \'S\'')
        ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade')
        ->where('p.id = :codProduto')
        ->andWhere("p.grade = :grade")
        ->setParameter('codProduto', $codProduto)
        ->setParameter('grade', $grade)
        ->andWhere('(pe.codigoBarras IS NOT NULL OR pv.codigoBarras IS NOT NULL)');

        return $dql->getQuery()->getResult();
    }


    public function buscaGradesProduto($codProduto)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p.grade')
            ->from('wms:Produto','p')
            ->where('p.id = :codProduto')
            ->setParameter('codProduto',ProdutoUtil::formatar($codProduto));

        $produtos = $queryBuilder->getQuery()->getArrayResult();
        $grades = array();
        foreach($produtos as $produto) {
            $grades[] = $produto['grade'];
        }
        return $grades;
    }

    public function getEnderecoPicking($produtoEntity,$tipoRetorno = "DSC")
    {
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
            return null;
        }

        $enderecoPicking = array();
        foreach($embalagemEn as $key => $embalagem) {
            if ($embalagem->getEndereco() != null) {
                if ($tipoRetorno == "DSC"){
                    $enderecoPicking[$key] = $embalagem->getEndereco()->getDescricao();
                } else {
                    $enderecoPicking[$key] = $embalagem->getEndereco()->getId();
                }
            } else{
                $enderecoPicking = null;
                break;
            }
        }
        return $enderecoPicking;
    }

    public function getNormaPaletizacaoPadrao($codProduto, $grade) {

        $produtoEntity = $this->findOneBy(array('id' => $codProduto, 'grade' => $grade));
        $volumes = $produtoEntity->getVolumes();
        $embalagens = $produtoEntity->getEmbalagens();

        $idNorma = NULL;
        $unidadePadrao = "";
        $unitizador = "";
        $qtdNorma = 0;
        $lastro = 0;
        $camadas = 0;
        $dscProduto = $produtoEntity->getDescricao();
        $result = array();

        foreach ($embalagens as $embalagem) {
            if ($embalagem->getIsPadrao()=="S") {
                $dadosLogisticos = $embalagem->getDadosLogisticos();
                $unidadePadrao = $embalagem->getDescricao();
                if (count($dadosLogisticos) >0){
                    $keyPadrao = 0;
                    foreach ($dadosLogisticos as $key => $normaPaletizacao) {
                        if ($normaPaletizacao->getNormaPaletizacao()->getIsPadrao() == 'S') {
                            $keyPadrao = $key;
                        }
                    }
                    $idNorma = $dadosLogisticos[$keyPadrao]->getNormaPaletizacao()->getId();
                    $qtdNorma = $dadosLogisticos[$keyPadrao]->getNormaPaletizacao()->getNumNorma();
                    $lastro = $dadosLogisticos[$keyPadrao]->getNormaPaletizacao()->getNumLastro();
                    $camadas = $dadosLogisticos[$keyPadrao]->getNormaPaletizacao()->getNumCamadas();
                    $unitizador = $dadosLogisticos[$keyPadrao]->getNormaPaletizacao()->getUnitizador()->getDescricao();
                    break;
                }
            }
        }

        foreach ($volumes as $volume) {
            $norma = $volume->getNormaPaletizacao()->getId();
            if ($norma != NULL) {
                $unidadePadrao = $volume->getDescricao();
                $qtdNorma = $volume->getNormaPaletizacao()->getNumNorma();
                $lastro = $volume->getNormaPaletizacao()->getNumLastro();
                $camadas = $volume->getNormaPaletizacao()->getNumCamadas();
                $unitizador = $volume->getNormaPaletizacao()->getUnitizador()->getDescricao();
                $idNorma = $norma;
                break;
            }
        }

        $result['idNorma'] = $idNorma;
        $result['unidade'] = $unidadePadrao;
        $result['unitizador'] = $unitizador;
        $result['qtdNorma'] = $qtdNorma;
        $result['lastro'] = $lastro;
        $result['camadas'] = $camadas;
        $result['dscProduto'] = $dscProduto;

        return $result;

    }

    public function getDadosProdutos($params)
    {
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
                 CASE WHEN (PV.COD_NORMA_PALETIZACAO IS NULL AND PDL.COD_NORMA_PALETIZACAO IS NULL) THEN 'SEM NORMA DE PALETIZACAO CADASTRADA'
                      WHEN (PE.COD_BARRAS IS NULL AND PV.COD_BARRAS IS NULL) THEN 'SEM CODIGO DE BARRAS'
                      WHEN (NP1.NUM_NORMA = 0 OR NP2.NUM_NORMA = 0) THEN 'SEM LASTRO OU CAMADAS DEFINIDOS'
                      WHEN (DEE.COD_DEPOSITO_ENDERECO IS NULL AND DEV.COD_DEPOSITO_ENDERECO IS NULL) THEN 'SEM ENDERECO DE PICKING'
                      ELSE 'CADASTRO CORRETO'
                 END AS \"PROBLEMA CADASTRAL\"
                 FROM PRODUTO P
           INNER JOIN LINHA_SEPARACAO L ON P.COD_LINHA_SEPARACAO = L.COD_LINHA_SEPARACAO
           INNER JOIN FABRICANTE F ON P.COD_FABRICANTE = F.COD_FABRICANTE
           INNER JOIN PRODUTO_CLASSE PC ON P.COD_PRODUTO_CLASSE = PC.COD_PRODUTO_CLASSE
            LEFT JOIN SIGLA S ON (P.COD_TIPO_COMERCIALIZACAO = S.COD_REFERENCIA_SIGLA AND S.COD_TIPO_SIGLA = 52)
            LEFT JOIN PRODUTO_VOLUME PV ON (P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE)
            LEFT JOIN PRODUTO_EMBALAGEM PE ON (P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
            LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM
            LEFT JOIN NORMA_PALETIZACAO NP1 ON PV.COD_NORMA_PALETIZACAO = NP1.COD_NORMA_PALETIZACAO
            LEFT JOIN NORMA_PALETIZACAO NP2 ON PDL.COD_NORMA_PALETIZACAO = NP2.COD_NORMA_PALETIZACAO
            LEFT JOIN UNITIZADOR U1 ON NP1.COD_UNITIZADOR = U1.COD_UNITIZADOR
            LEFT JOIN UNITIZADOR U2 ON NP2.COD_UNITIZADOR = U2.COD_UNITIZADOR
            LEFT JOIN DEPOSITO_ENDERECO DEE ON DEE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
            LEFT JOIN DEPOSITO_ENDERECO DEV ON DEV.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO";
        $queryWhere = "";
        if (!empty($params['grandeza'])) {
            $queryWhere = " WHERE ";
            $grandeza = $params['grandeza'];
            $grandeza = implode(',',$grandeza);
            $queryWhere = $queryWhere . " P.COD_LINHA_SEPARACAO IN ($grandeza) ";
        }
        $sql = $sql . $queryWhere . " ORDER BY P.COD_PRODUTO, P.DSC_GRADE, NVL(PV.COD_SEQUENCIAL_VOLUME,PE.QTD_EMBALAGEM)";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function relatorioListagemProdutos(array $params = array())
    {
        extract ($params);
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

    public function getProdutoByCodBarras($codigoBarras)
    {
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
                        NVL(pv.descricao, \'\') descricaoVolume,
                        NVL(de1.descricao, de2.descricao) picking,
                        NVL(pv.codigoSequencial, \'\') sequenciaVolume')
            ->from('wms:Produto', 'p')
            ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade')
            ->leftJoin('p.linhaSeparacao', 'ls')
            ->leftJoin('pe.dadosLogisticos', 'dl')
            ->leftJoin('pe.endereco', 'de1')
            ->leftJoin('dl.normaPaletizacao', 'np_embalagem')
            ->leftJoin('np_embalagem.unitizador', 'unitizador_embalagem')
            ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade')
            ->leftJoin('pv.endereco', 'de2')
            ->leftJoin('pv.normaPaletizacao', 'np_volume')
            ->leftJoin('np_volume.unitizador', 'unitizador_volume')
            ->where('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
            ->setParameters(
                array(
                    'codigoBarras' => $codigoBarras,
                )
            );

        return $dql->getQuery()->getArrayResult();
    }

    public function relatorioProdutosSemPicking(array $params = array())
    {
        extract ($params);
        $cond="";
        if (!empty($params['rua'])){
            $rua = $params['rua'];
            $cond=" AND  DE.NUM_RUA = ".$rua." ";
        }

        $sql = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO as \"descricao\",
                       DE.COD_DEPOSITO_ENDERECO as \"codigo\",
                       DE.COD_AREA_ARMAZENAGEM as \"areaArmazenagem\",
                       DE.IND_ATIVO  as \"ativo\",
                       DE.IND_SITUACAO  as \"status\"
                FROM DEPOSITO_ENDERECO DE
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_DEPOSITO_ENDERECO=DE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_DEPOSITO_ENDERECO=DE.COD_DEPOSITO_ENDERECO
                WHERE (PV.COD_DEPOSITO_ENDERECO IS NULL AND PE.COD_DEPOSITO_ENDERECO IS NULL)
                    AND DE.COD_CARACTERISTICA_ENDERECO <> 37 AND DE.IND_SITUACAO='D'
                      ".$cond."
                ORDER BY \"descricao\"";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaSeEProdutoComposto($idProduto)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.numVolumes')
            ->from('wms:Produto', 'p')
            ->where('p.id = :codProduto')
            ->setParameter('codProduto', $idProduto);

        return $dql->getQuery()->getResult();
    }

    public function getProdutoEmbalagem()
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('pe.descricao, IDENTITY(pe.produto) AS produto, pe.id')
            ->from('wms:Produto\Embalagem', 'pe')
            ->orderBy('pe.isPadrao', 'desc');

        return $dql->getQuery()->getResult();

    }

}
