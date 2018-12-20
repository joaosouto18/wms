<?php

namespace Wms\Coletor;

use Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

class Expedicao
{

    protected $_request;

    protected $_expedicaoRepo;

    protected $_sessaoColetor;

    protected $_expedicaoEntity;

    protected $etiquetaSeparacao;

    protected $etiquetaProduto;

    protected $idExpedicao;

    protected $central;

    protected $placa;

    protected $_status;

    protected $_redirect;

    protected $_message;

    protected $_tipoConferencia;


    /**
     * @param mixed $placa
     */
    public function setPlaca($placa)
    {
        $this->placa = $placa;
    }

    /**
     * @return mixed
     */
    public function getPlaca()
    {
        return $this->placa;
    }

    /**
     * @param object $expedicaoEntity
     */
    public function setExpedicaoEntity($expedicaoEntity)
    {
        $this->_expedicaoEntity = $expedicaoEntity;
    }

    /**
     * @return object
     */
    public function getExpedicaoEntity()
    {
        return $this->_expedicaoEntity;
    }

    /**
     * @param mixed $expedicaoRepo
     */
    public function setExpedicaoRepo($expedicaoRepo)
    {
        $this->_expedicaoRepo = $expedicaoRepo;
    }

    /**
     * @return mixed
     */
    public function getExpedicaoRepo()
    {
        return $this->_expedicaoRepo;
    }

    /**
     * @param mixed $redirect
     */
    public function setRedirect($redirect)
    {
        $this->_redirect = $redirect;
    }

    /**
     * @return mixed
     */
    public function getRedirect()
    {
        return $this->_redirect;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->_request = $request;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @param \Zend_Session_Namespace $sessaoColetor
     */
    public function setSessaoColetor($sessaoColetor)
    {
        $this->_sessaoColetor = $sessaoColetor;
    }

    /**
     * @return \Zend_Session_Namespace
     */
    public function getSessaoColetor()
    {
        return $this->_sessaoColetor;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->_status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param mixed $central
     */
    public function setCentral($central)
    {
        $this->central = $central;
    }

    /**
     * @return mixed
     */
    public function getCentral()
    {
        return $this->central;
    }

    /**
     * @param mixed $etiquetaProduto
     */
    public function setEtiquetaProduto($etiquetaProduto)
    {
        $this->etiquetaProduto = $etiquetaProduto;
    }

    /**
     * @return mixed
     */
    public function getEtiquetaProduto()
    {
        return $this->etiquetaProduto;
    }

    /**
     * @param mixed $etiquetaSeparacao
     */
    public function setEtiquetaSeparacao($etiquetaSeparacao)
    {
        $this->etiquetaSeparacao = $etiquetaSeparacao;
    }

    /**
     * @return mixed
     */
    public function getEtiquetaSeparacao()
    {
        return $this->etiquetaSeparacao;
    }

    /**
     * @param mixed $idExpedicao
     */
    public function setIdExpedicao($idExpedicao)
    {
        $this->idExpedicao = $idExpedicao;
    }

    /**
     * @return mixed
     */
    public function getIdExpedicao()
    {
        return $this->idExpedicao;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->_message = $message;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * @param mixed $tipoConferencia
     */
    public function setTipoConferencia($tipoConferencia)
    {
        $this->_tipoConferencia = $tipoConferencia;
    }

    /**
     * @return mixed
     */
    public function getTipoConferencia()
    {
        return $this->_tipoConferencia;
    }


    public function __construct($request, $em)
    {
        $this->setRequest($request);
        $this->setIdExpedicao($request->getParam('idExpedicao'));
        $this->setCentral($request->getParam('idCentral'));
        $this->setPlaca($request->getParam('placa', null));
        $this->setTipoConferencia($request->getParam('tipo-conferencia', null));

        $this->_sessaoColetor = new \Zend_Session_Namespace('coletor');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $this->_expedicaoRepo   = $em->getRepository('wms:Expedicao');
        $this->_expedicaoEntity = $this->getExpedicaoRepo()->find($this->getIdExpedicao());

        if ($this->_expedicaoEntity->getStatus()->getId() == ExpedicaoEntity::STATUS_EM_SEPARACAO) {
            $verificaReconferencia = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();
            if ($verificaReconferencia=='S')
                $this->_expedicaoRepo->alteraStatus($this->_expedicaoEntity, ExpedicaoEntity::STATUS_PRIMEIRA_CONFERENCIA);
            else
                $this->_expedicaoRepo->alteraStatus($this->_expedicaoEntity, ExpedicaoEntity::STATUS_EM_CONFERENCIA);
        }

    }

    public function setLayout()
    {
        $layout = \Zend_Layout::getMvcInstance();
        $layout->setLayout('leitura');
    }


    public function validacaoExpedicao()
    {
        if (!$this->_expedicaoEntity) {

            $this->setMessage('Expedição não encontrada');
            $this->setStatus('error');
            $this->setRedirect('/mobile/ordem-servico/conferencia-expedicao');
            return false;
        }
        if ($this->getExpedicaoEntity()->getStatus()->getID() == ExpedicaoEntity::STATUS_FINALIZADO) {

            $this->setMessage('Expedição já finalizada');
            $this->setStatus('error');
            $this->setRedirect('/mobile/ordem-servico/conferencia-expedicao');
            return false;
        }
        return true;
    }

    public function osLiberada()
    {
        $idExpedicao = $this->getIdExpedicao();
        $placa       = $this->getPlaca();

        $osEntity = $this->getExpedicaoRepo()->verificaOSUsuario($idExpedicao);
        if ($osEntity != null) {
            if ($osEntity[0]->getBloqueio() != null) {

                $this->setMessage('OS bloqueada');
                $this->setStatus('error');
                $this->setRedirect("/mobile/expedicao/liberar-os/idExpedicao/$idExpedicao/placa/$placa");
                return false;
            }
        }

        $resultado = $this->getExpedicaoRepo()->criarOrdemServico($idExpedicao);
        $this->getSessaoColetor()->osID = $resultado['id'];
        return true;
    }

    /**
     * metodo que verifica se a expedição possui embalado e caso possua e o usuário escolha conferir um embalado ou
     * não embalado redireciona para pagina do processo normal
     */
    public function possuiEmbalado()
    {
        $tipoConferencia = $this->getTipoConferencia();
        if ($tipoConferencia == 'embalado' || $tipoConferencia == 'naoembalado' || $tipoConferencia == 'volume') {
            return false;
        } else {
            $nEmbaladosExp = $this->getExpedicaoRepo()->getProdutosEmbalado($this->getIdExpedicao());
            if ($nEmbaladosExp == 0) {
                return false;
            }
        }
        return true;
    }

}