<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 27/11/2017
 * Time: 16:03
 */
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Filial;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\NotaFiscal;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Recebimento;
use Wms\Domain\Entity\Usuario;
use Wms\Math;

class ReservaEstoqueProprietarioRepository extends EntityRepository
{
    /**
     * @param $data
     * @param bool $runFlush
     * @return ReservaEstoqueProprietario
     * @throws \Exception
     */
    public function save($data, $runFlush = false)
    {
        try {
            if (!is_a($data['recebimento'], Recebimento::class)) {
                $data['recebimento'] = $this->_em->getReference(Recebimento::class, $data['recebimento']);
            }
            if (!isset($data['produto']) || !is_a($data['produto'], Produto::class)) {
                $data['produto'] = $this->_em->getReference(Produto::class, ["id" => $data['codProduto'], "grade" => $data['grade']]);
            }
            if (!is_a($data['proprietario'], Filial::class)) {
                $data['proprietario'] = $this->_em->getReference(Filial::class, $data['proprietario']);
            }
            if (!is_a($data['notaFiscal'], NotaFiscal::class)) {
                $data['notaFiscal'] = $this->_em->getReference(NotaFiscal::class, $data['notaFiscal']);
            }

            /** @var ReservaEstoqueProprietario $entity */
            $entity = Configurator::configure(new ReservaEstoqueProprietario(), $data);
            $this->_em->persist($entity);

            if ($runFlush) $this->_em->flush($entity);

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $idRecebimento
     * @param bool $runFlush
     * @throws \Exception
     */
    public function criarReservas($idRecebimento, $runFlush = false)
    {
        try {
            $nfRepository = $this->_em->getRepository(NotaFiscal::class);
            /** @var NotaFiscal[] $nfVetEntity */
            $nfVetEntity = $nfRepository->findBy(['recebimento' => $idRecebimento]);
            if(!empty($nfVetEntity)) {
                foreach ($nfVetEntity as $key => $nf) {
                    $itemsNF = $nfRepository->getConferencia($nf->getEmissor()->getId(), $nf->getNumero(), $nf->getSerie(), '', 16);
                    if (!empty($itemsNF)) {
                        foreach ($itemsNF as $item) {
                            self::save([
                                'recebimento' => $nf->getRecebimento(),
                                'notaFiscal' => $nf,
                                'codProduto' => $item['COD_PRODUTO'],
                                'grade' => $item['DSC_GRADE'],
                                'proprietario' => $nf->getCodPessoaProprietario(),
                                'qtd' => $item['QTD_CONFERIDA'],
                            ], $runFlush);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $idRecebimento int
     * @param $runFlush bool
     * @throws \Exception
     */
    private function efetivarReservas($idRecebimento, $runFlush = false)
    {
        try {

            /** @var ReservaEstoqueProprietario[] $reservas */
            $reservas = $this->findBy(['recebimento' => $idRecebimento, 'indAplicado' => 'N']);

            if (empty($reservas)) return;

            /** @var EstoqueProprietarioRepository $estoquePropRepo */
            $estoquePropRepo = $this->_em->getRepository(EstoqueProprietario::class);
            /** @var Usuario $usuarioEfetivacao */
            $usuarioEfetivacao = $this->_em->getReference(Usuario::class, \Zend_Auth::getInstance()->getIdentity()->getId());
            $dataAplicacao = new \DateTime();

            foreach ($reservas as $reserva) {
                $last = $estoquePropRepo->getlastMov($reserva->getCodProduto(), $reserva->getGrade(), $reserva->getProprietario()->getId());
                $saldoAnterior = (!empty($last)) ? $last->getSaldoFinal() : 0;
                $estoquePropRepo->save(
                    $reserva->getCodProduto(),
                    $reserva->getGrade(),
                    $reserva->getQtd(),
                    EstoqueProprietario::RECEBIMENTO,
                    Math::adicionar($saldoAnterior, $reserva->getQtd()),
                    $reserva->getProprietario()->getId(),
                    $reserva->getRecebimento()->getId(),
                    $reserva->getNotaFiscal()->getId()
                );
                $reserva->setIndAplicado(true);
                $reserva->setDthAplicacao($dataAplicacao);
                $reserva->setUsuarioAplicacao($usuarioEfetivacao);
                $this->_em->persist($reserva);

                if ($runFlush) $this->_em->flush($reserva);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $idRecebimento
     * @param $runFlush bool
     * @return bool
     * @throws \Exception
     */
    public function checkLiberacaoReservas($idRecebimento, $runFlush = false)
    {
        try {
            if (empty($this->_em->getRepository(Recebimento::class)->checkRecebimentoEnderecado($idRecebimento))) {
                self::efetivarReservas($idRecebimento, $runFlush);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}