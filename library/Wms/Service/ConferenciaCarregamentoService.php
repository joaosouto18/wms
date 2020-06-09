<?php
namespace Wms\Service;

use mysql_xdevapi\Exception;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\ConfCarregOs;
use Wms\Domain\Entity\Expedicao\ConferenciaCarregamento;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\OrdemServicoRepository;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;
use Wms\Domain\Entity\Usuario;

class ConferenciaCarregamentoService extends AbstractService
{
    /**
     * @param $params array
     * @return ConferenciaCarregamento
     * @throws \Exception
     */
    public function registrarNovaConferencia($params)
    {
        try {
            $this->em->beginTransaction();

            $confCarregRepo = $this->em->getRepository(ConferenciaCarregamento::class);
            $confCarregRepo->verifyConditionNewConfCarreg($params);

            $args = [
                'expedicao' => $this->em->getReference(Expedicao::class, $params['codExpedicao']),
                'tipoConferencia' => $params['tipoConferencia'],
                'usuarioAbertura' => $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId())
            ];

            /** @var ConferenciaCarregamento $confCarreg */
            $confCarreg = $confCarregRepo->save($args, false);

            /** @var Expedicao\ConfCarregClienteRepository $confClienteRepo */
            $confClienteRepo = $this->em->getRepository(Expedicao\ConfCarregCliente::class);

            foreach ($params['clientes'] as $cliente) {
                $newClienteConfCarreg = [
                    'conferenciaCarregamento' => $confCarreg,
                    'cliente' => $this->em->getReference(Cliente::class, $cliente['id'])
                ];
                $confClienteRepo->save($newClienteConfCarreg);
            }

            $this->em->flush();
            $this->em->commit();
            return $confCarreg;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function validaConfCarreg($confCarreg)
    {
        /** @var ConferenciaCarregamento $confCarregEn */
        $confCarregEn = $this->em->find(ConferenciaCarregamento::class, $confCarreg);

        if (empty($confCarregEn)) throw new \Exception("Conferência de carregamento não encontrada", 403);

        if ($confCarregEn->isFinalizada()) throw new \Exception("Esta conferência já foi finalizada", 403);

        if ($confCarregEn->getExpedicao()->getCodStatus() !== Expedicao::STATUS_FINALIZADO)
            throw new \Exception("A expedição desta conferência não está mais em condições de carregamento", 403);
    }

    private function createNewOsConfCarreg($confCarreg, $userId, $executeFlush = false)
    {
        try {
            $confCarregEn = $this->em->find(ConferenciaCarregamento::class, $confCarreg);

            $newOs = $this->em->getRepository(OrdemServico::class)->addNewOs([
                "dataInicial" => new \DateTime(),
                "pessoa" => $this->em->getReference(Usuario::class, $userId)->getPessoa(),
                "atividade" => $this->em->getReference('wms:Atividade', Atividade::CONFERENCIA_CARREGAMENTO),
                "formaConferencia" => 'C',
                "dscObservacao" => "Inclusão de novo usuário na conferência",
                "expedicao" => $confCarregEn->getExpedicao()
            ], $executeFlush);

            return $this->em->getRepository(ConfCarregOs::class)->save([
                'conferenciaCarregamento' => $this->em->getReference(ConferenciaCarregamento::class, $confCarreg),
                'ordemServico' => $newOs
            ], $executeFlush);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $confCarreg
     * @param bool $cine
     * @return ConfCarregOs
     * @throws \Exception
     */
    private function getOsConfCarreg($confCarreg, $cine = false, $allUsers = false)
    {
        try{
            $userId = ($allUsers)? null : \Zend_Auth::getInstance()->getIdentity()->getId();

            $osConfCarreg = $this->em->getRepository(ConfCarregOs::class)->getOsConf($confCarreg, $userId);

            if (!$allUsers) {
                if (!empty($osConfCarreg)) {
                    $osConfCarreg = $osConfCarreg[0];
                }
                elseif (empty($osConfCarreg) && $cine) {
                    $osConfCarreg = self::createNewOsConfCarreg($confCarreg, $userId, false);
                } elseif (empty($osConfCarreg) && !$cine) {
                    throw new \Exception("Nenhuma ordem de serviço aberta foi encontrada para este usuário");
                }
            }

            return $osConfCarreg;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function conferirVolume($confCarreg, $codBarras)
    {
        try {
            $this->em->beginTransaction();
            self::validaConfCarreg($confCarreg);

            $confCarregVolRepo = $this->em->getRepository(Expedicao\ConfCarregVolume::class);

            if ($confCarregVolRepo->checkVolumeInvalidoConfCarreg($confCarreg, $codBarras))
                throw new \Exception("Este código de barras $codBarras não pertence à este carregamento!");

            if ($confCarregVolRepo->checkConferido($confCarreg, $codBarras))
                throw new \Exception("Este volume $codBarras já foi conferido!");

            $type = self::getTypeVol($codBarras);

            $osConfCarreg = self::getOsConfCarreg($confCarreg, true);

            $confCarregVol = $confCarregVolRepo->save([
                'confCarregOs' => $osConfCarreg,
                'codVolume' => $codBarras,
                'tipoVolume' => $type,
            ]);

            $confCarregEn = $osConfCarreg->getConferenciaCarregamento();
            if ($confCarregEn->isGerado()) {
                $confCarregEn->setStatus(ConferenciaCarregamento::STATUS_EM_ANDAMENTO);
                $this->em->persist($confCarregEn);
            }

            $this->em->flush();
            $this->em->commit();

            return $confCarregVol;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getTypeVol($volume)
    {
        switch (substr($volume, 0, 2)){
            case Expedicao\EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO:
                $type = Expedicao\ConfCarregVolume::VOL_TIPO_ETIQ_SEP;
                break;
            case Expedicao\EtiquetaSeparacao::PREFIXO_ETIQUETA_EMBALADO:
                $type = Expedicao\ConfCarregVolume::VOL_TIPO_EMBALADO;
                break;
            case Expedicao\EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME:
                $type = Expedicao\ConfCarregVolume::VOL_TIPO_PATRIMONIO;
                break;
            default:
                throw new \Exception("Tipo de volume inválido para conferência");
        }

        return $type;
    }

    public function finalizarOs($confCarreg)
    {
        try {
            $this->em->beginTransaction();
            self::validaConfCarreg($confCarreg);

            if (!$this->em->getRepository(Expedicao\ConfCarregVolume::class)->checkVolumagemConferida($confCarreg))
                throw new \Exception("Existem volumes pendentes de conferência");

            $osConfCarreg = self::getOsConfCarreg($confCarreg, false, true);
            if (!empty($osConfCarreg)) {
                /** @var OrdemServicoRepository $ordemServicoRepo */
                $ordemServicoRepo = $this->em->getRepository(OrdemServico::class);
                foreach ($osConfCarreg as $osConf) {
                    $os = $osConf->getOrdemServico();
                    $ordemServicoRepo->finalizar($os->getId(), 'OS de conferência de carregamento finalizada', $os, false);
                }
            }

            $confCarregEn = $this->em->find(ConferenciaCarregamento::class, $confCarreg);
            if ($confCarregEn->isEmAndamento()) {
                $confCarregEn->setStatus(ConferenciaCarregamento::STATUS_FINALIZADO);
                $this->em->persist($confCarregEn);
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}