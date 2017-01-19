<?php

namespace Wms\Service\Mobile;


use Core\Grid\Column\Filter\Render\Date;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Module\Web\Form\Deposito\Endereco\Caracteristica;
use Wms\Util\Coletor;

class Inventario
{

    protected $_em;

    public function getSystemParameterValue ($parametro)
    {
        $parametroRepo = $this->getEm()->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => $parametro));

        if ($parametro == NULL) {
            return "";
        } else {
            return $parametro->getValor();
        }

    }

    /**
     * @param mixed $em
     */
    public function setEm($em)
    {
        $this->_em = $em;
    }

    /**
     * @return mixed
     */
    public function getEm()
    {
        return $this->_em;
    }


    public function formProduto($populate = array())
    {
        $formProduto = new \Wms\Module\Mobile\Form\Produto();
        $formProduto->setUrlParams(array('controller' => 'inventario', 'action' => 'consulta-produto'));
        $formProduto->populate($populate);
        return $formProduto;
    }

    public function criarOs($idInventario)
    {
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->getEm()->getRepository('wms:Inventario');
        $idContagemOs = $inventarioRepo->criarOS($idInventario);
        return $idContagemOs;
    }

    public function getEnderecos($idInventario, $numContagem, $divergencia)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $invEndRepo */
        $invEndRepo = $this->getEm()->getRepository('wms:Inventario\Endereco');
        $params['idInventario'] = $idInventario;
        $params['divergencia']  = $divergencia;
        $params['numContagem']  = $numContagem;
        if ($params['numContagem'] == 1 && $params['divergencia'] != 1) {
            $params['numContagem'] = 0;
        }

        $return['enderecos'] = $invEndRepo->getByInventario($params);
        $enderecos = array();
        foreach($return['enderecos'] as $endereco) {
            if ($params['divergencia'] == 1) {
                $enderecos[] = $endereco['DSC_DEPOSITO_ENDERECO'].' - '.$endereco['DSC_PRODUTO'].' - '.$endereco['DSC_GRADE'].' - '.$endereco['COMERCIALIZACAO'];
            } else {
                $enderecos[] = $endereco['DSC_DEPOSITO_ENDERECO'];
            }
        }
        return $enderecos;
    }

    public function consultaVinculoEndereco($idInventario, $idEndereco, $numContagem, $divergencia)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->getEm()->getRepository("wms:Inventario\Endereco");
        $inventarioEndEntity = $inventarioEndRepo->findOneBy(array('depositoEndereco' => $idEndereco, 'inventario' => $idInventario));

        if ($inventarioEndEntity == null) {
            $result = array(
                'status' => 'error',
                'msg' => 'Endereço não selecionado para o inventário:'.$idInventario,
                'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario.'/numContagem/'.$numContagem.'/divergencia/'.$divergencia
            );
            return $result;
        }

        $result['idInventarioEnd'] = $inventarioEndEntity->getId();

        return $result;
    }

    public function consultaOseEnd($idContagemOs, $idInventarioEnd, $idInventario, $recontagemMesmoUsuario)
    {
        //Permite a recontagem pelo mesmo usuário?
        if ($recontagemMesmoUsuario == 'N')  {
            /** @var \Wms\Domain\Entity\Inventario\ContagemEndereco $invContagemEndRepo */
            $invContagemEndRepo = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
            $invContagemEndEn = $invContagemEndRepo->findOneBy(array('contagemOs' => $idContagemOs, 'inventarioEndereco' => $idInventarioEnd));
            $result = array();
            if ($invContagemEndEn) {
                $result = array(
                    'status' => 'error',
                    'msg' => 'Endereço já contado pelo usuário logado',
                    'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario
                );
            }
            return $result;
        }
    }

    public function consultarProduto($params)
    {
        $codigoBarras   = $params['codigoBarras'];
        $idInventario   = $params['idInventario'];
        $numContagem    = $params['numContagem'];
        if (isset($params['divergencia'])) {
            $divergencia    = $params['divergencia'];
        }

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEm()->getRepository("wms:Produto");
        $info = $produtoRepo->getProdutoByCodBarras($codigoBarras);

        if ($info == NULL) {
            $paleteRepo = $this->getEm()->getRepository('wms:Enderecamento\Palete');
            $paleteEn = $paleteRepo->find(Coletor::retiraDigitoIdentificador($codigoBarras));
            if ($paleteEn == null) {
                $result = array(
                    'status' => 'error',
                    'msg' => 'Nenhum Produto ou U.M.A encontrado para o código de barras ' . $codigoBarras,
                    'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario.'/numContagem/'.$numContagem.'/divergencia/'.$divergencia
                );
                return $result;
            } else {
                $produtos = $paleteEn->getProdutosArray();
                $idProduto = $produtos[0]['codProduto'];
                $grade = $produtos[0]['grade'];
                $idVolume = $produtos[0]['codProdutoVolume'];
                $dscVolume = null;
                if ($idVolume != null){
                    $dscVolume = "VOLUME";
                }
            }
        } else {
            $idProduto = $info[0]['idProduto'];
            $grade = $info[0]['grade'];
            $idVolume = $info[0]['idVolume'];
            $dscVolume = $info[0]['descricaoVolume'];
        }

        $produtoEn = $produtoRepo->findOneBy(array('id'=>$idProduto,'grade'=>$grade));
        $idEndereco = $params['idEndereco'];
        $enderecoRepo = $this->getEm()->getRepository('wms:Deposito\Endereco');
        $embalagemRepo = $this->getEm()->getRepository('wms:Produto\Embalagem');
        $enderecoEn = $enderecoRepo->find($idEndereco);
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $params['codigoBarras']));

        $idPicking = Endereco::ENDERECO_PICKING;
        $idPickingDinamico = Endereco::ENDERECO_PICKING_DINAMICO;

        $pickingCorreto = true;
        if($enderecoEn->getIdCaracteristica() == $idPickingDinamico ||
           $enderecoEn->getIdCaracteristica() == $idPicking) {
            $pickings = $produtoRepo->getEnderecoPicking($produtoEn,'ID');
            $pickingCorreto = false;
            foreach ($pickings as $pickingId) {
                if ($pickingId == $idEndereco) {
                    $pickingCorreto = true;
                    continue;
                }
            }
        }
        
        $populateForm['pickinCorreto']     = $pickingCorreto;
        $populateForm['idProduto']          = $idProduto;
        $populateForm['grade']              = $grade;
        $populateForm['idContagemOs']       = $params['idContagemOs'];
        $populateForm['codigoBarras']       = $params['codigoBarras'];
        $populateForm['idInventarioEnd']    = $params['idInventarioEnd'];
        $populateForm['idEndereco']         = $params['idEndereco'];
        $populateForm['dscEndereco']        = $enderecoEn->getDescricao();
        $populateForm['descricaoProduto']   = '<b>' . $idProduto . " - " . $produtoEn->getDescricao() . '</b>';
        $populateForm['dscEmbalagem']       = '<b>Embalagem ' . $embalagemEn->getDescricao().' - Fator '.number_format($embalagemEn->getQuantidade(),2,',','') . '</b>';
        if ($dscVolume != null) {
            $populateForm['codProdutoVolume'] = $idVolume;
        } else {
            $populateForm['codProdutoEmbalagem'] = 0;
        }

        $result['populateForm'] = $populateForm;

        return $result;
    }

    public function contagemEndereco($params)
    {
        $qtdConferida = null;
        if (isset($params['qtdConferida']))
            $qtdConferida           = $params['qtdConferida'];
        $idContagemOs = null;
        if (isset($params['idContagemOs']))
            $idContagemOs           = $params['idContagemOs'];
        $qtdAvaria = null;
        if (isset($params['qtdAvaria']))
            $qtdAvaria              = $params['qtdAvaria'];
        $idInventarioEnd = null;
        if (isset($params['idInventarioEnd']))
            $idInventarioEnd        = $params['idInventarioEnd'];
        $idProduto = null;
        if (isset($params['idProduto']))
            $idProduto              = $params['idProduto'];
        $grade = null;
        if (isset($params['grade']))
            $grade                  = $params['grade'];
        $codProdutoEmbalagem = null;
        if (isset($params['codProdutoEmbalagem']))
            $codProdutoEmbalagem    = $params['codProdutoEmbalagem'];
        $codProdutoVolume = null;
        if (isset($params['codProdutoVolume']))
            $codProdutoVolume       = $params['codProdutoVolume'];
        $contagemEndId = null;
        if (isset($params['contagemEndId']))
            $contagemEndId          = $params['contagemEndId'];
        $numContagem = null;
        if (isset($params['numContagem']))
            $numContagem            = $params['numContagem'];

        $produtoEn = $this->getEm()->getRepository('wms:Produto')
            ->findOneBy(array('id'=> $idProduto, 'grade' => $grade));

        $possuiValidade = null;
        if (isset($produtoEn) && !empty($produtoEn))
            $possuiValidade = $produtoEn->getValidade();

        $controleValidade = $this->getSystemParameterValue('CONTROLE_VALIDADE');

        $dataValida = true;
        $validade = null;
        if ($possuiValidade == 'S' && $controleValidade == 'S') {
            if (strlen($params['validade']) < 8) {
                $dataValida = false;
            } else {
                $dia = substr($params['validade'],0,2);
                if ($dia == false) $dataValida = false;
                $mes = substr($params['validade'],3,2);
                if ($mes == false) $dataValida = false;
                $ano = substr($params['validade'],6,2);
                if ($mes == false) $dataValida = false;

                if ($dataValida == true) {
                    $data = $dia . "/" . $mes . "/20" . $ano;
                    if (checkdate($mes,$dia,"20".$ano) == false) $dataValida = false;
                }
            }
            if ($dataValida == false) {
                return array('status' => 'error', 'msg' => 'Informe uma data de validade correta!', 'url' => 'mobile');
            } else {
                $validade = new \Zend_Date($data);
                $validade = $validade->toString('Y-MM-dd');

            }
        }

        $divergencia = null;
        if (isset($params['divergencia'])) {
            $divergencia            = $params['divergencia'];
        }

        if ($divergencia == 1) {
            $numContagem++;
        }

        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $produtoVolumeRepo */
        $produtoVolumeRepo = $this->getEm()->getRepository("wms:Produto\Volume");
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $produtoEmbalagemRepo */
        $produtoEmbalagemRepo = $this->getEm()->getRepository('wms:Produto\Embalagem');
        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        if (empty($qtdConferida)) {
            $qtdConferida = 0;
        }

        if ($codProdutoEmbalagem != null) {
            $codProdutoVolume = null;
            $embalagemEntity = $produtoEmbalagemRepo->findOneBy(array('codigoBarras' => $params['codigoBarras']));
        }

        $embConferidos = array();
        if ($codProdutoEmbalagem == null && $codProdutoVolume == null) {
            //ENDEREÇO VAZIO
            $embalagem = array(
                'idVolume' => null,
                'idEmbalagem' => null);
            $embConferidos[] = $embalagem;
        } else {
            //EXISTE PRODUTO NO ENDEREÇO
            $bipaTodosVolumes = $this->getSystemParameterValue('CONFERE_TODOS_VOLUMES');
            if (($bipaTodosVolumes == "S") || ($codProdutoEmbalagem != null)) {
                //FOI BIPADO UMA EMBALAGEM OU DEVE SER BIPADO CADA VOLUME DO PRODUTO
                $embalagem = array(
                    'idVolume' => $codProdutoVolume,
                    'idEmbalagem' =>$codProdutoEmbalagem);
                $embConferidos[] = $embalagem;
            } else {
                //BIPOU UM VOLUME E O SISTEMA ESTA PARAMETRIZADO PARA OA BIPAR O VOLUME, BIPAR AUTOMATICAMENTE TODOS OS VOLUMES DO ENDEREÇO
                $volumeEn = $produtoVolumeRepo->find($codProdutoVolume);
                $volumes = $produtoVolumeRepo->findBy(array('normaPaletizacao'=>$volumeEn->getNormaPaletizacao()));
                foreach ($volumes as $volumeEn) {
                    $embalagem = array(
                        'idVolume' => $volumeEn->getId(),
                        'idEmbalagem' => null);
                    $embConferidos[] = $embalagem;
                }
            }
        }


        foreach ($embConferidos as $embalagem){
            $idEmbalagem = null;
            $idVolume = null;
            if (isset($embalagem['idEmbalagem'])) $idEmbalagem = $embalagem['idEmbalagem'];
            if (isset($embalagem['idVolume'])) $idVolume = $embalagem['idVolume'];

            if (isset($embalagemEntity) && !empty($embalagemEntity)) {
                $qtdConferida = $qtdConferida * $embalagemEntity->getQuantidade();
            }

            $contagemEndEn = $contagemEndRepo->findOneBy(array(
                'contagemOs' =>$idContagemOs,
                'inventarioEndereco' => $idInventarioEnd,
                'codProduto'=>$idProduto,
                'grade' =>$grade,
                'codProdutoEmbalagem'=>$idEmbalagem,
                'codProdutoVolume'=>$idVolume,
                'numContagem'=>$numContagem));
            if ($contagemEndEn != null) {
                $contagemEndEn->setQtdContada($contagemEndEn->getQtdContada() + $qtdConferida);
                $contagemEndEn->setQtdAvaria($contagemEndEn->getQtdAvaria() + $qtdAvaria);
                $contagemEndEn->setValidade($validade);
                $this->_em->persist($contagemEndEn);
                $contagemEndId = $contagemEndEn->getId();
            } else {
                $contagemEndEn = $contagemEndRepo->save(array(
                    'qtd' => $qtdConferida,
                    'idContagemOs' => $idContagemOs,
                    'idInventarioEnd' => $idInventarioEnd,
                    'qtdAvaria' => $qtdAvaria,
                    'codProduto' => $idProduto,
                    'grade' => $grade,
                    'codProdutoEmbalagem' => $idEmbalagem,
                    'codProdutoVolume' => $idVolume,
                    'numContagem' => $numContagem,
                    'validade' => $validade
                ));
                $contagemEndId = $contagemEndEn->getId();
            }
        }
        $this->_em->flush();
        return array('contagemEndId' => $contagemEndId);
    }


    public function validaEstoqueAtual($params, $parametroSistema)
    {
        if ($parametroSistema == 'S') {

            if (empty($params['contagemEndId'])) {
                throw new \Exception('contagemEndId não pode ser vazio');
            }

            /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
            $contagemEndRepo    = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
            $contagemEndEn      = $contagemEndRepo->find($params['contagemEndId']);
            $quantidadeContada  = $contagemEndEn->getQtdContada();
            $quantidadeAvaria   = $contagemEndEn->getQtdAvaria();
            $inventarioEndEn    = $contagemEndEn->getInventarioEndereco();
            $idDepositoEndereco = $inventarioEndEn->getDepositoEndereco()->getId();
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo    = $this->getEm()->getRepository("wms:Enderecamento\Estoque");

            if ($contagemEndEn->getCodProdutoVolume() != null) {
                $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'produtoVolume' => $contagemEndEn->getCodProdutoVolume()));
            } elseif($contagemEndEn->getCodProduto() != null) {
                $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'codProduto' => $contagemEndEn->getCodProduto(), 'grade' => $contagemEndEn->getGrade()));
            } else {
                $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco));
            }

            $quantidadeTotal = ($quantidadeContada+$quantidadeAvaria);
            if ($estoqueEn) {
                //Houve divergência?
                $quantidadeEstoque = $estoqueEn->getQtd();
                if ($quantidadeEstoque != $quantidadeTotal || ($this->compareProduto($estoqueEn, $contagemEndEn) == false) ) {
                    $inventarioEndEn->setInventariado(null);
                    $inventarioEndEn->setDivergencia(1);
                    $contagemEndEn->setQtdDivergencia($quantidadeContada-$quantidadeEstoque);
                    $contagemEndEn->setDivergencia(1);
                    $this->getEm()->persist($inventarioEndEn);
                    $this->getEm()->persist($contagemEndEn);
                    $this->getEm()->flush();
                    return false;
                }
            } elseif($quantidadeTotal > 0) {
                $inventarioEndEn->setInventariado(null);
                $inventarioEndEn->setDivergencia(1);
                $contagemEndEn->setQtdDivergencia($quantidadeContada);
                $contagemEndEn->setDivergencia(1);
                $this->getEm()->persist($inventarioEndEn);
                $this->getEm()->persist($contagemEndEn);
                $this->getEm()->flush();
                return false;
            }

        }
        $this->retiraDivergenciaContagemProduto($params);
        return true;
    }

    /**
     * @param $estoqueEn
     * @param $contagemEndEn
     * @return bool | true se igual | false se diferente
     */
    public function compareProduto($estoqueEn, $contagemEndEn)
    {
        if (($estoqueEn->getCodProduto() == $contagemEndEn->getCodProduto()) &&  ($estoqueEn->getGrade() == $contagemEndEn->getGrade())) {

            if (($estoqueEn->getProdutoEmbalagem() == null)  && ($estoqueEn->getProdutoVolume() != null)) {
                if ($estoqueEn->getProdutoVolume()->getId() == $contagemEndEn->getCodProdutoVolume()) {
                    return true;
                }
            } else {
                return true;
            }

        }
        return false;
    }

    public function novaRegraContagem($params, $parametroSistema){

        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        $idInventarioEndereco =  $params['idInventarioEnd'];
        $qtdContagensIguais = $parametroSistema;
        $SQL = " SELECT MAX(QTD_IGUAL), 
                        COD_PRODUTO,
                        DSC_GRADE,
                        COD_PRODUTO_VOLUME,
                        COD_PRODUTO_EMBALAGEM
                   FROM (SELECT COUNT(COD_INV_CONT_END) as QTD_IGUAL,
                                QTD_CONTADA,
                                COD_PRODUTO,
                                DSC_GRADE,
                                COD_PRODUTO_VOLUME,
                                COD_PRODUTO_EMBALAGEM
                           FROM INVENTARIO_CONTAGEM_ENDERECO 
                          WHERE COD_INVENTARIO_ENDERECO = $idInventarioEndereco
                          GROUP BY QTD_CONTADA,
                                   COD_PRODUTO,
                                   DSC_GRADE,
                                   COD_PRODUTO_VOLUME,
                                   COD_PRODUTO_EMBALAGEM) C
                 HAVING MAX(QTD_IGUAL) < $qtdContagensIguais
                  GROUP BY COD_PRODUTO,
                           DSC_GRADE,
                           COD_PRODUTO_VOLUME,
                           COD_PRODUTO_EMBALAGEM
          ";
        $records =  $this->getEm()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($records) <=0) {
            $this->retiraDivergenciaContagemProduto($params);
            return true;
        }
        return false;
    }

    public function regraContagem($params, $parametroSistema, $estoqueValidado)
    {
        $qtdConferida           = $params['qtdConferida'];
        $contagemEndId          = $params['contagemEndId'];
        $codProduto             = $params['idProduto'];
        $grade                  = $params['grade'];
        $codProdutoEmbalagem    = $params['codProdutoEmbalagem'];
        $codProdutoVolume       = $params['codProdutoVolume'];
        $idInventarioEnd        = $params['idInventarioEnd'];

        if ($codProdutoEmbalagem == null) {
            $codProdutoEmbalagem = '0';
        }

        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }
        if (empty($qtdConferida)) {
            $qtdConferida = 0;
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $idInventarioEnd));

        if ($parametroSistema == '1') {

            if (count($contagemEndEntities) > 0) {
                foreach($contagemEndEntities as $contagemEndEn) {
                    if ($contagemEndEn->getId() == $contagemEndId) {
                        continue;
                    }
                    $qtdTotal = ($contagemEndEn->getQtdContada()+$contagemEndEn->getQtdAvaria());
                    if (($qtdConferida == $qtdTotal) && ($contagemEndEn->getCodProduto() == $codProduto) && ($contagemEndEn->getGrade() == $grade) &&
                        ( ($contagemEndEn->getCodProdutoEmbalagem() == $codProdutoEmbalagem) || ($contagemEndEn->getCodProdutoVolume()) == $codProdutoVolume )
                    ) {
                        $this->retiraDivergenciaContagemProduto($params);
                        return true;
                    }
                }
            }

            return false;
        } else if ($parametroSistema == '2') {

            // caso a primeira contagem seja igual à segunda mas diferente do estoque atual então será necessário uma terceira contagem igual as duas anteriores
            if ($estoqueValidado == false)  {
                $numContagensMinimo = 3;
            } else {
                $numContagensMinimo = 2;
            }
            $numContagemIguais = 0;
            if (count($contagemEndEntities) >= $numContagensMinimo ) {
                foreach($contagemEndEntities as $contagemEndEn) {
                    if ($contagemEndEn->getId() == $contagemEndId) {
                        continue;
                    }
                    $qtdTotal = ($contagemEndEn->getQtdContada()+$contagemEndEn->getQtdAvaria());
                    if (($qtdConferida == $qtdTotal) &&
                        ($contagemEndEn->getCodProduto() == $codProduto) &&
                        ($contagemEndEn->getGrade() == $grade) &&
                        ( ($contagemEndEn->getCodProdutoEmbalagem() == $codProdutoEmbalagem) || ($contagemEndEn->getCodProdutoVolume()) == $codProdutoVolume )
                    ) {
                        $numContagemIguais++;
                    }
                }

                if ($numContagemIguais == $numContagensMinimo-1) {
                    $this->retiraDivergenciaContagemProduto($params);
                    return true;
                }

            }
            return false;
        }
        return true;
    }

    public function inventariarEndereco($params, $contagemEndEntities)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $invEndRepo */
        $invEndRepo         = $this->getEm()->getRepository("wms:Inventario\Endereco");
        $inventarioEndEn    = $invEndRepo->find($params['idInventarioEnd']);

        $inventarioEndEn->setInventariado(1);
        $inventarioEndEn->setDivergencia(null);
        $this->getEm()->persist($inventarioEndEn);

        foreach($contagemEndEntities as $contagemEndEn) {
            $contagemEndEn->setContagemInventariada(1);
            $this->getEm()->persist($contagemEndEn);
        }

        return $this->getEm()->flush();
    }

    public function deveAtualizarEstoque($params, $atualiza)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $invEndRepo */
        $invEndRepo         = $this->getEm()->getRepository("wms:Inventario\Endereco");
        $inventarioEndEn    = $invEndRepo->find($params['idInventarioEnd']);
        $inventarioEndEn->setAtualizaEstoque($atualiza);
        $this->getEm()->persist($inventarioEndEn);
        $this->getEm()->flush();
    }

    public function contagemEndComDivergencia($params)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'divergencia' => 1));
        if (count($contagemEndEntities) > 0) {
            return true;
        }
        return false;
    }

    public function retiraDivergenciaContagemProduto($params)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        $codProdutoEmbalagem    = $params['codProdutoEmbalagem'];
        $codProdutoVolume       = $params['codProdutoVolume'];
        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");

        if ($codProdutoVolume != null) {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'codProdutoVolume' => $codProdutoVolume));
        } elseif($codProdutoEmbalagem != null) {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'codProdutoEmbalagem' => $codProdutoEmbalagem, 'codProduto' => $params['idProduto'], 'grade' => $params['grade']));
        }else {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'codProdutoEmbalagem' => null, 'codProdutoVolume' => null));
        }

        foreach($contagemEndEntities as $contagemEndEn) {
            $contagemEndEn->setDivergencia(null);
            $this->getEm()->persist($contagemEndEn);
        }

        $contagemEndEntitiesZero    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'codProdutoEmbalagem' => null, 'codProdutoVolume' => null));
        if (count($contagemEndEntitiesZero) > 0) {
            foreach($contagemEndEntitiesZero as $contagemEndEn) {
                $contagemEndEn->setDivergencia(null);
                $this->getEm()->persist($contagemEndEn);
            }
        }
        /**
         * Caso tenha duas contagens vazio o endereço esta vazio e se ja tiver alguma contagem de outro produto retirar divergência do mesmo
         */
        if (count($contagemEndEntitiesZero) >= 2) {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd']));
            foreach($contagemEndEntities as $contagemEndEn) {
                $contagemEndEn->setDivergencia(null);
                $this->getEm()->persist($contagemEndEn);
            }
        }

        $this->getEm()->flush();

        return true;
    }

    /**
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function verificaContagemEnd($params)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        $numContagem            = $params['numContagem'];
        $codProdutoVolume       = !empty($params['codProdutoVolume']) ? $params['codProdutoVolume'] : null;
        if (isset($params['codProdutoEmbalagem'])) {
            $codProdutoEmbalagem  = $params['codProdutoEmbalagem'];
        }

        $divergencia            = $params['divergencia'];
        if ($divergencia == 1) {
            $numContagem++;
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");

        if ($codProdutoVolume != null) {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'],
                'codProdutoVolume' => $codProdutoVolume, 'numContagem' => $numContagem)
            );
        } elseif ($codProdutoEmbalagem == 0) {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'],
                'codProdutoEmbalagem' => $codProdutoEmbalagem,
                'numContagem' => $numContagem,
                'codProduto' => $params['idProduto'],
                'grade' => $params['grade']
                )
            );
        } else {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'],
                    'numContagem' => $numContagem,
                    'codProduto' => $params['idProduto'],
                    'grade' => $params['grade']
                )
            );
        }

        if (count($contagemEndEntities) > 0) {
            $result = $this->checaSeInventariado($params, $contagemEndEntities);
            if ($result == false) {
                return $contagemEndEntities[0]->getId();
            }
            return $result;
        }
        return false;
    }

    public function removeEnderecoInventario($params)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo         = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $contagemEndEntities     = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'numContagem' => null));

        if (count($contagemEndEntities) > 0) {
            foreach($contagemEndEntities as $contagemEndEn) {
                $this->_em->remove($contagemEndEn);
            }
            $this->_em->flush();
            return true;
        }
        return false;
    }

    private function acertaContagensProdutosNaoConferidos( $contagemEndEntities, $validaEstoqueAtual) {

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $invEndProdRepo = $this->getEm()->getRepository("wms:Inventario\EnderecoProduto");
        $produtoVolumeRepo = $this->getEm()->getRepository("wms:Produto\Volume");
        $produtoRepo = $this->getEm()->getRepository("wms:Produto");

        $maiorContagem = $contagemEndEntities[count($contagemEndEntities)-1]->getNumContagem();
        $inventarioEnderecoEn = $contagemEndEntities[count($contagemEndEntities)-1]->getInventarioEndereco();
        $idOs = $contagemEndEntities[count($contagemEndEntities)-1]->getContagemOs()->getId();
        $produtosObrigatorios = array();

        /*
         * Monta uma lista com os produtos obrigatórios a serem conferidos,
         * Se a for a primeira contagem então monta a lista a partir do banco de dados (INVENTARIO_ENDERECO_PRODUTO) ou do estoque
         * Se for uma contagem de divergencia, então a lista de produtos obrigatórios são os produtos divergentes.
         * Assim obriga a bipar todos os produtos para poder finalizar a contagem
         */

        $alterou = false;
        if ($maiorContagem == 1) {
            $produtosInventariar = $invEndProdRepo->findBy(array('inventarioEndereco'=> $inventarioEnderecoEn));
            if (count($produtosInventariar) == 0) {
                if ($validaEstoqueAtual == "S" ) {
                    /*
                     * verifica se todos os produtos do estoque foram conferidos
                     * Pego o código de barras, embalagens e volumes a partir do que esta no estoque
                     */
                    $estoqueEntities = $this->getEm()->getRepository('wms:Enderecamento\Estoque')
                        ->findBy(array('depositoEndereco' => $contagemEndEntities[0]->getInventarioEndereco()->getDepositoEndereco()));
                    foreach ($estoqueEntities as $estoqueEn) {
                        $idEmbalagem = null;
                        $idVolume = null;
                        $codBarras = "";

                        if ($estoqueEn->getProdutoEmbalagem() != null) {
                            $idEmbalagem = $estoqueEn->getProdutoEmbalagem()->getId();
                            $codBarras = $estoqueEn->getProdutoEmbalagem()->getCodigoBarras();
                        }

                        if ($estoqueEn->getProdutoVolume() != null) {
                            $idVolume = $estoqueEn->getProdutoVolume()->getId();
                            $codBarras = $estoqueEn->getProdutoVolume()->getCodigoBarras();
                        }

                        $produtosObrigatorios[$estoqueEn->getCodProduto()][$estoqueEn->getGrade()] = array(
                            'codBarras' => $codBarras,
                            'codProdutoEmbalagem' => $idEmbalagem,
                            'codProdutoVolume'=> $idVolume
                        );
                    }
                }
            } else {
                /*
                 * Pego o código de barras, embalagens e volumes a partir do que esta no cadastro do produto
                 */
                foreach ($produtosInventariar as $produto) {
                    /** @var \Wms\Domain\Entity\Produto $produtoEn */
                    $produtoEn = $produto->getProduto();
                    $volumes = $produtoEn->getVolumes();
                    $embalagens = $produtoEn->getEmbalagens();
                    if (count($volumes) >0) {
                        foreach ($volumes as $volumeEn) {
                            $produtosObrigatorios[$produto->getCodProduto()][$produto->getProduto()->getGrade()] = array(
                                'codBarras' => $volumeEn->getCodigoBarras(),
                                'codProdutoEmbalagem' => null,
                                'codProdutoVolume'=> $volumeEn->getId()
                            );
                        }
                    } else {
                        if (count($embalagens) >0) {
                            $menorEmbalagem = $embalagens[0];
                            foreach ($embalagens as $embalagemEn) {
                                if ($embalagemEn->getQuantidade() < $menorEmbalagem->getQuantidade()) {
                                    $menorEmbalagem = $embalagemEn;
                                }
                            }
                            $produtosObrigatorios[$produto->getCodProduto()][$produto->getProduto()->getGrade()] = array(
                                'codBarras' => $menorEmbalagem->getCodigoBarras(),
                                'codProdutoEmbalagem' => $menorEmbalagem->getId(),
                                'codProdutoVolume'=> null
                            );
                        }
                    }
                }
            }
        } else {
            foreach ($contagemEndEntities as $contagemEn) {
                /*
                 * Pego o código de barras, embalagens e volumes a partir dos produtos que tiveram divergencia
                 */
                if ($contagemEn->getDivergencia() == true) {
                    $idEmbalagem = $contagemEn->getCodProdutoEmbalagem();
                    $idVolume = $contagemEn->getCodProdutoVolume();
                    $codBarras = "";
                    if ($idEmbalagem != null) {
                        $embalagens = $contagemEn->getProduto()->getEmbalagens();
                        $menorEmbalagem = $embalagens[0];
                        foreach ($embalagens as $embalagemEn) {
                            if ($embalagemEn->getQuantidade() < $menorEmbalagem->getQuantidade()) {
                                $menorEmbalagem = $embalagemEn;
                            }
                        }
                        $codBarras = $menorEmbalagem->getCodigoBarras();
                    }
                    if ($idVolume != null) {
                        $codBarras = $produtoVolumeRepo->findOneBy(array('id'=>$idVolume))->getCodigoBarras();
                    }

                    if (!isset($produtosObrigatorios[$contagemEn->getCodProduto()][$contagemEn->getGrade()])) {
                        $produtosObrigatorios[$contagemEn->getCodProduto()][$contagemEn->getGrade()] = array(
                            'codBarras' => $codBarras,
                            'codProdutoEmbalagem' => $idEmbalagem,
                            'codProdutoVolume'=> $idVolume
                        );
                    }
                }
            }
        }

        /*Verifica se todos os produtos obrigados a bipar foram realmente bipados na maior contagem*/
        foreach( $produtosObrigatorios as $codProduto => $produto){
            foreach($produto as $grade => $values){
                $encontrouProduto = false;
                $idVolume = $values['codProdutoVolume'];
                $codBarras = $values['codBarras'];
                if ($idVolume == null) {
                    $idEmbalagem = 0;
                } else {
                    $idEmbalagem = null;
                }

                foreach ($contagemEndEntities as $contagemEn) {
                 if (($contagemEn->getNumContagem() == $maiorContagem) && ($contagemEn->getCodProduto() == $codProduto) && ($contagemEn->getGrade() == $grade)) {
                        if ((($contagemEn->getCodProdutoVolume == null) && ($idVolume == null)) || ($contagemEn->getCodProdutoVolume == $idVolume)) {
                            $encontrouProduto = true;
                            break;
                        }
                    }
                }

                /*
                 * Se for a primeira Contagem e não encontrou o produto da lista dos produtos obrigatorios,
                 * então gera uma contagem com a qtd = 0 para que seja gerada uma divergencia.
                 * Agora se for a contagem de divergencia, então obriga que o produto seja confirmado com a quantidade 0
                 */
                if ($encontrouProduto == false) {
                    if ($maiorContagem == 1) {
                        $contagemEndRepo->save(array(
                            'qtd' => 0,
                            'idContagemOs' => $idOs,
                            'idInventarioEnd' => $inventarioEnderecoEn->getId(),
                            'qtdAvaria' => 0,
                            'codProduto' => $codProduto,
                            'grade' => $grade,
                            'codProdutoEmbalagem' => $idEmbalagem,
                            'codProdutoVolume' => $idVolume,
                            'numContagem' => 1,
                            'validade' => null
                        ));
                        $alterou = true;
                    } else {
                        $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));
                        $dscProduto = $produtoEn->getDescricao();
                        throw new \Exception(("Não foi encontrada a conferencia do item $codProduto/$grade - $dscProduto - codBarras: $codBarras"));
                    }
                }
            }
        }

        /*
         * Se teve alguma alteração das contagens então retorna isso para que as contagens possam ser consultadas novamente
         */
        if ($alterou == true) {
            $this->_em->flush();
        }

        return $alterou;
    }

    public function finalizaContagemEndereco($params, $paramsSystem)
    {

        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd']), array('numContagem' => 'ASC'));

        if (count($contagemEndEntities) == 0) {
            return false;
        }

        $validaEstoqueAtual = $paramsSystem['validaEstoqueAtual'];
        $regraContagemParam = $paramsSystem['regraContagemParam'];

        $teveAlteracao = $this->acertaContagensProdutosNaoConferidos($contagemEndEntities, $validaEstoqueAtual);
        if ($teveAlteracao == true) {
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd']), array('numContagem' => 'ASC'));
        }

        foreach($contagemEndEntities as $contagemEndEn) {

            $params['contagemEndId']        = $contagemEndEn->getId();
            $params['qtdConferida']         = $contagemEndEn->getQtdContada() + $contagemEndEn->getQtdAvaria();
            $params['idProduto']            = $contagemEndEn->getCodProduto();
            $params['grade']                = $contagemEndEn->getGrade();
            $params['codProdutoEmbalagem']  = $contagemEndEn->getCodProdutoEmbalagem();
            $params['codProdutoVolume']     = $contagemEndEn->getCodProdutoVolume();

            $estoqueValidado    = $this->validaEstoqueAtual($params, $validaEstoqueAtual);

            $regraContagem      = $this->regraContagem($params, $regraContagemParam, $estoqueValidado);

            $contagemEndComDivergencia = $this->contagemEndComDivergencia($params);

            if (false == $regraContagem && $regraContagemParam == '2') {
                //não passou na regra de contagem
            }
            else {

                if ((true == $estoqueValidado || true == $regraContagem) && (false == $contagemEndComDivergencia)) {
                    //Estoque validado, endereço considerado inventariado
                    $this->inventariarEndereco($params, $contagemEndEntities);
                    $this->deveAtualizarEstoque($params, true);
                    $result = true;
                } else {
                    $this->deveAtualizarEstoque($params, false);
                    $result = false;
                }
            }
        }
        $this->_em->flush();
        return $result;

    }

    public function getContagens($params)
    {
        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo   = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $result            = $contagemEndRepo->getContagens($params);

        if ($params['regraContagem'] == 2) {
            $posicaoArray = count($result);
            $result[$posicaoArray]['CONTAGEM'] = 2;
            $result[$posicaoArray]['DIVERGENCIA'] = null;
        }

        return $result;
    }

    public function checaSeInventariado($params, $contagemEndEntities = null)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->getEm()->getRepository("wms:Inventario\Endereco");
        $inventarioEndEntity = $inventarioEndRepo->find($params['idInventarioEnd']);

        $result = null;
        if ($inventarioEndEntity != null) {

            if ($contagemEndEntities == null) {
                if ($inventarioEndEntity->getInventariado() == 1) {
                    $result = array(
                        'status' => 'error',
                        'msg' => 'Endereço já invetariado, não é permitido zera-lo',
                        'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$params['idInventario'].'/numContagem/'.$params['numContagem'].'/divergencia/'.$params['divergencia']
                    );
                    return $result;
                }
            } else {
                if ($contagemEndEntities[0]->getContagemInventariada() == 1) {
                    $result = array(
                        'status' => 'error',
                        'msg' => 'Endereço já invetariado com o produto informado',
                        'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$params['idInventario'].'/numContagem/'.$params['numContagem'].'/divergencia/'.$params['divergencia']
                    );
                    return $result;
                }
            }

        }

        return $result;
    }

}