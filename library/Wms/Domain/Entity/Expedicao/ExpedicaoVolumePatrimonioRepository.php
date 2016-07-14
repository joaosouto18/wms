<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use DoctrineExtensions\Versionable\Exception;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class ExpedicaoVolumePatrimonioRepository extends EntityRepository
{

    public function vinculaExpedicaoVolume($volume, $idExpedicao, $idTipoVolume)
    {
        $em = $this->_em;
        $em->beginTransaction();
        try {
            $entityVolumePatrimonio = $this->validarEtiquetaVolume($volume);
            $arrayExpVolPatrimonioEn = $this->findBy(array('volumePatrimonio' => $volume, 'expedicao' => $idExpedicao));

            /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonio $volumePatrimoEn */
            foreach($arrayExpVolPatrimonioEn as $volumePatrimoEn) {
                if ($volumePatrimoEn->getDataFechamento() != NULL) {
                    throw new Exception('Volume '.$volume. ' fechado');
                }
            }

            if (count($arrayExpVolPatrimonioEn) <= 0) {
                if ($entityVolumePatrimonio->getOcupado() == 'S') {
                    throw new Exception('Volume '.$volume. ' esta ocupado');
                }
            }

            $entityVolumePatrimonio->setOcupado('S');
            $em->persist($entityVolumePatrimonio);

            $expedicaoRepo          = $em->getRepository('wms:Expedicao');
            $entityExpedicao        = $expedicaoRepo->findOneBy(array('id' => $idExpedicao));
            $usuarioId = \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);

            $arrayExpVolPatrimonioEn = $this->findBy(array('volumePatrimonio' => $volume, 'expedicao' => $idExpedicao, 'tipoVolume'=>$idTipoVolume));
            /** @var ExpedicaoVolumePatrimonio $ultimoVolume */
            $arrExVol = $this->findBy(array('expedicao' => $idExpedicao), array('sequencia' => "ASC"));
            $nextSeq = 1;
            if (!empty($arrExVol)) {
                $ultimoVolume = end($arrExVol);
                $nextSeq += $ultimoVolume->getSequencia();
            }
            
            if (count($arrayExpVolPatrimonioEn) ==0){
                $enExpVolumePatrimonio = new ExpedicaoVolumePatrimonio();
                $enExpVolumePatrimonio->setVolumePatrimonio($entityVolumePatrimonio);
                $enExpVolumePatrimonio->setExpedicao($entityExpedicao);
                $enExpVolumePatrimonio->setTipoVolume($idTipoVolume);
                $enExpVolumePatrimonio->setSequencia($nextSeq);
                $enExpVolumePatrimonio->setUsuario($usuario);
                $em->persist($enExpVolumePatrimonio);
            }

            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function validarEtiquetaVolume($volume)
    {
        /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonioRepository $volumePatrimonioRepo */
        $volumePatrimonioRepo = $this->_em->getRepository('wms:Expedicao\VolumePatrimonio');
        $entityVolumePatrimonio = $volumePatrimonioRepo->findOneBy(array('id' =>$volume));
        if (is_null($entityVolumePatrimonio)) {
            throw new \Exception('Volume não encontrado');
        }
        return $entityVolumePatrimonio;
    }

    public function confereExpedicaoVolume($volume, $idExpedicao)
    {
        $em = $this->_em;
        $em->beginTransaction();
        try {
            $this->validarEtiquetaVolume($volume);
            $volumesPatrimonio = $this->findBy(array('volumePatrimonio' => $volume, 'expedicao' => $idExpedicao));
            if (count($volumesPatrimonio) == 0) {
                $retorno['msg'] = "Volume $volume não encontrado na expedição: $idExpedicao ";
                $retorno['redirect'] = true;
                return $retorno;
            }

            $expedicaoRepo = $this->_em->getRepository('wms:Expedicao');
            $expedicao = $expedicaoRepo->find($idExpedicao);
            $sessao = new \Zend_Session_Namespace('coletor');

            foreach ($volumesPatrimonio as $volumeCarga) {
                $validaEtiqueta = false;
                $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();
                if ($verificaReconferencia=='S'){

                    $idStatus=$expedicao->getStatus()->getId();
                    /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $EtiquetaConfRepo */
                    $EtiquetaConfRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaConferencia');
                    if ($idStatus==Expedicao::STATUS_SEGUNDA_CONFERENCIA){
                        $etiquetas = $EtiquetaConfRepo->findBy(array('status'=>Expedicao::STATUS_PRIMEIRA_CONFERENCIA,
                                                                     'codExpedicao'=>$idExpedicao,
                                                                     'volumePatrimonio'=>$volume));
                        $statusEntity = $this->_em->getReference('wms:Util\Sigla', EXPEDICAO::STATUS_SEGUNDA_CONFERENCIA);

                        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferencia $etiqueta */
                        foreach ($etiquetas as $etiqueta) {
                            $etiqueta->setStatus($statusEntity);
                            $etiqueta->setCodOsSegundaConferencia($sessao->osID);
                            $etiqueta->setDataReconferencia(new \DateTime());
                            $this->getEntityManager()->persist($etiqueta);
                        }
                        $validaEtiqueta = false;
                    } else {
                        $validaEtiqueta = true;
                    }
                } else {
                    $validaEtiqueta = true;
                }

                if ($validaEtiqueta == true) {
                    if ($volumeCarga->getDataFechamento() == NULL) {
                        throw new \Exception("O Volume $volume ainda está em conferencia na carga " . $volumeCarga->getTipoVolume());
                    }

                    if ($volumeCarga->getDataConferencia() != NULL) {
                        throw new \Exception("O Volume $volume já esta conferido");
                    }

                    $volumeCarga->setDataConferencia(new \DateTime());
                    $em->persist($volumeCarga);
                }
            }
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function confereVolumeRecTransbordo($idExpedicao, $volume)
    {
        $this->validarEtiquetaVolume($volume);
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaSeparacaoRepo->getEtiquetasByExpedicaoAndVolumePatrimonio($idExpedicao, $volume);
        if (count($result) <= 0) {
            throw new \Exception("Volume $volume não encontrado na expedicao $idExpedicao");
        }
        $numProdutosRecebidos = 0;
        foreach($result as $etiqueta) {
            if ($etiqueta->getStatus()->getId() != EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO) {
                $etiquetaSeparacaoRepo->alteraStatus($etiqueta,EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO);
                $numProdutosRecebidos++;
            }
        }

        if ($numProdutosRecebidos == 0) {
            throw new \Exception("Todos os produtos do volume $volume já foram recebidos");
        }

        return $this->_em->flush();
    }

    public function confereVolumeExpTransbordo($idExpedicao, $volume)
    {
        $this->validarEtiquetaVolume($volume);
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaSeparacaoRepo->getEtiquetasByExpedicaoAndVolumePatrimonio($idExpedicao, $volume);
        if (count($result) <= 0) {
            throw new \Exception("Volume $volume não encontrado na expedicao $idExpedicao");
        }
        $numProdutosRecebidos = 0;
        foreach($result as $etiqueta) {
            if ($etiqueta->getStatus()->getId() != EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO) {
                $etiquetaSeparacaoRepo->alteraStatus($etiqueta,EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO);
                $numProdutosRecebidos++;
            }
        }

        if ($numProdutosRecebidos == 0) {
            throw new \Exception("Todos os produtos do volume $volume já foram expedidos");
        }

        return $this->_em->flush();
    }

    public function fecharCaixa($idExpedicao, $volume)
    {
        $em = $this->_em;
        try {
            $this->validarEtiquetaVolume($volume);
            $volumesPatrimonio = $this->findBy(array('volumePatrimonio' => $volume, 'expedicao' => $idExpedicao));
            if (count($volumesPatrimonio) == 0) {
                throw new \Exception('Volume '.$volume.' não encontrado na expedição:'.$idExpedicao. ')');
            }

            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
            $etiquetaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacao");
            $etiquetasEn = $etiquetaRepo->getEtiquetasByExpedicaoAndVolumePatrimonio($idExpedicao,$volume);

            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
            $mapaSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao");
            $qtdMapa = $mapaSeparacaoRepo->getQtdConferidaByVolumePatrimonio($idExpedicao,$volume);

            if ((count($etiquetasEn) == 0) && ($qtdMapa == 0)) {
                $this->desocuparVolume($volume,$idExpedicao);
            } else {
                foreach ($volumesPatrimonio as $carga) {
                    $carga->setDataFechamento(new \DateTime());
                    $em->persist($carga);
                }
            }
            $em->flush();

        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function desocuparVolume($idVolume, $idExpedicao){

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo   = $this->_em->getRepository('wms:Expedicao\Andamento');
        $andamentoRepo->save("Volume Patrimonio $idVolume desocupado. Etiquetas retornadas para o status inicial",$idExpedicao);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetas = $etiquetaRepo->getEtiquetasByExpedicaoAndVolumePatrimonio($idExpedicao,$idVolume);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao $etiquetaEn */
        foreach($etiquetas as $etiquetaEn) {
            $etiquetaEn->setVolumePatrimonio(NULL);
            $etiquetaEn->setCodStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA);
        }

        /** @var \Wms\Domain\Entity\Expedicao\VolumePatrimonio $volumePatrimonioEn */
        $volumePatrimonioEn = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio')->findOneBy(array('id'=>$idVolume));
        $volumePatrimonioEn->setOcupado('N');

        $conferencias = $this->findBy(array('expedicao'=>$idExpedicao,'volumePatrimonio'=>$idVolume));
        foreach ($conferencias as $conferenciaEn) {
            $this->_em->remove($conferenciaEn);
        }

        $this->_em->flush();
    }

    public function getProdutosVolumeByMapa($idExpedicao, $volumePatrimonio)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('msc.codProduto, msc.dscGrade, (msc.qtdEmbalagem * SUM(msc.qtdConferida)) quantidade, p.descricao, evp.sequencia')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoConferencia', 'msc', 'WITH', 'msc.mapaSeparacao = ms.id')
            ->innerJoin('wms:Expedicao\ExpedicaoVolumePatrimonio', 'evp', 'WITH', 'evp.volumePatrimonio = msc.volumePatrimonio AND evp.expedicao = ms.expedicao')
            ->innerJoin("wms:Produto", 'p', 'WITH', 'p.id = msc.codProduto AND p.grade = msc.dscGrade')
            ->where("ms.expedicao = $idExpedicao")
            ->andWhere("msc.volumePatrimonio = $volumePatrimonio")
            ->groupBy("msc.codProduto, msc.dscGrade, p.descricao, evp.sequencia, msc.qtdEmbalagem");

        return $dql->getQuery()->getResult();
    }

    public function vinculaLacre($params)
    {
        $expedicaoVolumePatrimonioEn = $this->getEntityManager()->getReference('wms:Expedicao\ExpedicaoVolumePatrimonio', $params['id']);

        $usuarioId = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);
        if ($expedicaoVolumePatrimonioEn) {
            $observacao = 'Lacre '. $expedicaoVolumePatrimonioEn->getLacre() . ' alterado para lacre '. $params['numeroLacre'];
            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');
            $andamentoRepo->save($observacao, $params['expedicao'], false, true);

            $expedicaoVolumePatrimonioEn->setDataVinculoLacre(new \DateTime());
            $expedicaoVolumePatrimonioEn->setLacre($params['numeroLacre']);
            $expedicaoVolumePatrimonioEn->setUsuarioLacre($usuario);

            $this->getEntityManager()->persist($expedicaoVolumePatrimonioEn);
            $this->getEntityManager()->flush();

            return true;
        }

        return false;

    }

}