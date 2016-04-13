<?php

namespace Mobile\Service;

class Enderecamento
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function validarEndereco($paleteEn, $LeituraColetor, $paleteRepo)
    {
        $endereco   = $LeituraColetor->retiraDigitoIdentificador($this->_getParam("endereco"));

        if (!isset($endereco)) {
            $this->createXml('error','Nenhum Endereço Informado');
        }
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
        $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($endereco);
        if (empty($idEndereco)) {
            $this->createXml('error','Endereço não encontrado');
        }
        $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $enderecoRepo->find($idEndereco);

        if ($enderecoEn->getNivel() == '0') {
            $elementos = array();
            $elementos[] = array('name' => 'nivelzero', 'value' => true);
            $elementos[] = array('name' => 'rua', 'value' => $enderecoEn->getRua());
            $elementos[] = array('name' => 'predio', 'value' => $enderecoEn->getPredio());
            $elementos[] = array('name' => 'apartamento', 'value' => $enderecoEn->getApartamento());
            $elementos[] = array('name' => 'uma', 'value' => $paleteEn->getId());
            $this->createXml('info','Escolha um nível',null, $elementos);
        }

        if ($enderecoEn->getIdEstruturaArmazenagem() == Wms\Domain\Entity\Armazenagem\Estrutura\Tipo::BLOCADO) {
            $paleteRepo->alocaEnderecoPaleteByBlocado($paleteEn->getId(), $idEndereco);
        } else {
            $enderecoReservado = $paleteEn->getDepositoEndereco();

            if (($enderecoReservado == NULL) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
                $this->enderecar($enderecoEn,$paleteEn,$enderecoRepo, $paleteRepo);
            } else {
                $this->createXml('info','Confirmar novo endereço','/mobile/enderecamento/confirmar-novo-endereco/uma/' . $paleteEn->getId() . '/endereco/' . $idEndereco);
            }
        }
    }


} 