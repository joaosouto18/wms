<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:16
 */

namespace Wms\Service;


use Bisna\Base\Domain\Entity\EntityService;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\InventarioNovo;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\OrdemServicoRepository;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Usuario;
use Wms\Math;

class InventarioService extends AbstractService
{
    /**
     * @param $params array
     * @return object|null
     * @throws \Exception
     */
    public function registrarNovoInventario($params) {

        $this->em->beginTransaction();

        try {
            $args = [
                'descricao' => $params['descricao'],
                'dthCriacao' => new \DateTime(),
                'status' => InventarioNovo::STATUS_GERADO,
                'modeloInventario' => $this->em->getReference('wms:InventarioNovo\ModeloInventario', $params['modelo']['id'])
            ];
            unset($params['modelo']['id']);
            unset($params['modelo']['dscModelo']);
            unset($params['modelo']['dthCriacao']);
            unset($params['modelo']['ativo']);
            unset($params['modelo']['isDefault']);

            $inventarioEn = self::save( array_merge($args, $params['modelo']), false);

            /** @var InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:InventarioNovo\InventarioEnderecoNovo');

            if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
                /** @var InventarioNovo\InventarioEndProdRepository $invEndProdRepod */
                $invEndProdRepod = $this->em->getRepository('wms:InventarioNovo\InventarioEndProd');
            }

            foreach ($params['selecionados'] as $item) {
                $inventarioEnderecoEn = $inventarioEnderecoRepo->save([
                    'inventario' => $inventarioEn,
                    'depositoEndereco' => $this->em->getReference('wms:Deposito\Endereco', $item['id']),
                    'contagem' => 1,
                    'ativo' => 'S'
                ]);
                if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
                    $invEndProdRepod->save([
                        'inventarioEndereco' => $inventarioEnderecoEn,
                        'ativo' => 'S',
                        'produto' => $this->em->getReference('wms:Produto', ['id' => $item['codProduto'], 'grade' => $item['grade']])
                    ]);
                }
            }

            $this->em->flush();
            $this->em->commit();
            return $inventarioEn;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function liberarInventario($id)
    {
        $this->em->beginTransaction();
        try {
            /** @var InventarioNovo $inventarioEn */
            $inventarioEn = $this->find($id);
            if ($inventarioEn->getStatus() != InventarioNovo::STATUS_GERADO) {
                throw new \Exception("O inventário $id está " . InventarioNovo::$tipoStatus[$inventarioEn->getStatus()], 500);
            }

            $impedimentos = $this->getRepository()->findImpedimentosLiberacao($id);
            if (!empty($impedimentos)) {
                return $impedimentos;
            } else {
                $inventarioEn->setStatus(InventarioNovo::STATUS_LIBERADO);
                $inventarioEn->setDthInicio(new \DateTime());

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository('wms:Deposito\Endereco');

                /** @var InventarioNovo\InventarioEnderecoNovo[] $enderecos */
                $enderecos = $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->findBy(["inventario" => $inventarioEn]);

                foreach ($enderecos as $endereco) {
                    $this->addNovaContagem($endereco);
                    $enderecoRepo->bloqueiaOuDesbloqueiaInventario($endereco->getDepositoEndereco(), 'S', false);
                }

                $this->em->persist($inventarioEn);
                $this->em->flush();
                $this->em->commit();
                return true;
            }
        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $id_inventario
     * @param $id_item
     * @param $tipo
     * @param $grade
     * @param $lote
     * @throws \Exception
     */
    public function removerItem($id_inventario, $id_item, $tipo, $grade, $lote)
    {
        $this->em->beginTransaction();

        try {
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEnderecoNovo');
            $endereco = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'depositoEndereco' => $idEndereco]);

            //exclusão lógica
            $endereco->setAtivo('N');

            $this->em->persist($endereco);
            $this->em->flush();
            $this->em->commit();

            // verifica se existe algum endereço ativo ainda no inventario
            $enderecoAtivo = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'ativo' => 'S']);

            if(empty($enderecoAtivo))
                $this->cancelarInventario($idInventario);

        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function removerProduto($idInventario, $idProduto, $grade, $lote){
        $this->em->beginTransaction();

        try {
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository $inventarioEndProdRepo */
            $inventarioEndProdRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEndProd');
            $produto = $inventarioEndProdRepo->findOneBy(['COD_INVENTARIO' => $idInventario, 'COD_PRODUTO' => $idProduto, 'GRADE' => $grade, 'LOTE' => $lote]);

            //exclusão lógica
            $produto->setAtivo(false);

            $this->em->persist($produto);
            $this->em->flush();
            $this->em->commit();

            // verifica se existe algum produto ativo ainda no endereço
            $enderecoAtivo = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'ativo' => 'S']);

