<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;
use Wms\Service\Estoque;
use Wms\Service\Mobile\Inventario as InventarioService;


class InventarioRepository extends EntityRepository
{

    /**
     * @return Inventario
     * @throws \Exception
     */
    public function save()
    {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventario = new Inventario();

            $statusEntity = $em->getReference('wms:Util\Sigla',Inventario::STATUS_GERADO);
            $enInventario->setStatus($statusEntity);
            $enInventario->setDataInicio(new \DateTime);

            $em->persist($enInventario);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $enInventario;
    }


    public function vinculaEnderecos($codEnderecos, $codInventario)
    {
        /** @var \Wms\Domain\Entity\Deposito\Endereco $depositoEnderecoRepo */
        $depositoEnderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $enderecosSalvos = array();
        foreach($codEnderecos as $codEndereco) {
            $enDepositoEnd = $depositoEnderecoRepo->find($codEndereco);
            if (!is_null($enDepositoEnd)) {
                $enderecoEn = $enderecoRepo->findBy(array('inventario' => $codInventario, 'depositoEndereco' => $enDepositoEnd->getId()));
                //não adiciona 2x o mesmo endereço
                if (count($enderecoEn) == 0 && !in_array($codEndereco, $enderecosSalvos)) {
                    $enderecoRepo->save(array('codInventario' => $codInventario, 'codDepositoEndereco' => $codEndereco));
                    $enderecosSalvos[] = $codEndereco;
                }
            }
        }

        if (!is_null($enDepositoEnd)) {
            $this->_em->flush();
        }

    }

    /**
     * @param null $status
     * @return array
     */
    public function getByStatus($status = null)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('i.id, i.dataInicio, i.codStatus')
            ->from('wms:Inventario', 'i')
            ->orderBy("i.id" , "DESC");

        if (is_array($status)) {
            $status = implode(',',$status);
            $source->andWhere("i.status in ($status)");
        }else if ($status) {
            $source->andWhere("i.status = :status")
                ->setParameter('status', $status);
        }

