<?php

namespace Wms\Domain\Entity\Deposito;

use Bisna\Base\Service\Exception;
use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity,
    Wms\Util\Endereco as EnderecoUtil,
    Core\Util\Converter;
use Wms\Domain\Entity\Enderecamento\Estoque;
use Wms\Domain\Entity\Produto;

/**
 * Endereco
 *
 */
class EnderecoRepository extends EntityRepository {

    /**
     * Checa se existem enderecos com os mesmos valores de enderecamento
     * e diferentes caracteristicas
     *
     * @param array $values
     * @return boolean Caso faixa de endereco ok retorna verdadeiro
     */
    public function checarEndereco(array $values) {
        extract($values['identificacao']);
        $em = $this->getEntityManager();

        $query = $em->createQuery('
            SELECT DISTINCT e.idAreaArmazenagem, e.idCaracteristica, e.idEstruturaArmazenagem, e.idTipoEndereco
            FROM wms:Deposito\Endereco e
            WHERE e.deposito = :idDeposito
                AND e.rua BETWEEN :inicialRua AND :finalRua
                AND e.predio BETWEEN :inicialPredio AND :finalPredio
                AND e.nivel BETWEEN :inicialNivel AND :finalNivel
                AND e.apartamento BETWEEN :inicialApartamento AND :finalApartamento');

        $query->setParameter('idDeposito', $idDeposito)
                ->setParameter('inicialRua', $inicialRua)
                ->setParameter('finalRua', $finalRua)
                ->setParameter('inicialPredio', $inicialPredio)
                ->setParameter('finalPredio', $finalPredio)
                ->setParameter('inicialNivel', $inicialNivel)
                ->setParameter('finalNivel', $finalNivel)
                ->setParameter('inicialApartamento', $inicialApartamento)
                ->setParameter('finalApartamento', $finalApartamento);

        if (!empty($lado)) {
            if ($lado == "P")
                $query->andWhere("MOD(e.predio,2) = 0");
            if ($lado == "I")
                $query->andWhere("MOD(e.predio,2) = 1");
        }

        $enderecos = $query->getArrayResult();

        return (count($enderecos) > 0) ? true : false;
    }

    /**
     *
     * @param EnderecoEntity $enderecoEntity
     * @param array $values
     * @throws \Exception
     */
    public function save(EnderecoEntity $enderecoEntity = null, array $values) {
        extract($values['identificacao']);
        $em = $this->getEntityManager();

        $paramsUrl = $values['identificacao'];
        $paramsUrl['controller'] = 'endereco';
        $paramsUrl['action'] = 'listar-existentes-ajax';

        $deposito = $em->getReference('wms:Deposito', $idDeposito);
        $caracteristica = $em->getReference('wms:Deposito\Endereco\Caracteristica', $idCaracteristica);
        $estruturaArmazenagem = $em->getReference('wms:Armazenagem\Estrutura\Tipo', $idEstruturaArmazenagem);
        $tipoEndereco = $em->getReference('wms:Deposito\Endereco\Tipo', $idTipoEndereco);
        $areaArmazenagem = $em->getReference('wms:Deposito\AreaArmazenagem', $idAreaArmazenagem);

        //echo $ativo;exit;
        //caso edicao
        
        if (!empty($id)) {
            /** @var Endereco $enderecoEntity */
            $enderecoEntity = $em->getReference('wms:Deposito\Endereco', $id);

            $bloquearSaida = in_array('S', $bloqueada);
            $bloquearEntrada = in_array('E', $bloqueada);

            if ($bloquearSaida || $bloquearEntrada) {
                $dscEndereco = $enderecoEntity->getDescricao();
                if (!empty(self::validaEnderecosPicking([$id]))) throw new \Exception("Esse endereço $dscEndereco está vinculado como picking em algum produto, por esse motivo não pode ser bloqueado!");
                if (!empty(self::validaEnderecosComReservas([$id]))) throw new \Exception("Esse endereço $dscEndereco tem reservas pendentes, por esse motivo não pode ser bloqueado!");
            }

            $enderecoEntity->setBloqueadaSaida($bloquearSaida);
            $enderecoEntity->setBloqueadaEntrada($bloquearEntrada);
            $enderecoEntity->setDeposito($deposito);
            $enderecoEntity->setCaracteristica($caracteristica);
            $enderecoEntity->setEstruturaArmazenagem($estruturaArmazenagem);
            $enderecoEntity->setTipoEndereco($tipoEndereco);
            $enderecoEntity->setStatus($status);
            $enderecoEntity->setAtivo($ativo);
            $enderecoEntity->setAreaArmazenagem($areaArmazenagem);

            $em->persist($enderecoEntity);
        } else {
            //loop de rua
            for ($auxRua = $inicialRua; $auxRua <= $finalRua; $auxRua++) {
                //loop de predio
                for ($auxPredio = $inicialPredio; $auxPredio <= $finalPredio; $auxPredio++) {
                    //loop de nivel
                    for ($auxNivel = $inicialNivel; $auxNivel <= $finalNivel; $auxNivel++) {
                        //loop de apartamento
                        for ($auxApto = $inicialApartamento; $auxApto <= $finalApartamento; $auxApto++) {

                            //checa o cadastro dos lados
                            if (isset($lado) && (($lado == 'I' && !($auxPredio % 2)) || ($lado == 'P' && ($auxPredio % 2))))
                                continue;

                            //procura um endereco existente com as caracteristicas
                            $enderecoEntity = $this->findOneBy(array(
                                'idDeposito' => $idDeposito,
                                'rua' => $auxRua,
                                'predio' => $auxPredio,
                                'nivel' => $auxNivel,
                                'apartamento' => $auxApto,
                            ));

                            //cria um objeto caso n encontre->get
                            if ($enderecoEntity == null) {
                                $enderecoEntity = new EnderecoEntity;
                                $enderecoEntity->setDisponivel("S");
                            } else {
                                //enderecosExistentes
                                if (!in_array($enderecoEntity->getId(), $enderecosSobrepor))
                                    continue;
                            }

                            $endereco = array(
                                'rua' => $auxRua,
                                'predio' => $auxPredio,
                                'nivel' => $auxNivel,
                                'apartamento' => $auxApto);

                            $dscEndereco = EnderecoUtil::formatar($endereco);

                            $enderecoEntity->setRua($auxRua)
                                    ->setPredio($auxPredio)
                                    ->setNivel($auxNivel)
                                    ->setApartamento($auxApto)
                                    ->setBloqueadaSaida(in_array('S', $bloqueada))
                                    ->setBloqueadaEntrada(in_array('E', $bloqueada))
                                    ->setDeposito($deposito)
                                    ->setCaracteristica($caracteristica)
                                    ->setEstruturaArmazenagem($estruturaArmazenagem)
                                    ->setTipoEndereco($tipoEndereco)
                                    ->setStatus($status)
                                    ->setAreaArmazenagem($areaArmazenagem)
                                    ->setDescricao($dscEndereco)
                                    ->setAtivo($ativo);


                            $em->persist($enderecoEntity);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @param int $id
     */
    public function remove($id) {
        $em = $this->getEntityManager();
        $auxPredioroxy = $em->getReference('wms:Deposito\Endereco', $id);
        $em->remove($auxPredioroxy);
    }

    /**
     *
     * @return type
     */
    public function getRuas() {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $auxRuauas = $qb->select('DISTINCT e.rua')
                ->from('wms:Deposito\Endereco', 'e')
                ->orderBy('e.rua');

        return $auxRuauas->getQuery()->getResult();
    }

    public function getPicking() {
        $em = $this->getEntityManager();
        $tipoPicking = Endereco::PICKING;

        $dql = $em->createQueryBuilder()
                ->select('e.descricao as DESCRICAO, MOD(e.predio,2) as lado')
                ->from('wms:Deposito\Endereco', 'e')
                ->orderBy("e.rua, lado , e.predio, e.apartamento")
                ->where("e.idCaracteristica = '" . $tipoPicking . "'")
                ->groupBy("e.descricao, e.predio");
        $enderecos = $dql->getQuery()->getResult();

        return $enderecos;
    }

    public function getEnderecoByProduto($idProduto, $grade) {
        $em = $this->getEntityManager();
        $produtoEn = $em->getRepository("wms:Produto")->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        if (count($produtoEn->getEmbalagens()) <= 0) {
            $dql = $em->createQueryBuilder()
                    ->select('e.descricao as DESCRICAO, e.id')
                    ->from("wms:Produto\Volume", "pv")
                    ->innerJoin("pv.endereco", "e")
                    ->innerJoin("pv.produto", "p")
                    ->where("p.id = '$idProduto'")
                    ->andWhere("p.grade = '$grade'")
                    ->groupBy("e.descricao, e.id");
        } else {
            $dql = $em->createQueryBuilder()
                    ->select('e.descricao as DESCRICAO, e.id')
                    ->from("wms:Produto\Embalagem", "pe")
                    ->innerJoin("pe.endereco", "e")
                    ->innerJoin("pe.produto", "p")
                    ->where("p.id = '$idProduto'")
                    ->andWhere("p.grade = '$grade'")
                    ->groupBy("e.descricao, e.id");
        }

        $enderecos = $dql->getQuery()->getResult();

        return $enderecos;
    }

    public function getEnderecosAlocados() {
        $sql = "
            SELECT DESCRICAO
            FROM (
                        SELECT DISTINCT DSC_DEPOSITO_ENDERECO as DESCRICAO, MOD(DE.NUM_PREDIO,2) as LADO, DE.NUM_RUA, DE.NUM_PREDIO, DE.NUM_APARTAMENTO
                          FROM DEPOSITO_ENDERECO DE
                         WHERE DE.COD_DEPOSITO_ENDERECO IN (SELECT DE2.COD_DEPOSITO_ENDERECO
                                                              FROM PRODUTO_EMBALAGEM PE
                                                        INNER JOIN DEPOSITO_ENDERECO DE2 ON DE2.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO)
                            OR DE.COD_DEPOSITO_ENDERECO IN (SELECT DE3.COD_DEPOSITO_ENDERECO
                                                              FROM PRODUTO_VOLUME PV
                                                        INNER JOIN DEPOSITO_ENDERECO DE3 ON DE3.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO)
            ) a
            ORDER BY NUM_RUA, LADO , NUM_PREDIO, NUM_APARTAMENTO
        ";

        $array = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;
    }

    public function getVolumesByPicking($idEndereco, $unico = true) {
        $em = $this->getEntityManager();

        $dql = $em->createQueryBuilder()
                ->select('p.id as codProduto, p.grade, p.descricao as produto, pv.id as codVolume, pv.descricao, e.descricao as endereco')
                ->distinct(true)
                ->from("wms:Produto\Volume", "pv")
                ->InnerJoin("pv.endereco", "e")
                ->InnerJoin("pv.produto", "p")
                ->where("e.id = $idEndereco");

        if ($unico == true) {
            $produto = $dql->getQuery()->setMaxResults(1)->getArrayResult();
        } else {
            $produto = $dql->getQuery()->getArrayResult();
        }

        if (count($produto) <= 0) {
            $dql = $em->createQueryBuilder()
                    ->select("p.id as codProduto, p.grade, p.descricao as produto, 0 as codVolume, 'PRODUTO UNITARIO' as descricao, e.descricao as endereco")
                    ->distinct(true)
                    ->from("wms:Produto\Embalagem", "pe")
                    ->leftJoin("pe.endereco", "e")
                    ->leftJoin("pe.produto", "p")
                    ->where("e.id = $idEndereco");

            if ($unico == true) {
                $produto = $dql->getQuery()->setMaxResults(1)->getArrayResult();
            } else {
                $produto = $dql->getQuery()->getArrayResult();
            }
        }
        return $produto;
    }

    public function checkTipoEnderecoPicking($endereco, $produtoId, $embalagemId) {

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        /* $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
          $indPickMultiProduto = $this->getSystemParameterValue('IND_PICKING_MULTIPRODUTO');
          if ($indPickMultiProduto == 'N')
          $enderecoRepo->checkTipoEnderecoPicking($enderecoDestinoEn->getDescricao(),$idProduto, $data['embalagem']->getId()); */


        $result = $this->getProdutoByEndereco($endereco, false, true);

        if (!empty($result)) {
            $tipoPicking = $this->getSystemParameterValue('IND_TIPO_PICKING');

            if ($tipoPicking == 'P') {
                foreach ($result as $item) {
                    if ($item['codProduto'] != $produtoId)
                        throw new \Exception("Não é possível adicionar outro produto neste endereço de picking.");
                }
            } else if ($tipoPicking == 'E') {
                foreach ($result as $item) {
                    if ($item['codEmbalagem'] != $embalagemId)
                        throw new \Exception("Não é possível adicionar outra embalagem neste endereço de picking.");
                }
            }
        }

        return true;
    }

    /**
     * @param $dscEndereco
     * @param bool $unico
     * @param bool $picking | true pega endereço do tipo picking
     * @return array
     */
    public function getProdutoByEndereco($dscEndereco, $unico = true, $picking = false, $detalharVolume = true)
    {

        $endereco = EnderecoUtil::formatar($dscEndereco);

        $select = "
            p.id as codProduto, 
            p.grade,
            p.descricao, 
            NVL(pe.capacidadePicking, pv.capacidadePicking) capacidadePicking, 
            f.nome fabricante, 
            p.referencia
        ";

        if ($detalharVolume == true) {
            $select = $select . "            
            ,NVL(pe.descricao, pv.descricao) descricaoEmbVol, 
             NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras 
            ";
        }

        $dql = $this->_em->createQueryBuilder()
            ->select($select)
            ->distinct(true)
            ->from("wms:Deposito\Endereco", "de")
            ->leftJoin("wms:Produto\Volume", "pv", "WITH", "pv.endereco = de and pv.dataInativacao is null")
            ->leftJoin("wms:Produto\Embalagem", "pe", "WITH", "pe.endereco = de and pe.dataInativacao is null")
            ->innerJoin("wms:Produto", "p", "WITH", "(p.id = pv.codProduto and p.grade = pv.grade) or (p.id = pe.codProduto and p.grade = pe.grade)")
            ->leftJoin('p.fabricante', 'f')
            ->where("de.descricao = '$endereco'");

        if ($picking) {
            $dql->andWhere('de.idCaracteristica =' . Endereco::PICKING);
        }

        if ($unico == true) {
            $produto = $dql->getQuery()->setMaxResults(1)->getArrayResult();
        } else {
            $produto = $dql->getQuery()->getArrayResult();
        }

        return $produto;

    }

    public function getEnderecoesDisponivesByParam($params) {
        $idCaracteristicaEndereco = Endereco::PICKING;
        $estruturaBlocado = \Wms\Domain\Entity\Armazenagem\Estrutura\Tipo::BLOCADO;

        extract($params);
        $query = "
         SELECT DE.COD_DEPOSITO_ENDERECO,
                DE.DSC_DEPOSITO_ENDERECO,
                CA.DSC_CARACTERISTICA_ENDERECO,
                AA.DSC_AREA_ARMAZENAGEM,
                TP.DSC_TIPO_EST_ARMAZ,
                TE.DSC_TIPO_ENDERECO,
                DE.NUM_RUA,
                DE.NUM_PREDIO,
                DE.NUM_NIVEL,
                LONGARINA.TAMANHO_LONGARINA - LONGARINA.OCUPADO as TAMANHO_DISPONIVEL
           FROM DEPOSITO_ENDERECO DE
          INNER JOIN CARACTERISTICA_ENDERECO CA ON DE.COD_CARACTERISTICA_ENDERECO = CA.COD_CARACTERISTICA_ENDERECO
          INNER JOIN AREA_ARMAZENAGEM AA        ON DE.COD_AREA_ARMAZENAGEM = AA.COD_AREA_ARMAZENAGEM
          INNER JOIN TIPO_EST_ARMAZ TP          ON DE.COD_TIPO_EST_ARMAZ = TP.COD_TIPO_EST_ARMAZ
          INNER JOIN TIPO_ENDERECO TE           ON DE.COD_TIPO_ENDERECO = TE.COD_TIPO_ENDERECO
          INNER JOIN V_OCUP_RESERVA_LONGARINA LONGARINA
		          ON LONGARINA.NUM_PREDIO = DE.NUM_PREDIO
                 AND LONGARINA.NUM_NIVEL  = DE.NUM_NIVEL
                 AND LONGARINA.NUM_RUA    = DE.NUM_RUA
          WHERE DE.IND_ATIVO = 'S'
          AND ((DE.COD_CARACTERISTICA_ENDERECO  != $idCaracteristicaEndereco) OR (DE.COD_TIPO_EST_ARMAZ = $estruturaBlocado))
                  ";

        if (!empty($unitizador)) {
            $unitizadorEn = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador")->find($unitizador);
            $larguraUnitizador = $unitizadorEn->getLargura(false) * 100;
            $query = $query . " AND ((LONGARINA.TAMANHO_LONGARINA - LONGARINA.OCUPADO) >= $larguraUnitizador)";
        }

        if ($ocupado == 'D') {
            $query = $query . " AND DE.IND_DISPONIVEL = 'S'";
        }
        if ($ocupado == 'O') {
            $query = $query . " AND DE.IND_DISPONIVEL = 'N'";
        }

        if (!empty($inicialRua)) {
            $query = $query . " AND DE.NUM_RUA >= $inicialRua";
        }
        if (!empty($finalRua)) {
            $query = $query . " AND DE.NUM_RUA <= $finalRua";
        }
        if (!empty($inicialPredio)) {
            $query = $query . " AND DE.NUM_PREDIO >= $inicialPredio";
        }
        if (!empty($finalPredio)) {
            $query = $query . " AND DE.NUM_PREDIO <= $finalPredio";
        }
        if (!empty($inicialNivel)) {
            $query = $query . " AND DE.NUM_NIVEL >= $inicialNivel";
        }
        if (!empty($finalNivel)) {
            $query = $query . " AND DE.NUM_NIVEL <= $finalNivel";
        }
        if (!empty($inicialApartamento)) {
            $query = $query . " AND DE.NUM_APARTAMENTO >= $inicialApartamento";
        }
        if (!empty($finalApartamento)) {
            $query = $query . " AND DE.NUM_APARAMENTO >= $finalApartamento";
        }

        if (!empty($lado)) {
            if ($lado == "P")
                $query = $query . " AND MOD(DE.NUM_PREDIO,2) = 0";
            if ($lado == "I")
                $query = $query . " AND MOD(DE.NUM_PREDIO,2) = 1";
        }

        if (!empty($status))
            $query = $query . " AND DE.IND_STATUS = $status";
        if (!empty($idCaracteristica))
            $query = $query . " AND DE.COD_CARACTERISTICA_ENDERECO = $idCaracteristica";
        if (!empty($idEstruturaArmazenagem))
            $query = $query . " AND DE.COD_TIPO_EST_ARMAZ = $idEstruturaArmazenagem";
        if (!empty($idTipoEndereco))
            $query = $query . " AND DE.COD_TIPO_ENDERECO = $idTipoEndereco";
        if (!empty($idAreaArmazenagem))
            $query = $query . " AND DE.COD_AREA_ARMAZENAGEM = $idAreaArmazenagem";

        $query = $query . "  ORDER BY (LONGARINA.TAMANHO_LONGARINA - LONGARINA.OCUPADO), DE.NUM_RUA, DE.NUM_PREDIO, DE.NUM_NIVEL";

        $array = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;
    }

    public function getEnderecosAdjacentes($predio, $rua, $nivel, $apartamento, $qtdAdjacentes) {
        $sql = "
            SELECT * FROM (
            SELECT DISTINCT * FROM (
            SELECT DE.COD_DEPOSITO_ENDERECO,
                   CASE WHEN E.COD_DEPOSITO_ENDERECO IS NULL THEN 'S'
                        ELSE 'N'
                   END AS DISPONIVEL,
                   DE.NUM_APARTAMENTO
             FROM DEPOSITO_ENDERECO DE
             LEFT JOIN ESTOQUE E ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
            WHERE DE.NUM_PREDIO = $predio
              AND DE.NUM_NIVEL = $nivel
              AND DE.NUM_RUA = $rua
              AND DE.NUM_APARTAMENTO >= $apartamento)
              ORDER BY CAST(NUM_APARTAMENTO AS INT) ASC) WHERE ROWNUM <= $qtdAdjacentes
              ";

        $array = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;
    }

    public function enderecoOcupado($enderecoId) {
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $estoquesEn = $estoqueRepo->findBy(array('depositoEndereco' => $enderecoId));

        if (count($estoquesEn) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function ocuparLiberarEnderecosAdjacentes($enderecoEn, $qtdAdjacente, $operacao = "OCUPAR", $idUma = "") {
        if ($operacao == "OCUPAR") {
            if ($enderecoEn->getDisponivel() == "S") {
                $enderecoEn->setDisponivel("N");
                $this->getEntityManager()->persist($enderecoEn);
            }

        } else {

            $existeReservaEntradaPendente = false;
            $existeOutroEstoque = false;

            $SQL = " SELECT * 
                       FROM RESERVA_ESTOQUE_ENDERECAMENTO REE
                       INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                       WHERE RE.IND_ATENDIDA = 'N'
                         AND RE.COD_DEPOSITO_ENDERECO = '" . $enderecoEn->getId() . "'
                         AND REE.UMA <> $idUma";

            $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
            if (count($result) > 0) {
                $existeReservaEntradaPendente = true;
            }

            $SQL = " SELECT *
                       FROM ESTOQUE E 
                      WHERE E.COD_DEPOSITO_ENDERECO = '" . $enderecoEn->getId() . "'";
            $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
            if (count($result) > 0) {
                $existeOutroEstoque = true;
            }

            if (($existeOutroEstoque == false) && ($existeReservaEntradaPendente == false)) {
                if ($enderecoEn->getDisponivel() == "N") {
                    $enderecoEn->setDisponivel("S");
                    $this->getEntityManager()->persist($enderecoEn);
                }
            }
        }
    }

    public function getTamanhoDisponivelByPredio($rua, $predio, $nivel) {
        $tamanhoLongarinaRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco\TamanhoLongarina");
        $longarinaEn = $tamanhoLongarinaRepo->findOneBy(array('predio' => $predio, 'rua' => $rua));

        if ($longarinaEn != NULL) {
            $tamanhoLongarina = $longarinaEn->getTamanho();
        } else {
            $parametro = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'TAMANHO_LONGARINA_PADRAO'));
            $tamanhoLongarina = $parametro->getValor();
        }

        $sql = "SELECT SUM(U.NUM_LARGURA_UNITIZADOR) as OCUPADO
                  FROM DEPOSITO_ENDERECO DE
                  LEFT JOIN ESTOQUE E ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                  LEFT JOIN UNITIZADOR U ON E.COD_UNITIZADOR = U.COD_UNITIZADOR
                 WHERE DE.NUM_PREDIO = $predio
                   AND DE.NUM_NIVEL = $nivel
                   AND DE.NUM_RUA = $rua";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if ($result[0]['OCUPADO'] == NULL) {
            $ocupado = 0;
        } else {
            $ocupado = $result[0]['OCUPADO'] * 100;
        }

        $disponivel = $tamanhoLongarina - $ocupado;

        return $disponivel;
    }

    public function getEndereco($rua, $predio, $nivel, $apto) {
        $nivel = ((int) $nivel === 0) ? '00' : $nivel;
        if (empty($rua) || empty($predio) || empty($nivel) || empty($apto)) {
            throw new \Exception("É necessário informar todo o endereço");
        }
        $source = $this->_em->createQueryBuilder()
                ->select("e.id, e.descricao")
                ->from('wms:Deposito\Endereco', 'e')
                ->andWhere("e.rua = $rua")
                ->andWhere("e.predio = $predio")
                ->andWhere("e.nivel = $nivel")
                ->andWhere("e.apartamento = $apto");

        $result = $source->getQuery()->getSingleResult();
        if ($result == null) {
            throw new \Exception("Endereço não encontrado.");
        }
        return $result;
    }

    public function getOcupacaoRuaReport($params) {
        $ruaInicial = $params['ruaInicial'];
        $ruaFinal = $params['ruaFinal'];

        $sqlWhere = "";
        if ($ruaFinal != "") {
            $sqlWhere = $sqlWhere . " AND DE.NUM_RUA <= " . $ruaFinal;
        }
        if ($ruaInicial != "") {
            $sqlWhere = $sqlWhere . " AND DE.NUM_RUA >= " . $ruaInicial;
        }

        $SQL = "SELECT NUM_RUA,
                       SUM(POS_EXISTENTES) as POS_EXISTENTES,
                       SUM(CASE WHEN (POS_EXISTENTES - QTD_OCUPADOS) > POS_DISPONIVEIS THEN POS_DISPONIVEIS ELSE (POS_EXISTENTES - QTD_OCUPADOS) END) AS POS_DISPONIVEIS
                  FROM (SELECT DE.NUM_RUA,
                               DE.NUM_PREDIO,
                               DE.NUM_NIVEL,
                               SUM(DE.QTD_ENDERECO) as POS_EXISTENTES,
                               SUM(CASE WHEN DISP.QTD_DISPONIVEL > DE.QTD_ENDERECO THEN DE.QTD_ENDERECO ELSE DISP.QTD_DISPONIVEL END) AS POS_DISPONIVEIS,
                               SUM(NVL(OCUP.QTD_OCUPADOS,0)) as QTD_OCUPADOS
                          FROM (SELECT COUNT(DE.COD_DEPOSITO_ENDERECO) as QTD_ENDERECO,
                                       DE.NUM_PREDIO, DE.NUM_NIVEL, DE.NUM_RUA
                                  FROM DEPOSITO_ENDERECO DE
                                 WHERE DE.IND_ATIVO = 'S'
                                 GROUP BY DE.NUM_PREDIO, DE.NUM_NIVEL, DE.NUM_RUA) DE
                     LEFT JOIN (SELECT TRUNC((O.TAMANHO_LONGARINA - O.OCUPADO) /UN.LARGURA) as QTD_DISPONIVEL,
                                       O.TAMANHO_LONGARINA - O.OCUPADO as LARGURA_DISPONIVEL,
                                       O.NUM_PREDIO, O.NUM_NIVEL, O.NUM_RUA
                                  FROM V_OCUPACAO_LONGARINA O,
                                       (SELECT MIN(NUM_LARGURA_UNITIZADOR * 100) LARGURA FROM UNITIZADOR) UN) DISP
                            ON DISP.NUM_PREDIO = DE.NUM_PREDIO
                           AND DISP.NUM_NIVEL = DE.NUM_NIVEL
                           AND DISP.NUM_RUA = DE.NUM_RUA
                     LEFT JOIN (SELECT COUNT(DISTINCT (DE.COD_DEPOSITO_ENDERECO)) as QTD_OCUPADOS,
                                       DE.NUM_RUA, DE.NUM_PREDIO, DE.NUM_NIVEL
                                  FROM ESTOQUE E LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                                 GROUP BY DE.NUM_RUA, DE.NUM_PREDIO, DE.NUM_NIVEL) OCUP
                            ON OCUP.NUM_PREDIO = DE.NUM_PREDIO
                           AND OCUP.NUM_NIVEL = DE.NUM_NIVEL
                           AND OCUP.NUM_RUA = DE.NUM_RUA
                     WHERE 1 = 1
                      AND DE.NUM_NIVEL > 0
                      $sqlWhere
                     GROUP BY DE.NUM_RUA, DE.NUM_PREDIO, DE.NUM_NIVEL) OCUP
                 GROUP BY NUM_RUA
                 ORDER BY NUM_RUA";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getTipoArmazenamentoByEndereco($endereco) {
        $sql = "SELECT DE.COD_DEPOSITO_ENDERECO, EA.COD_TIPO_EST_ARMAZ FROM DEPOSITO_ENDERECO DE
                INNER JOIN TIPO_EST_ARMAZ EA ON DE.COD_TIPO_EST_ARMAZ = EA.COD_TIPO_EST_ARMAZ
                WHERE DE.COD_DEPOSITO_ENDERECO = $endereco";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getValidaTamanhoEndereco($idEndereco, $larguraPalete) {
        $longarinaRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\VOcupacaoLongarina");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

        $tamanhoUnitizadorAlocado = 0;
        $estoquesEn = $estoqueRepo->findBy(array('depositoEndereco' => $idEndereco));
        foreach ($estoquesEn as $estoqueEn) {
            $unitizadorEn = $estoqueEn->getUnitizador();
            if ($unitizadorEn != NULL) {
                $tamanhoUnitizador = $unitizadorEn->getLargura(false) * 100;
                if ($tamanhoUnitizador > $tamanhoUnitizadorAlocado) {
                    $tamanhoUnitizadorAlocado = $tamanhoUnitizador;
                }
            }
        }

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $this->findOneBy(array('id' => $idEndereco));

        $rua = $enderecoEn->getRua();
        $predio = $enderecoEn->getPredio();
        $nivel = $enderecoEn->getNivel();

        /** @var \Wms\Domain\Entity\Armazenagem\VOcupacaoLongarina $longarinaEn */
        $longarinaEn = $longarinaRepo->findOneBy(array('rua' => $rua, 'predio' => $predio, 'nivel' => $nivel));
        $larguraLongarina = $longarinaEn->getTamanho();
        $tamanhoOcupado = $longarinaEn->getQtdOcupada() - $tamanhoUnitizadorAlocado;

        if ($tamanhoUnitizadorAlocado > $larguraPalete) {
            $larguraPalete = $tamanhoUnitizadorAlocado;
        }

        if (($tamanhoOcupado + ($larguraPalete)) > $larguraLongarina) {
            return false;
        }

        return true;
    }

    public function getOcupacaoPeriodoResumidoReport($params) {
        $dataInicial = $params['dataInicial1'];
        $dataFinal = $params['dataInicial2'];
        $ruaInicial = $params['ruaInicial'];
        $ruaFinal = $params['ruaFinal'];

        $sqlWhere = "";
        if ($ruaFinal != "") {
            $sqlWhere = $sqlWhere . " AND P.NUM_RUA <= " . $ruaFinal . " ";
        }
        if ($ruaInicial != "") {
            $sqlWhere = $sqlWhere . " AND P.NUM_RUA >= " . $ruaInicial . " ";
        }
        if(!empty($dataInicial)){
            $sqlWhere .= " AND (P.DTH_ESTOQUE >= TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI'))";
        }if(!empty($dataFinal)){
            $sqlWhere .= " AND (P.DTH_ESTOQUE <= TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        $sql = "SELECT NUM_RUA,
                        QTD_EXISTENTES,
                        QTD_OCUPADOS,
                        QTD_VAZIOS,
                        OCUPACAO,
                        TO_CHAR(DTH_ESTOQUE,'DD/MM/YYYY') as DTH_ESTOQUE
                   FROM POSICAO_ESTOQUE_RESUMIDO P
                  WHERE 1 = 1
                        $sqlWhere
                  ORDER BY DTH_ESTOQUE, TO_NUMBER(P.NUM_RUA)";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getOcupacaoPeriodoReport($params) {
        extract($params);

        $dataInicial = $params['dataInicial1'];
        $dataFinal = $params['dataInicial2'];
        $ruaInicial = $params['ruaInicial'];
        $ruaFinal = $params['ruaFinal'];
        $tipoPicking = Endereco::PICKING;

        $sqlWhere = "";
        if (isset($ruaInicial) && !empty($ruaFinal)) {
            $sqlWhere = $sqlWhere . " AND HIST.NUM_RUA <= " . $ruaFinal . " ";
        }
        if (isset($ruaInicial) && !empty($ruaFinal)) {
            $sqlWhere = $sqlWhere . " AND HIST.NUM_RUA >= " . $ruaInicial . " ";
        }
        $sqlWhereDepEnd = "";
        if (isset($ruaInicial) && !empty($ruaFinal)) {
            $sqlWhereDepEnd = $sqlWhereDepEnd . " AND DE.NUM_RUA <= " . $ruaFinal . " ";
        }
        if (isset($ruaInicial) && !empty($ruaFinal)) {
            $sqlWhereDepEnd = $sqlWhereDepEnd . " AND DE.NUM_RUA >= " . $ruaInicial . " ";
        }

//        $sql= " SELECT TO_CHAR(HIST.DTH_ESTOQUE,'DD/MM/YYYY') as DATA_ESTOQUE,
//                       DE.NUM_RUA as RUA,
//                       HIST.OCUPADO as PALETES_OCUPADOS,
//                       DE.QTD_EXISTENTES as PALETES_EXISTENTES,
//                       ROUND((( HIST.OCUPADO/ DE.QTD_EXISTENTES) * 100),2) AS PERCENTUAL_OCUPADOS
//                  FROM (
//                     SELECT COUNT(DISTINCT DE.COD_DEPOSITO_ENDERECO) as QTD_EXISTENTES, DE.NUM_RUA
//                       FROM DEPOSITO_ENDERECO DE WHERE DE.COD_CARACTERISTICA_ENDERECO <> $tipoPicking
//                      GROUP BY DE.NUM_RUA) DE
//                RIGHT JOIN (
//                     SELECT COUNT(DISTINCT PE.COD_DEPOSITO_ENDERECO) as OCUPADO, PE.DTH_ESTOQUE, DE.NUM_RUA
//                       FROM POSICAO_ESTOQUE PE
//                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
//                      WHERE PE.COD_DEPOSITO_ENDERECO IS NOT NULL AND DE.COD_CARACTERISTICA_ENDERECO <> $tipoPicking
//                       AND (PE.DTH_ESTOQUE BETWEEN TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI') AND TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))
//                   GROUP BY DE.NUM_RUA, PE.DTH_ESTOQUE) HIST
//                   ON HIST.NUM_RUA = DE.NUM_RUA
//                   $sqlWhere
//                   ORDER BY HIST.DTH_ESTOQUE, DE.NUM_RUA";

        $sql = "SELECT  HIST.DATA_ESTOQUE,
                        DE.NUM_RUA as RUA,
                        HIST.OCUPADO as PALETES_OCUPADOS,
                        DE.QTD_EXISTENTES as PALETES_EXISTENTES,
                        ROUND((( HIST.OCUPADO/ DE.QTD_EXISTENTES) * 100),2) AS PERCENTUAL_OCUPADOS
                    FROM (
                       SELECT COUNT(DISTINCT DE.COD_DEPOSITO_ENDERECO) as QTD_EXISTENTES, DE.NUM_RUA
                         FROM DEPOSITO_ENDERECO DE WHERE DE.COD_CARACTERISTICA_ENDERECO <> $tipoPicking
                        GROUP BY DE.NUM_RUA) DE
                  RIGHT JOIN (

                      SELECT COUNT(DISTINCT E.COD_DEPOSITO_ENDERECO) as OCUPADO, TO_CHAR(E.DTH_PRIMEIRA_MOVIMENTACAO,'DD/MM/YYYY') AS DATA_ESTOQUE, DE.NUM_RUA
                        FROM ESTOQUE E
                        LEFT JOIN UNITIZADOR U ON U.COD_UNITIZADOR = E.COD_UNITIZADOR
                        LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                        LEFT JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                           WHERE E.COD_DEPOSITO_ENDERECO IS NOT NULL AND DE.COD_CARACTERISTICA_ENDERECO <> 37
                            AND (E.DTH_PRIMEIRA_MOVIMENTACAO BETWEEN TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI') AND TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))
                            $sqlWhereDepEnd
                        GROUP BY DE.NUM_RUA, TO_CHAR(E.DTH_PRIMEIRA_MOVIMENTACAO,'DD/MM/YYYY')) HIST

                        ON HIST.NUM_RUA = DE.NUM_RUA
                        $sqlWhere
                        ORDER BY DATA_ESTOQUE, DE.NUM_RUA";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getPickingSemProdutos($params) {
        $SQLWhere = "";
        if ($params['ruaInicial'] != "") {
            $SQLWhere = $SQLWhere . " AND DE.NUM_RUA >= " . $params['ruaInicial'];
        }
        if ($params['ruaFinal'] != "") {
            $SQLWhere = $SQLWhere . " AND DE.NUM_RUA <= " . $params['ruaFinal'];
        }
        if ($params['idAreaArmazenagem'] != "") {
            $SQLWhere = $SQLWhere . " AND DE.COD_AREA_ARMAZENAGEM = " . $params['idAreaArmazenagem'];
        }
        if ($params['idEstruturaArmazenagem'] != "") {
            $SQLWhere = $SQLWhere . " AND DE.COD_TIPO_EST_ARMAZ = " . $params['idEstruturaArmazenagem'];
        }
        if ($params['idTipoEndereco'] != "") {
            $SQLWhere = $SQLWhere . " AND DE.COD_TIPO_ENDERECO = " . $params['idTipoEndereco'];
        }

        $SQL = " SELECT DISTINCT
                        DE.DSC_DEPOSITO_ENDERECO,
                        U.DSC_UNITIZADOR,
                        AM.DSC_AREA_ARMAZENAGEM,
                        TEA.DSC_TIPO_EST_ARMAZ,
                        TE.DSC_TIPO_ENDERECO
                   FROM V_PALETE_DISPONIVEL_PICKING V
                  INNER JOIN (SELECT MAX(TAMANHO_UNITIZADOR) as TAMANHO_UNITIZADOR,
                                         COD_DEPOSITO_ENDERECO
                                FROM V_PALETE_DISPONIVEL_PICKING
                               GROUP BY COD_DEPOSITO_ENDERECO) MAXP
                         ON MAXP.TAMANHO_UNITIZADOR = V.TAMANHO_UNITIZADOR
                        AND MAXP.COD_DEPOSITO_ENDERECO = V.COD_DEPOSITO_ENDERECO
                   LEFT JOIN UNITIZADOR U ON U.COD_UNITIZADOR = V.COD_UNITIZADOR
                   LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = V.COD_DEPOSITO_ENDERECO
                   INNER JOIN TIPO_ENDERECO TE ON (TE.COD_TIPO_ENDERECO = DE.COD_TIPO_ENDERECO)
                   INNER JOIN TIPO_EST_ARMAZ TEA ON (TEA.COD_TIPO_EST_ARMAZ = DE.COD_TIPO_EST_ARMAZ)
                   INNER JOIN AREA_ARMAZENAGEM AM ON (AM.COD_AREA_ARMAZENAGEM = DE.COD_AREA_ARMAZENAGEM)
                   WHERE 1 = 1";

        $SQLOrder = " ORDER BY DE.DSC_DEPOSITO_ENDERECO";
        $result = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLOrder)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getPickingMultiplosProdutos($params) {

        $SQLWhere = "";
        if (isset($params['ruaInicial']) && !empty($params['ruaInicial'])) {
            $SQLWhere = $SQLWhere . " AND DE.NUM_RUA >= " . $params['ruaInicial'];
        }
        if (isset($params['ruaFinal']) && !empty($params['ruaFinal'])) {
            $SQLWhere = $SQLWhere . " AND DE.NUM_RUA <= " . $params['ruaFinal'];
        }
        $SQL = "
                SELECT DISTINCT P.COD_PRODUTO COD_PRODUTO,
                                P.DSC_PRODUTO PRODUTO,
                                P.DSC_GRADE GRADE,
                                TDE.DESCRICAO,
                                TDE.QTD QTD
                FROM PRODUTO P
                LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE
                LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                INNER JOIN (SELECT COUNT(*) AS QTD,
                                   DE.DSC_DEPOSITO_ENDERECO as DESCRICAO,
                                   DE.COD_DEPOSITO_ENDERECO
                            FROM (SELECT DISTINCT P.COD_PRODUTO,
                                                  P.DSC_GRADE,
                                                  NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO) AS COD_DEPOSITO_ENDERECO
                                  FROM PRODUTO P
                                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                                  LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                                  WHERE PE.COD_DEPOSITO_ENDERECO IS NOT NULL OR PV.COD_DEPOSITO_ENDERECO IS NOT NULL) E
                            LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                            GROUP BY DE.DSC_DEPOSITO_ENDERECO, DE.COD_DEPOSITO_ENDERECO
                            HAVING COUNT (*) > 1
                            ORDER BY DSC_DEPOSITO_ENDERECO) TDE
                      ON TDE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR TDE.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = TDE.COD_DEPOSITO_ENDERECO
                WHERE (PE.COD_DEPOSITO_ENDERECO IS NOT NULL OR PV.COD_DEPOSITO_ENDERECO IS NOT NULL) $SQLWhere
                ORDER BY TDE.DESCRICAO, P.DSC_PRODUTO";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEnderecosByParam($params) {
        $query = "
           SELECT DISTINCT(DEP.COD_DEPOSITO_ENDERECO) CODIGO, DEP.NUM_RUA RUA, DEP.NUM_PREDIO PREDIO, DEP.NUM_APARTAMENTO APARTAMENTO,
                  DEP.NUM_NIVEL NIVEL, DEP.DSC_DEPOSITO_ENDERECO ENDERECO
           FROM DEPOSITO_ENDERECO DEP
           LEFT JOIN PRODUTO_EMBALAGEM PE ON DEP.COD_DEPOSITO_ENDERECO =  PE.COD_DEPOSITO_ENDERECO
           LEFT JOIN PRODUTO_VOLUME PV ON DEP.COD_DEPOSITO_ENDERECO =  PV.COD_DEPOSITO_ENDERECO
           --LEFT JOIN PRODUTO P ON PE.COD_PRODUTO = P.COD_PRODUTO OR PV.COD_PRODUTO = P.COD_PRODUTO
           WHERE 1 = 1 AND DEP.COD_CARACTERISTICA_ENDERECO = $params[tipoEndereco]
        ";

        if (!empty($params['ruaInicial'])) {
            $query = $query . " AND DEP.NUM_RUA >= " . $params['ruaInicial'];
        }
        if (!empty($params['predioInicial'])) {
            $query = $query . " AND DEP.NUM_PREDIO >= " . $params['predioInicial'];
        }
        if (isset($params['nivelInicial']) && $params['nivelInicial'] != '') {
            $query = $query . " AND DEP.NUM_NIVEL >= " . $params['nivelInicial'];
        }
        if (!empty($params['aptoInicial'])) {
            $query = $query . " AND DEP.NUM_APARTAMENTO >= " . $params['aptoInicial'];
        }

        if (!empty($params['ruaFinal'])) {
            $query = $query . " AND DEP.NUM_RUA <= " . $params['ruaFinal'];
        }
        if (!empty($params['predioFinal'])) {
            $query = $query . " AND DEP.NUM_PREDIO <= " . $params['predioFinal'];
        }
        if (isset($params['nivelFinal']) && $params['nivelFinal'] != '') {
            $query = $query . " AND DEP.NUM_NIVEL <= " . $params['nivelFinal'];
        }
        if (!empty($params['aptoFinal'])) {
            $query = $query . " AND DEP.NUM_APARTAMENTO <= " . $params['aptoFinal'];
        }

        if (!empty($params['bloqueada'])) {
            $entrada = null;
            $saida = null;
            switch ($params['bloqueada']) {
                case "E":
                    $entrada = true;
                    $saida = false;
                    break;
                case "S":
                    $saida = true;
                    $entrada = false;
                    break;
                case "ES":
                    $entrada = true;
                    $saida = true;
                    break;
                case "N":
                    $entrada = false;
                    $saida = false;
                    break;
            }
            if (!is_null($entrada))
                $query = $query . " AND DEP.BLOQUEADA_ENTRADA = " . (int)$entrada;

            if (!is_null($saida))
                $query = $query . " AND DEP.BLOQUEADA_SAIDA = " . (int)$saida;
        }

        if (!empty($params['status']) && $params['status'] != '') {
            $query = $query . " AND DEP.IND_STATUS = '" . $params['status'] . "'";
        }
        if (isset($params['ativo']) && $params['ativo'] != '') {
            $query = $query . " AND DEP.IND_ATIVO = '" . $params['ativo'] . "'";
        }

        if (!empty($params['lado'])) {
            if ($params['lado'] == "P")
                $query = $query . " AND MOD(NUM_PREDIO,2) = 0";
            if ($params['lado'] == "I")
                $query = $query . " AND MOD(NUM_PREDIO,2) = 1";
        }

        if ($params['opcao'] == 'sem') {
            $query .= ' AND DEP.COD_DEPOSITO_ENDERECO NOT IN (SELECT COD_DEPOSITO_ENDERECO FROM PRODUTO_EMBALAGEM)';
            $query .= ' AND DEP.COD_DEPOSITO_ENDERECO NOT IN (SELECT COD_DEPOSITO_ENDERECO FROM PRODUTO_VOLUME)';
        } elseif ($params['opcao'] == 'com') {
            $query .= ' AND (DEP.COD_DEPOSITO_ENDERECO IN (SELECT COD_DEPOSITO_ENDERECO FROM PRODUTO_EMBALAGEM)';
            $query .= ' OR DEP.COD_DEPOSITO_ENDERECO IN (SELECT COD_DEPOSITO_ENDERECO FROM PRODUTO_VOLUME))';
        }

        $query = $query . " ORDER BY RUA, PREDIO, NIVEL, APARTAMENTO";

        $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getImprimirEndereco($enderecos) {
        $query = "
          SELECT DEP.DSC_DEPOSITO_ENDERECO DESCRICAO
          FROM DEPOSITO_ENDERECO DEP
          WHERE DEP.COD_DEPOSITO_ENDERECO in ($enderecos)
          ORDER BY DEP.NUM_RUA ASC, DEP.NUM_PREDIO ASC, DEP.NUM_APARTAMENTO ASC, DEP.NUM_NIVEL ASC ";

        $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function verificaBloqueioInventario($idDepositoEndereco) {
        if (!isset($idDepositoEndereco) || empty($idDepositoEndereco)) {
            throw new \Exception('E necessario informar idDepositoEndereco');
        }

        $depositoEnderecoEn = $this->find($idDepositoEndereco);
        if ($idDepositoEndereco != null) {
            if ($depositoEnderecoEn->getInventarioBloqueado() == 'S') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $idInventario
     * @throws \Doctrine\DBAL\DBALException
     */
    public function desbloquearByInventario($idInventario)
    {
        $sql = "UPDATE DEPOSITO_ENDERECO SET IND_INVENTARIO_BLOQUEADO = 'N' WHERE COD_DEPOSITO_ENDERECO IN (
                    SELECT COD_DEPOSITO_ENDERECO FROM INVENTARIO_ENDERECO_NOVO WHERE COD_INVENTARIO = $idInventario
                )";
        $this->getEntityManager()->getConnection()->query($sql)->execute();
    }


    public function getEnderecosPorProduto($codbarras) {

        $SQL = "SELECT * 
                  FROM PRODUTO P 
                  LEFT JOIN PRODUTO_EMbALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE AND PE.DTH_INATIVACAO IS NULL
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE AND PV.DTH_INATIVACAO IS NULL
            WHERE PE.COD_BARRAS = '$codbarras' OR PV.COD_BARRAS = '$codbarras'";
        $array = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($array) == 0) {
            throw new \Exception("Nenhum produto encontrado com o código de barras: " . $codbarras);
        }

        $SQL = "     SELECT P.COD_PRODUTO,
                            P.DSC_GRADE,
                            P.DSC_PRODUTO,
                            NVL(PV.DSC_VOLUME, PE.DSC_EMBALAGEM) as VOLUME,
                            PK.DSC_DEPOSITO_ENDERECO as ENDERECO,
                            CE.DSC_CARACTERISTICA_ENDERECO as TIPO,
                            RES.QTD_RESERVADA as QTD_RESERVADA,
                            NVL(E.QTD,0) as QTD,
                            RES.RESERVAS,
                            P.IND_CONTROLA_LOTE
                       FROM PRODUTO P
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                 INNER JOIN DEPOSITO_ENDERECO PK ON PK.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR PK.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                  LEFT JOIN CARACTERISTICA_ENDERECO CE ON CE.COD_CARACTERISTICA_ENDERECO = PK.COD_CARACTERISTICA_ENDERECO
                  LEFT JOIN (SELECT LISTAGG (RE.COD_RESERVA_ESTOQUE,',') WITHIN GROUP (ORDER BY RE.COD_RESERVA_ESTOQUE) RESERVAS,
                                    SUM(REP.QTD_RESERVADA) QTD_RESERVADA,
                                    RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                               FROM RESERVA_ESTOQUE RE
                              INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                              INNER JOIN RESERVA_ESTOQUE_ENDERECAMENTO REE ON REE.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                              WHERE RE.IND_ATENDIDA = 'N' AND RE.TIPO_RESERVA = 'E'
                              GROUP BY RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0)) RES
                         ON RES.COD_DEPOSITO_ENDERECO = PK.COD_DEPOSITO_ENDERECO
                        AND RES.COD_PRODUTO = P.COD_PRODUTO
                        AND RES.DSC_GRADE = P.DSC_GRADE
                        AND RES.VOLUME = NVL(PV.COD_PRODUTO_VOLUME,0)
                  LEFT JOIN ESTOQUE E
                         ON E.COD_PRODUTO = P.COD_PRODUTO
                        AND E.DSC_GRADE = P.DSC_GRADE
                        AND NVL(E.COD_PRODUTO_VOLUME,0) = NVL(PV.COD_PRODUTO_VOLUME,0)
                        AND E.COD_DEPOSITO_ENDERECO = PK.COD_DEPOSITO_ENDERECO
                      WHERE PE.COD_BARRAS = '$codbarras' OR PV.COD_BARRAS = '$codbarras'
                UNION
                SELECT P.COD_PRODUTO,
                       P.DSC_GRADE,
                       P.DSC_PRODUTO,
                       NVL(PV.DSC_VOLUME, PE.DSC_EMBALAGEM) as VOLUME,
                       DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                       CE.DSC_CARACTERISTICA_ENDERECO as TIPO,
                       0 as QTD_RESERVADA,
                       E.QTD as QTD,
                       NULL as RESERVAS,
                       P.IND_CONTROLA_LOTE
                       FROM PRODUTO P
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN ESTOQUE E
                         ON E.COD_PRODUTO = P.COD_PRODUTO
                        AND E.DSC_GRADE = P.DSC_GRADE
                        AND NVL(E.COD_PRODUTO_VOLUME,0) = NVL(PV.COD_PRODUTO_VOLUME,0)
                        AND E.COD_DEPOSITO_ENDERECO <> NVL(PE.COD_DEPOSITO_ENDERECO,0)
                        AND E.COD_DEPOSITO_ENDERECO <> NVL(PV.COD_DEPOSITO_ENDERECO,0)
                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  LEFT JOIN CARACTERISTICA_ENDERECO CE ON CE.COD_CARACTERISTICA_ENDERECO = DE.COD_CARACTERISTICA_ENDERECO
                      WHERE PE.COD_BARRAS = '$codbarras' OR PV.COD_BARRAS = '$codbarras'";

        $array = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        //var_dump($array);
        return $array;

    }

    public function getProdutoPorEndereco($endereco)
    {

        $enderecoEn = $this->findOneBy(array('descricao' => $endereco));

        $usaGrade = ($this->getSystemParameterValue("UTILIZA_GRADE") == "S");

        if (!isset($enderecoEn) || empty($enderecoEn)) {
            throw new \Exception("Endereço não encontrado");
        } else {
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
            $result = $vetEmbalagens = array();
            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
            $itens = $estoqueRepo->findBy(array('depositoEndereco' => $enderecoEn));
            $itensPickingEmb = $embalagemRepo->findBy(array('endereco' => $enderecoEn->getId()), array('codProduto' => 'ASC'));
            $itensPickingVol = $volumeRepo->findBy(array('endereco' => $enderecoEn->getId()), array('codProduto' => 'ASC'));
            if (!empty($itensPickingEmb)) {
                /**
                 * @var int $key
                 * @var Produto\Embalagem $itemPinckingEmb
                 */
                foreach ($itensPickingEmb as $key => $itemPinckingEmb) {
                    $temEstoque = false;
                    $produtoEn = $itemPinckingEmb->getProduto();
                    foreach ($itens as $item) {
                        if ($item->getProduto() == $produtoEn) $temEstoque = true;
                    }
                    if ($temEstoque) continue;
                    $produto = array(
                        'produto' => $produtoEn->getId(),
                        'desc' => (!$usaGrade) ? $produtoEn->getDescricao() : $produtoEn->getDescricao() . " ( " .$produtoEn->getGrade() . " ) ",
                        'qtd' => 0);
                    $result[$produtoEn->getId() . "---" . $produtoEn->getGrade()] = $produto;
                }
            }

            if (!empty($itens)) {
                /**
                 * @var int $key
                 * @var Estoque $item
                 */
                foreach ($itens as $key => $item) {
                    $produtoEn = $item->getProduto();
                    $dataValidade = !is_null($item->getValidade()) ? $item->getValidade()->format('d/m/Y') : null;
                    if ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_UNITARIO) {
                        $vetEmbalagens = $embalagemRepo->getQtdEmbalagensProduto($produtoEn->getId(), $produtoEn->getGrade(), $item->getQtd());
                        $produto = array('produto' => $produtoEn->getId(),
                            'desc' => (!$usaGrade) ? $produtoEn->getDescricao() : $produtoEn->getDescricao() . " ( " .$produtoEn->getGrade() . " ) ",
                            'qtd' => implode(' + ', $vetEmbalagens),
                            'dataValidade' => $dataValidade, 'lote' => $item->getLote());
                        $result[$produtoEn->getId() . "---" . $produtoEn->getGrade() . "---" .$item->getLote()] = $produto;
                    } elseif ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO) {
                        /** @var Produto\Volume $volumeEn */
                        $volumeEn = $item->getProdutoVolume();
                        $result[$produtoEn->getId() . "---" . $produtoEn->getGrade()."-".$volumeEn->getId(). "---" .$item->getLote()] = array('produto' => $produtoEn->getId(),
                            'desc' => (!$usaGrade) ? $produtoEn->getDescricao() . " - (" . $volumeEn->getDescricao() . ")" :
                                $produtoEn->getDescricao() . " - (" . $volumeEn->getDescricao() . ") ( " .$produtoEn->getGrade() . " ) ",
                            'qtd' => $item->getQtd(),
                            'dataValidade' => $dataValidade, 'lote' => $item->getLote());
                    }
                }
            }
            if (empty($result)) {
                throw new \Exception("Não existe produto nesse endereço");
            }
        }
        return $result;
    }

    public function validaInativacaoEndereco ($idEndereco) {

        if ($this->enderecoOcupado($idEndereco)) {
            throw new \Exception("Endereço não pode ser inativado pois ainda contem produtos armazenados");
        }

        $SQL = " SELECT * 
                   FROM RESERVA_ESTOQUE RE 
                  WHERE RE.IND_ATENDIDA = 'N'
                    AND RE.COD_DEPOSITO_ENDERECO = '" . $idEndereco . "'";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            throw new \Exception("Endereço não pode ser inativado pois ainda contem reservas de estoque com movimentações pendentes");
        }

        return true;
    }

    public function validaEnderecosComReservas(array $arrIds)
    {
        $strIds = implode(", ", $arrIds);
        $sql = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO 
                FROM DEPOSITO_ENDERECO DE 
                INNER JOIN RESERVA_ESTOQUE RE on DE.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO AND RE.IND_ATENDIDA = 'N'
                WHERE DE.COD_DEPOSITO_ENDERECO IN ($strIds)";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function validaEnderecosPicking(array $arrIds)
    {
        $strIds = implode(", ", $arrIds);
        $sql = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO 
                FROM PRODUTO P 
                LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO and P.DSC_GRADE = PE.DSC_GRADE AND PE.COD_DEPOSITO_ENDERECO IS NOT NULL
                LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO and P.DSC_GRADE = PV.DSC_GRADE AND PV.COD_DEPOSITO_ENDERECO IS NOT NULL
                INNER JOIN DEPOSITO_ENDERECO DE ON PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO OR PV.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                WHERE DE.COD_DEPOSITO_ENDERECO IN ($strIds)";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function getProdutosPicking($idEndereco)
    {
        $sql = "SELECT DISTINCT 
                    P.COD_PRODUTO,
                    P.DSC_GRADE,
                    P.DSC_PRODUTO,
                    NVL(0, PV.COD_NORMA_PALETIZACAO) AS ID_NORMA
                FROM DEPOSITO_ENDERECO DE
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO AND PE.DTH_INATIVACAO IS NULL
                LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO AND PV.DTH_INATIVACAO IS NULL
                INNER JOIN PRODUTO P ON P.COD_PRODUTO = NVL(PE.COD_PRODUTO, PV.COD_PRODUTO) AND P.DSC_GRADE = NVL(PE.DSC_GRADE, PV.DSC_GRADE)
                WHERE DE.COD_DEPOSITO_ENDERECO = $idEndereco AND NVL(PE.COD_PRODUTO, PV.COD_PRODUTO) IS NOT NULL";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function verificaAlocacaoPickingDinamico($enderecoAntigoEn, $enderecoNovoEn, $embVolEn){

        $idCaracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::PICKING;
        $idCaracteristicaPickingRotativo = \Wms\Domain\Entity\Deposito\Endereco::PICKING_DINAMICO;

        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

        if ($embVolEn == null) {
            throw new \Exception("Nenhuma embalagem nem volume informados");
        }

        $tipo = "U";
        if ($embVolEn->getProduto()->getTipoComercializacao()->getId() != 1) $tipo = "V";
        $produtoEn = $embVolEn->getProduto();

        /*
         * COMENTANDO REGRA DE TRANSFERENCIA PICKING -> PICKING
         *
        if ($enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
            if ($tipo == "U") {
                $embalagens = $embalagemRepo->findBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade()));
                foreach ($embalagens as $embalagemEn) {
                    $embalagemEn->setEndereco($enderecoNovoEn);
                    $this->getEntityManager()->persist($embalagemEn);
                }
                $this->getEntityManager()->flush();
            } else {
                $embVolEn->setEndereco($enderecoNovoEn);
                $this->getEntityManager()->persist($embVolEn);
                $this->getEntityManager()->flush();
            }
        }
        */

        if ($enderecoAntigoEn->getIdCaracteristica() == $idCaracteristicaPicking ||
            $enderecoAntigoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
            if ($enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPicking && $enderecoAntigoEn->getId() != $enderecoNovoEn->getId()) {
                throw new \Exception("Só é permitido transferir de Picking para Picking Dinâmico!");
            }
            if ($enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo && $enderecoNovoEn->liberadoPraSerPicking()) {
                if ($tipo == "U") {
                    $embalagens = $embalagemRepo->findBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade()));
                    foreach ($embalagens as $embalagemEn) {
                        $embalagemEn->setEndereco($enderecoNovoEn);
                        $this->getEntityManager()->persist($embalagemEn);
                    }
                    $this->getEntityManager()->flush();
                } else {
                    $embVolEn->setEndereco($enderecoNovoEn);
                    $this->getEntityManager()->persist($embVolEn);
                    $this->getEntityManager()->flush();
                }
            }
        } else {
            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E O ENDEREÇO DO PRODUTO ESTÁ VAZIO ... E TRAVA
            if ($enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPicking) {
                if ( is_null($embVolEn->getEndereco()) ) {
                    throw new \Exception("Esse Endereço de Picking não está cadastrado para esse produto!");
                }
            }

            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E SE O ENDEREÇO DE DESTINO É DIFERENTE DO ENDEREÇO CADASTRADO NO PRODUTO E EXIBE MENSAGEM DE ERRO
            if (($enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPicking || $enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo)) {
                if (!is_null($embVolEn->getEndereco()) && ($enderecoNovoEn->getId() !== $embVolEn->getEndereco()->getId())) {
                    throw new \Exception("Produto ja cadastrado no Picking " . $embVolEn->getEndereco()->getDescricao() . "!");
                }
            }

            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING DINAMICO E SE O ENDERECO DO PRODUTO ESTÁ VAZIO E SALVA O ENDEREÇO DE DESTINO
            if ($enderecoNovoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo && $enderecoNovoEn->liberadoPraSerPicking()) {
                if (is_null($embVolEn->getEndereco())) {
                    if ($tipo = "U") {
                        $embalagens = $embalagemRepo->findBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade()));
                        foreach ($embalagens as $embalagemEn) {
                            $embalagemEn->setEndereco($enderecoNovoEn);
                            $this->getEntityManager()->persist($embalagemEn);
                        }
                        $this->getEntityManager()->flush();
                    } else {
                        $embVolEn->setEndereco($enderecoNovoEn);
                        $this->getEntityManager()->persist($embVolEn);
                        $this->getEntityManager()->flush();
                    }
                }
            }

        }
    }

}
