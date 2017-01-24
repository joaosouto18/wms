<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Util\Coletor;

class ConferenciaRecebimentoReentregaRepository extends EntityRepository
{

    public function save($data)
    {
        $codBarras = Coletor::adequaCodigoBarras($data['codBarras'],true);

        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $produtoVolumeRepo */
        $produtoVolumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
        $produtoVolumeEn = $produtoVolumeRepo->findOneBy(array('codigoBarras' => $codBarras));

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $produtoEmbalagemRepo */
        $produtoEmbalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $produtoEmbalagemEn = $produtoEmbalagemRepo->findOneBy(array('codigoBarras' => $codBarras));

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $this->getEntityManager()->beginTransaction();
        try {
            $idVolume    = null;
            $idEmbalagem = null;
            if (isset($produtoVolumeEn)) {
                $produtoId = $produtoVolumeEn->getProduto();
                $grade = $produtoVolumeEn->getGrade();
                $idVolume = $produtoVolumeEn->getId();
            } else if (isset($produtoEmbalagemEn)) {
                $produtoId = $produtoEmbalagemEn->getProduto();
                $grade = $produtoEmbalagemEn->getGrade();
                $idEmbalagem = $produtoEmbalagemEn->getId();
            } else {
                throw new \Exception(utf8_encode('Código do Produto não cadastrado!'));
            }

            /** @var \Wms\Domain\Entity\Produto $produtoRepo */
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            $produtoEn = $produtoRepo->findOneBy(array('id' => $produtoId, 'grade' => $grade));

            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
            $recebimentoReentregaEn = $recebimentoReentregaRepo->findOneBy(array('id' => $data['id']));

            /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
            $ordemServicoRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
            $ordemServicoEn = $ordemServicoRepo->findOneBy(array('recebimentoReentrega' => $recebimentoReentregaEn));

            if ($data['modeloSeparacaoFracionado'] == 'E' || $data['modeloSeparacaoNaoFracionado'] == 'E') {
                if (isset($data['etiqueta']) && !empty($data['etiqueta'])) {
                    $etiqueta = Coletor::retiraDigitoIdentificador($data['etiqueta']);
                    $etiquetaEn = $etiquetaRepo ->findOneBy(array('id' => $etiqueta, 'produtoEmbalagem' => $idEmbalagem, 'produtoVolume' => $idVolume));
                    if (!isset($etiquetaEn) || empty($etiquetaEn)) {
                        throw new \Exception(utf8_encode('Código da Etiqueta não confere com Código de Barras do Produto!'));
                    }
                }
            }

            //verifica se o produto existe no recebimento selecionado
            $getProdutosByRecebimento = $recebimentoReentregaRepo->getProdutosByRecebimento($data['id'], $produtoId, $grade);
            if (count($getProdutosByRecebimento) == 0) {
                throw new \Exception(utf8_encode('Produto não encontrado para esse recebimento!'));
            }

            $conferenciaRecebimentoReentregaEn = new ConferenciaRecebimentoReentrega();
            $conferenciaRecebimentoReentregaEn->setProdutoVolume($produtoVolumeEn);
            $conferenciaRecebimentoReentregaEn->setProdutoEmbalagem($produtoEmbalagemEn);
            $conferenciaRecebimentoReentregaEn->setProduto($produtoEn);
            $conferenciaRecebimentoReentregaEn->setGrade($grade);
            $conferenciaRecebimentoReentregaEn->setQuantidadeConferida($data['qtd']);
            $conferenciaRecebimentoReentregaEn->setRecebimentoReentrega($recebimentoReentregaEn);
            $conferenciaRecebimentoReentregaEn->setNumeroConferencia($recebimentoReentregaEn->getNumeroConferencia() + 1);
            $conferenciaRecebimentoReentregaEn->setQtdEmbalagemConferida(1);
            $conferenciaRecebimentoReentregaEn->setOrdemServico($ordemServicoEn);

            $conferenciaRecebimentoReentregaEn->setDataConferencia(new \DateTime);

            $this->_em->persist($conferenciaRecebimentoReentregaEn);
            $this->_em->flush();
            $this->_em->clear();
            $this->getEntityManager()->commit();

            return $produtoEn;
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }
}