            if(empty($enderecoAtivo))
                $this->cancelarInventario($idInventario);

        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }


    public function cancelarEndereco($idInventario, $idEndereco){

        // se o produto for o ultimo, cancela o endereço

    }

    public function cancelarInventario($idInventario){

        // se o endereço for o ultimo, cancela o inventário
        /** @var \Wms\Domain\Entity\InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");
        $inventarioEn   = $inventarioRepo->find($idInventario);

        if ($inventarioEn) {
            $inventarioRepo->cancelar($inventarioEn);
            $inventarioRepo->desbloqueiaEnderecos($idInventario);
            //return $this->redirect('index');
        }
    }

    /**
     * @param InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn
     * @param int $ultimaSequencia
     * @param int $contagem
     * @param bool $divergencia
     * @return InventarioNovo\InventarioContEnd
     * @throws \Exception
     */
    public function addNovaContagem(InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn, $ultimaSequencia = 1, $contagem = 1, $divergencia = false)
    {
        try {
            /** @var InventarioNovo\InventarioContEndRepository $inventContEndRepo */
            $inventContEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEnd");

            return $inventContEndRepo->save([
                "inventarioEndereco" => $inventarioEnderecoEn,
                "sequencia" => ($ultimaSequencia + 1),
                "contagem" => ($contagem + 1),
                "contagemDivergencia" => $divergencia,
                "finalizada" => false
            ], false);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario
     * @param $contagem
     * @param $produto
     * @param $conferencia
     * @throws \Exception
     */
    public function registrarContagem($inventario, $contagem, $produto, $conferencia, $tipoConferencia)
    {
        $this->em->beginTransaction();
        try {

            $this->getOsUsuarioContagem( $contagem, $inventario, $tipoConferencia, true);

            $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd")->save(array(
                "inventarioContEnd" => $this->em->getReference("wms:InventarioNovo\InventarioContEnd", $contagem['id']),
                "produto" => $this->em->getReference("wms:Produto", array("id" => $produto['idProduto'], "grade" => $produto['grade'])),
                "lote" => $conferencia['lote'],
                "qtdContada" => $conferencia['qtd'],
                "produtoEmbalagem" => (!empty($produto['idEmbalagem'])) ? $this->em->getReference("wms:Produto\Embalagem", $produto['idEmbalagem']): null,
                "qtdEmbalagem" => $produto['quantidadeEmbalagem'],
                "codBarras" => $produto['codigoBarras'],
                "produtoVolume" => (!empty($produto->idVolume)) ? $this->em->getReference("wms:Produto\Volume", $produto['idVolume']): null,
                "validade" => (!empty($conferencia['validade'])) ? date_create_from_format("d/m/y", $conferencia['validade']) : null
            ), false);

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $contagem
     * @param $inventario
     * @param $tipoConferencia
     * @param $createIfNoExist
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    public function getOsUsuarioContagem($contagem, $inventario = [], $tipoConferencia = [], $createIfNoExist = false)
    {
        try {
            /** @var Usuario $usuario */
            $usuario = $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

            /** @var InventarioNovo\InventarioContEndOsRepository $contagemEndOsRepo */
            $contagemEndOsRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs");

            /** @var InventarioNovo\InventarioContEndOs $usrContOs */
            $usrContOs = $contagemEndOsRepo->getOsContUsuario( $contagem['id'], $usuario->getId());

            if (empty($usrContOs) && $createIfNoExist) {
                $osContagensAnteriores = $contagemEndOsRepo->getContagensUsuario( $usuario->getId(), $inventario['id']);
                if (!empty($osContagensAnteriores) && $inventario['usuarioNContagens'])
                    throw new \Exception("Este usuário não tem permissão para iniciar uma nova contagem neste endereço");

                $usrContOs = $this->addNewOsContagem($contagem, $usuario, $tipoConferencia);
            }

            return $usrContOs;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contagemEndereco
     * @param $usuario Usuario
     * @param $tipoConferencia
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    private function addNewOsContagem($contagemEndereco, $usuario, $tipoConferencia)
    {
        try {
            /** @var OrdemServico $newOsEn */
            $newOsEn = $this->em->getRepository("wms:OrdemServico")->addNewOs([
                "dataInicial" => new \DateTime(),
                "pessoa" => $usuario->getPessoa(),
                "atividade" => $this->em->getReference('wms:Atividade', Atividade::INVENTARIO),
                "formaConferencia" => $tipoConferencia,
                "dscObservacao" => "Inclusão de novo usuário na contagem"
            ], false);

            return $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs")->save([
                "invContEnd" => $this->em->getReference("wms:InventarioNovo\InventarioContEnd", $contagemEndereco['id']),
                "ordemServico" => $newOsEn
            ], false);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario
     * @param $contagem
     * @throws \Exception
     */
    public function finalizarOs($inventario, $contagem)
    {
        try {
            /** @var OrdemServicoRepository $osRepo */
            $osRepo = $this->em->getRepository("wms:OrdemServico");

            $osUsuarioCont = $this->getOsUsuarioContagem($contagem);

            $osRepo->finalizar($osUsuarioCont->getOrdemServico()->getId(), "Contagem finalizada", $osUsuarioCont->getOrdemServico(), false);

            $outrasOs = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs")
                ->getOutrasOsAbertasContagem($inventario['id'], $osUsuarioCont->getOrdemServico()->getPessoa()->getId(), $contagem['id']);

            if (empty($outrasOs)) {
                $contMaiorAcerto = $this->compararContagens($osUsuarioCont->getInvContEnd(), $inventario);
                $nContagensNecessarias = ($inventario['comparaEstoque'])? $inventario['numContagens'] + 1 : $inventario['numContagens'] ;
                $isDiverg = ($contagem['sequencia'] > $nContagensNecessarias);
                $this->updateFlagContagensProdutos($contagem, $isDiverg);

                if (count($contMaiorAcerto['seq']) < $nContagensNecessarias) {
                    $this->addNovaContagem(
                        $osUsuarioCont->getInvContEnd()->getInventarioEndereco(),
                        $contagem['sequencia'],
                        (!$contagem['divergencia'] && $isDiverg) ? 0 : $contagem['contagem'],
                        $isDiverg
                    );
                } else {
                    $this->finalizarEndereco($osUsuarioCont->getInvContEnd()->getInventarioEndereco());
                }
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contEnd InventarioNovo\InventarioContEnd
     * @param $inventario
     * @return array
     */
    private function compararContagens($contEnd, $inventario)
    {
        $countQtdsIguais = [];

        $validaValidade = ($inventario['controlaValidade'] === InventarioNovo\ModeloInventario::VALIDADE_VALIDA);

        $strConcat = "+=+";

        if ($inventario['comparaEstoque']) {
            /** @var \Wms\Domain\Entity\Enderecamento\Estoque[] $estoque */
            $estoque = $this->em->getRepository("wms:Enderecamento\Estoque")->findBy(["depositoEndereco" => $contEnd->getInventarioEndereco()->getDepositoEndereco()]);
            foreach ($estoque as $produto) {
                $vol = $produto->getProdutoVolume();
                $arg = [
                    $produto->getCodProduto(),
                    $produto->getGrade(),
                    $produto->getLote(),
                    (!empty($vol)) ? $vol->getId() : 1,
                    $produto->getQtd(),
                    (!empty($produto->getValidade()) && $validaValidade) ? $produto->getValidade()->format("d/m/Y") : ""
                ];
                $countQtdsIguais[implode($strConcat, $arg)][] = "estoque";
            }
        }

        foreach ($this->em->getRepository("wms:InventarioNovo\InventarioContEndProd")->getContagensProdutos($contEnd->getInventarioEndereco()->getId()) as $contagem){
            $arg = [
                $contagem['codProduto'],
                $contagem['grade'],
                $contagem['lote'],
                $contagem['idElem'],
                $contagem['qtdContagem'],
                $contagem['validade'],
            ];
            $countQtdsIguais[implode($strConcat, $arg)][] = $contagem['sequencia'];
        }

        $result = [];
        foreach ($countQtdsIguais as $k => $v)
        {
            $exploded = explode($strConcat, $k);
            $result[count($v)] = [
                "seq" => $v,
                "codProduto" => $exploded[0],
                "grade"  => $exploded[1],
                "lote" => $exploded[2],
                "idElem" => $exploded[3],
                "qtdContagem" => $exploded[4],
                "validade" => $exploded[5]
            ];
        }

        ksort($result);
        return array_reverse($result)[0];
    }

    private function updateFlagContagensProdutos($contagem, $resultFlag)
    {

    }

    private function finalizarEndereco()
    {

    }


}