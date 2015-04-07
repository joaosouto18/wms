<?php

namespace Wms\Service\Mobile;


class Inventario
{

    protected $_em;

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
            $enderecos[] = $endereco['DSC_DEPOSITO_ENDERECO'];
        }
        return $enderecos;
    }

    public function consultaVinculoEndereco($idInventario, $idEndereco)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->getEm()->getRepository("wms:Inventario\Endereco");
        $inventarioEndEntity = $inventarioEndRepo->findOneBy(array('depositoEndereco' => $idEndereco, 'inventario' => $idInventario));

        if ($inventarioEndEntity == null) {
            $result = array(
                'status' => 'error',
                'msg' => 'Endereço não selecionado para o inventário:'.$idInventario,
                'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario
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

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEm()->getRepository("wms:Produto");
        $info = $produtoRepo->getProdutoByCodBarras($codigoBarras);

        if ($info == NULL) {
            $result = array(
                'status' => 'error',
                'msg' => 'Nenhum produto encontrado para o código de barras ' . $codigoBarras,
                'url' => '/mobile/inventario/consulta-endereco/idInventario/'.$idInventario
            );
            return $result;
        }

        $populateForm['idProduto']          = $info[0]['idProduto'];
        $populateForm['grade']              = $info[0]['grade'];
        $populateForm['idContagemOs']       = $params['idContagemOs'];
        $populateForm['codigoBarras']       = $params['codigoBarras'];
        $populateForm['idInventarioEnd']    = $params['idInventarioEnd'];
        $populateForm['idEndereco']         = $params['idEndereco'];

        if ($info[0]['descricaoVolume'] != null) {
            $populateForm['codProdutoVolume'] = $info[0]['idVolume'];
        } else {
            $populateForm['codProdutoEmbalagem'] = 0;
        }

        $result['populateForm'] = $populateForm;

        return $result;
    }

    public function contagemEndereco($params)
    {
        $qtdConferida           = $params['qtdConferida'];
        $idContagemOs           = $params['idContagemOs'];
        $qtdAvaria              = $params['qtdAvaria'];
        $idInventarioEnd        = $params['idInventarioEnd'];
        $idProduto              = $params['idProduto'];
        $grade                  = $params['grade'];
        $codProdutoEmbalagem    = $params['codProdutoEmbalagem'];
        $codProdutoVolume       = $params['codProdutoVolume'];
        $contagemEndId          = $params['contagemEndId'];
        $numContagem            = $params['numContagem'];
        $divergencia            = $params['divergencia'];

        if ($divergencia == 1) {
            $numContagem++;
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        if (empty($qtdConferida)) {
            $qtdConferida = 0;
        }
        if (empty($contagemEndId)) {
            $contagemEndEn =
                $contagemEndRepo->save(array(
                    'qtd' => $qtdConferida,
                    'idContagemOs' => $idContagemOs,
                    'idInventarioEnd' => $idInventarioEnd,
                    'qtdAvaria' => $qtdAvaria,
                    'codProduto' => $idProduto,
                    'grade' => $grade,
                    'codProdutoEmbalagem' => $codProdutoEmbalagem,
                    'codProdutoVolume' => $codProdutoVolume,
                    'numContagem' => $numContagem
                ));
            $contagemEndId = $contagemEndEn->getId();
        } else {
            $contagemEndEn = $contagemEndRepo->find($contagemEndId);
            $contagemEndEn->setQtdContada($contagemEndEn->getQtdContada()+$qtdConferida);
            $contagemEndEn->setQtdAvaria($contagemEndEn->getQtdAvaria()+$qtdAvaria);
            $this->_em->persist($contagemEndEn);
            $this->_em->flush();
        }


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
                    $this->getEm()->flush();
                    return false;
                }
            } elseif($quantidadeTotal > 0) {
                $inventarioEndEn->setInventariado(null);
                $inventarioEndEn->setDivergencia(1);
                $contagemEndEn->setQtdDivergencia($quantidadeContada);
                $contagemEndEn->setDivergencia(1);
                $this->getEm()->persist($inventarioEndEn);
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
                    if ( ($qtdConferida == $qtdTotal) &&
                        ( ( $contagemEndEn->getCodProdutoEmbalagem() == $codProdutoEmbalagem ) || ($contagemEndEn->getCodProdutoVolume()) == $codProdutoVolume )
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

    public function inventariarEndereco($params)
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
        return $this->getEm()->flush();
    }

    public function deveAtualizarEstoque($params)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $invEndRepo */
        $invEndRepo         = $this->getEm()->getRepository("wms:Inventario\Endereco");
        $inventarioEndEn    = $invEndRepo->find($params['idInventarioEnd']);
        $inventarioEndEn->setAtualizaEstoque(1);
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
            $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'codProdutoEmbalagem' => $codProdutoEmbalagem));
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
            return $contagemEndEntities[0]->getId();
        }
        return false;
    }

    public function removeEnderecoInventario($params)
    {
        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd'], 'numContagem' => null));
        if (count($contagemEndEntities) > 0) {
            foreach($contagemEndEntities as $contagemEndEn) {
                $this->_em->remove($contagemEndEn);
            }
            $this->_em->flush();
            return true;
        }
        return false;
    }

    public function finalizaContagemEndereco($params, $paramsSystem)
    {

        if (empty($params['idInventarioEnd'])) {
            throw new \Exception('idInventarioEnd não pode ser vazio');
        }

        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
        $contagemEndRepo        = $this->getEm()->getRepository("wms:Inventario\ContagemEndereco");
        $contagemEndEntities    = $contagemEndRepo->findBy(array('inventarioEndereco' => $params['idInventarioEnd']));

        if (count($contagemEndEntities) == 0) {
            return false;
        }
        $validaEstoqueAtual = $paramsSystem['validaEstoqueAtual'];
        $regraContagemParam = $paramsSystem['regraContagemParam'];

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
                    $this->inventariarEndereco($params);
                    $result = true;
                } else {
                    $this->deveAtualizarEstoque($params);
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
            $posicaoArray = count($result['contagens']);
            $result[$posicaoArray]['CONTAGEM'] = 2;
            $result[$posicaoArray]['DIVERGENCIA'] = null;
        }

        return $result;
    }


} 