        return $source->getQuery()->getArrayResult();
    }

    public function criarOS($idInventario)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');

        $contagemOsEn = $this->verificaOSUsuario($idInventario);

        if ($contagemOsEn == null) {

            // cria ordem de servico
            $idOrdemServico = $ordemServicoRepo->save(new OrdemServicoEntity, array(
                'identificacao' => array(
                    'tipoOrdem' => 'inventario',
                    'idAtividade' => AtividadeEntity::INVENTARIO,
                    'formaConferencia' => OrdemServicoEntity::COLETOR,
                ),
            ));

            /** @var \Wms\Domain\Entity\Inventario\ContagemOsRepository $contagemOsRepo */
            $contagemOsRepo = $this->getEntityManager()->getRepository('wms:Inventario\ContagemOs');
            $contagemOsEn = $contagemOsRepo->save(array('codInventario' => $idInventario, 'codOs' => $idOrdemServico));
            $idContagemOs = $contagemOsEn->getId();

        } else {
            $idContagemOs = $contagemOsEn[0]['id'];
        }
        return $idContagemOs;
    }

    /**
     * @param $idInventario
     * @return array
     */
    public function verificaOSUsuario($idInventario)
    {
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $source = $this->_em->createQueryBuilder()
            ->select('ios.id')
            ->from('wms:Inventario\ContagemOs', 'ios')
            ->innerJoin('ios.os','os')
            ->where('ios.inventario = :idInventario')
            ->andWhere('os.pessoa = :pessoa')
            ->setParameter('idInventario', $idInventario)
            ->setParameter('pessoa', $idPessoa);

        return $source->getQuery()->getResult();
    }
    /**
     * @param $idInventario
     * @return array
     */
    public function getAvariados($idInventario)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('de.descricao Endereco, ce.codProduto Produto, ce.grade Grade, ce.qtdAvaria Qtde_Avaria')
            ->from('wms:Inventario', 'i')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'i.id = ie.inventario')
            ->innerJoin('wms:Inventario\ContagemEndereco', 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->innerJoin('ie.depositoEndereco','de')
            ->where('i.id = :idInventario')
            ->andWhere('ce.qtdAvaria is not null')
            ->setParameter('idInventario', $idInventario);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $idInventario
     * @return array
     */
    public function getDivergencias($idInventario)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('de.descricao Endereco, ce.codProduto Produto, ce.grade Grade, ce.qtdContada Qtde_Contada, ce.qtdDivergencia Qtde_Divergencia, ce.numContagem, p.descricao')
            ->from('wms:Inventario', 'i')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'i.id = ie.inventario')
            ->innerJoin('wms:Inventario\ContagemEndereco', 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->innerJoin('ce.produto', 'p')
            ->innerJoin('ie.depositoEndereco','de')
            ->where('i.id = :idInventario')
            ->andWhere('ce.divergencia is not null')
            ->orderBy('de.descricao')
            ->setParameter('idInventario', $idInventario);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $inventarioEntity
     * @param $status
     * @return bool
     */
    public function alteraStatus($inventarioEntity, $status)
    {
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);
        $inventarioEntity->setStatus($statusEntity);
        $this->_em->persist($inventarioEntity);
        $this->_em->flush();
        return true;
    }

    public function cancelar($inventarioEntity)
    {
        $this->alteraStatus($inventarioEntity, Inventario::STATUS_CANCELADO);
    }

    public function atualizarEstoque($inventarioEntity)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->_em->getRepository('wms:Enderecamento\Estoque');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $idUsuarioLogado  = \Zend_Auth::getInstance()->getIdentity()->getId();
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $usuarioRepo = $this->_em->getRepository('wms:Usuario');
        $usuarioEn   = $usuarioRepo->find($idUsuarioLogado);

        $serviceInventario = new InventarioService();

        $invEnderecosEn = $enderecoRepo->getComContagem($inventarioEntity->getId());
        foreach($invEnderecosEn as $invEnderecoEn) {

            //ultima contagem
            $contagemEndEnds = $enderecoRepo->getUltimaContagem($invEnderecoEn);

            $enderecoEn         = $invEnderecoEn->getDepositoEndereco();
            $idDepositoEndereco = $enderecoEn->getId();

            foreach($contagemEndEnds as $contagemEndEn) {
                //Endereco tem estoque?

                $osEn           = $contagemEndEn->getContagemOs()->getOs();
                $enderecoVazio  = false;

                if ($contagemEndEn->getCodProdutoVolume() != null) {
                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'produtoVolume' => $contagemEndEn->getCodProdutoVolume()));
                } elseif($contagemEndEn->getCodProdutoEmbalagem() != null) {
                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'codProduto' => $contagemEndEn->getCodProduto(), 'grade' => $contagemEndEn->getGrade()));
                } else {
                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco));
                    $enderecoVazio = true;
                }

                $qtdContagem = ($contagemEndEn->getQtdContada()+$contagemEndEn->getQtdAvaria());
                if ($estoqueEn && $invEnderecoEn->getAtualizaEstoque() == 1) {
                    //mesmo produto?
                    if ($serviceInventario->compareProduto($estoqueEn,$contagemEndEn) == true) {
                        $qtd = $qtdContagem - $estoqueEn->getQtd();
                        if ($qtd != 0) {
                            $this->entradaEstoque($contagemEndEn,$invEnderecoEn,$qtd, $osEn, $usuarioEn, $estoqueRepo);
                        }
                    } else {
                        if ($enderecoVazio) {
                            $qtdRetirar = $estoqueEn->getQtd();
                            $this->retiraEstoque($estoqueEn, $invEnderecoEn, -$qtdRetirar, $osEn, $usuarioEn, $estoqueRepo);
                        } else {
                            $this->retiraEstoque($estoqueEn, $invEnderecoEn, -$qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                            $this->entradaEstoque($contagemEndEn,$invEnderecoEn, $qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                        }
                    }
                } elseif($estoqueEn == null) {
                    if ($qtdContagem != 0) {
                        $this->entradaEstoque($contagemEndEn,$invEnderecoEn,$qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                    }
                }

            }

        }
        $inventarioEntity->setDataFinalizacao(new \DateTime());
        $this->alteraStatus($inventarioEntity,Inventario::STATUS_FINALIZADO);
        $this->_em->persist($inventarioEntity);
        $this->_em->flush();
    }

    public function entradaEstoque($contagemEndEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo)
    {
        $params['contagemEndEn'] = $contagemEndEn;
        $params['produto']       = $contagemEndEn->getProduto();
        $params['endereco']      = $invEnderecoEn->getDepositoEndereco();
        $params['qtd']           = $qtd;
        $params['volume']        = $contagemEndEn->getProdutoVolume();
        $params['embalagem']     = $contagemEndEn->getCodProdutoEmbalagem();
        $params['tipo']          = 'I';
        $params['observacoes']   = 'Mov. correção inventário';
        $params['os']            = $osEn;
        $params['usuario']       = $usuarioEn;
        $params['estoqueRepo']   = $estoqueRepo;

        $serviceEstoque = new Estoque($this->getEntityManager(), $params);
        return $serviceEstoque->movimentaEstoque();
    }

    public function retiraEstoque($estoqueEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo)
    {
        $params['produto']      = $estoqueEn->getProduto();
        $params['endereco']     = $invEnderecoEn->getDepositoEndereco();
        $params['qtd']          = $qtd;
        $params['volume']       = $estoqueEn->getProdutoVolume();
        $params['embalagem']    = 0;
        $params['tipo']         = 'I';
        $params['observacoes']  = 'Mov. correção inventário';
        $params['os']           = $osEn;
        $params['usuario']      = $usuarioEn;
        $params['estoqueRepo']  = $estoqueRepo;

        $serviceEstoque = new Estoque($this->getEntityManager(), $params);
        return $serviceEstoque->movimentaEstoque();
    }

    public function getSumarioByRua($params)
    {
        $idInventario   = $params['id'];

        $sql = "
        SELECT
              F.NUM_RUA RUA,
              COUNT(G.COD_INVENTARIO) QTD_ENDERECOS,
              COUNT(G.DIVERGENCIA) QTD_DIVERGENTE,
              COUNT(G.INVENTARIADO) QTD_INVENTARIADO,
              COUNT(PENDENTES.CONT) as QTD_PENDENTE,
              round( (COUNT(G.INVENTARIADO) * 100) / COUNT(G.COD_INVENTARIO) ) CONCLUIDO
            FROM INVENTARIO_ENDERECO G
            INNER JOIN  DEPOSITO_ENDERECO F  ON F.COD_DEPOSITO_ENDERECO = G.COD_DEPOSITO_ENDERECO
            LEFT JOIN (SELECT IE.COD_INVENTARIO_ENDERECO as CONT, IE.COD_INVENTARIO_ENDERECO FROM INVENTARIO_ENDERECO IE
                  INNER JOIN DEPOSITO_ENDERECO DE ON IE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                  WHERE INVENTARIADO IS NULL AND DIVERGENCIA IS NULL
                  GROUP BY IE.COD_INVENTARIO_ENDERECO) PENDENTES
              ON PENDENTES.COD_INVENTARIO_ENDERECO = G.COD_INVENTARIO_ENDERECO
            WHERE
             G.COD_INVENTARIO = ".$idInventario."
            GROUP BY F.NUM_RUA
            ORDER BY F.NUM_RUA
        ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaReservas($idInventario)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('d.id, re.tipoReserva, re.dataReserva, d.descricao')
            ->from("wms:Ressuprimento\ReservaEstoque","re")
            ->innerJoin('re.endereco', 'd')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'ie.depositoEndereco = d.id')
            ->andWhere("re.atendida = 'N'")
            ->andWhere("ie.inventario = $idInventario")
            ->groupBy('d.id, re.tipoReserva, re.dataReserva, d.descricao');
        return $source->getQuery()->getResult();
    }

    public function removeEnderecos(array $enderecos, $id)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');
        foreach($enderecos as $endereco) {
            $inventarioEndEn = $inventarioEndRepo->findOneBy(array('depositoEndereco' => $endereco, 'inventario' => $id));
            if ($inventarioEndEn) {
                $this->_em->remove($inventarioEndEn);
                $this->_em->flush();
            }
        }
    }

    public function bloqueiaEnderecos($id)
    {
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $inventarioEndsEn  = $inventarioEndRepo->findBy(array('inventario' => $id));
        foreach($inventarioEndsEn as $invEndEn) {
            $enderecoRepo->bloqueiaOuDesbloqueiaInventario($invEndEn->getDepositoEndereco()->getID(),'S');
        }
        $this->_em->flush();
    }

    public function desbloqueiaEnderecos($id)
    {
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $inventarioEndsEn  = $inventarioEndRepo->findBy(array('inventario' => $id));
        foreach($inventarioEndsEn as $invEndEn) {
            $enderecoRepo->bloqueiaOuDesbloqueiaInventario($invEndEn->getDepositoEndereco()->getID(),'N');
        }
        $this->_em->flush();
    }

}