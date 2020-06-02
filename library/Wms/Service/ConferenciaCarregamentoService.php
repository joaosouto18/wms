<?php
namespace Wms\Service;

use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\ConfCarregOs;
use Wms\Domain\Entity\Expedicao\ConferenciaCarregamento;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;

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

            $this->getRepository()->verifyConditionNewConfCarreg($params);

            $args = [
                'expedicao' => $this->em->getReference(Expedicao::class, $params['codExpedicao']),
                'tipoConferencia' => $params['tipoConferencia'],
                'usuarioAbertura' => $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId())
            ];

            /** @var ConferenciaCarregamento $confCarreg */
            $confCarreg = self::save($args, false);

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

    private function createNewOsConfCarreg($confCarreg, $userId, $executeFlush = false)
    {
        try {
            $newOs = $this->em->getRepository(OrdemServico::class)->addNewOs([
                "dataInicial" => new \DateTime(),
                "pessoa" => $this->em->getReference(Pessoa::class, $userId)->getPessoa(),
                "atividade" => $this->em->getReference('wms:Atividade', Atividade::CONFERENCIA_CARREGAMENTO),
                "formaConferencia" => 'C',
                "dscObservacao" => "Inclusão de novo usuário na conferência"
            ], $executeFlush);

            return $this->em->getRepository(ConfCarregOs::class)->save([
                'conferenciaCarregamento' => $this->getReference($confCarreg),
                'os' => $newOs
            ], $executeFlush);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getOsConfCarreg($confCarreg, $cine = false)
    {
        try{
            $userId = \Zend_Auth::getInstance()->getIdentity()->getId();

            $osConfCarreg = $this->em->getRepository(ConfCarregOs::class)->getOsConf($confCarreg, $userId);

            if (empty($osConfCarreg) && $cine) {
                $osConfCarreg = self::createNewOsConfCarreg($confCarreg, $userId, false);
            }

            return $osConfCarreg;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function conferirVolume($confCarreg, $idVol)
    {
        try {
            $this->em->beginTransaction();

            if ($this->em->getRepository(Expedicao\ConfCarregVolume::class)->checkConferido($confCarreg, $idVol))
                throw new \Exception("Este volume já foi conferido!");

            list($type, $className, $strType) = self::getTypeVol($idVol);

            $volumeEn = $this->em->find($className, $idVol);

            if (empty($volumeEn))
                throw new \Exception("O Volume ($strType) $idVol não foi encontrado");

            $osConfCarreg = self::getOsConfCarreg($confCarreg, true);

            $confCarregVol = $this->em->getRepository(Expedicao\ConfCarregVolume::class)->save([
                'confCarregOs' => $osConfCarreg,
                'codVolume' => $idVol,
                'tipoVolume' => $type,
            ]);

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
                $className = Expedicao\EtiquetaSeparacao::class;
                $strType = "Etiqueta Separação";
                break;
            case Expedicao\EtiquetaSeparacao::PREFIXO_ETIQUETA_EMBALADO:
                $type = Expedicao\ConfCarregVolume::VOL_TIPO_EMBALADO;
                $className = Expedicao\MapaSeparacaoEmbalado::class;
                $strType = "Embalado";
                break;
            case Expedicao\EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME:
                $type = Expedicao\ConfCarregVolume::VOL_TIPO_PATRIMONIO;
                $className = Expedicao\VolumePatrimonio::class;
                $strType = "Patrimônio";
                break;
            default:
                throw new \Exception("Tipo de volume inválido para conferência");
        }

        return [$type, $className, $strType];
    }
}