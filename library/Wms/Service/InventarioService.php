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
                    'contagem' => 1
                ]);
                if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
                    $invEndProdRepod->save([
                        'inventarioEndereco' => $inventarioEnderecoEn,
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
            // remover endereço
            if($tipo == 'E') {
                /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
                $inventarioEnderecoRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEnderecoNovo');
                $endereco = $inventarioEnderecoRepo->findOneBy(['inventario' => $id_inventario, 'depositoEndereco' => $id_item]);

                //exclusão lógica
                $endereco->setAtivo(false);

                $this->em->persist($endereco);
                $this->em->flush();
                $this->em->commit();
            }
            //remover produto
            elseif($tipo == 'P'){
                /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository $inventarioEndProdRepo */
                $inventarioEndProdRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEndProd');
                $produto = $inventarioEndProdRepo->findOneBy(['COD_INVENTARIO' => $id_inventario, 'COD_PRODUTO' => $id_item, 'GRADE' => $grade, 'LOTE' => $lote]);

                //exclusão lógica
                $produto->setAtivo(false);

                $this->em->persist($produto);
                $this->em->flush();
                $this->em->commit();
            }
        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn
     * @param bool $divergencia
     * @return InventarioNovo\InventarioContEnd
     * @throws \Exception
     */
    public function addNovaContagem(InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn, $divergencia = false)
    {
        try {
            /** @var InventarioNovo\InventarioContEndRepository $inventContEndRepo */
            $inventContEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEnd");

            $ultimaContagem = $inventContEndRepo->findBy(["inventarioEndereco" => $inventarioEnderecoEn]);

            return $inventContEndRepo->save([
                "inventarioEndereco" => $inventarioEnderecoEn,
                "sequencia" => (count($ultimaContagem) + 1),
                "contagem" => $inventarioEnderecoEn->getContagem(),
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
            /** @var Usuario $usuario */
            $usuario = $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

            $this->getOsUsuarioContagem($usuario, $contagem, $inventario, $tipoConferencia, true);

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
     * @param $usuario
     * @param $contagem
     * @param $inventario
     * @param $tipoConferencia
     * @param $createIfNoExist
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    public function getOsUsuarioContagem($usuario, $contagem, $inventario, $tipoConferencia, $createIfNoExist = false)
    {
        try {
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
    public function addNewOsContagem($contagemEndereco, $usuario, $tipoConferencia)
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
}