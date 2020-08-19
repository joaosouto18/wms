<?php

namespace Wms\Plugin;

use \Core\Controller\PluginAbstract;
use Wms\Serial;

class Deposito extends PluginAbstract
{

    /**
     * @var array nome dos módulos em que o plugin será executado
     */
    protected $useInModules = array('web', 'expedicao','mobile');

    /**
     * @var array nome dos controllers que este plugin não será executado
     */
    protected $notUseInControllers = array('error', 'auth');
    public $rotasProibidas = array(
        array(
            'controller' => 'usuario',
            'action' => 'mudar-senha-provisoria',
            'module' => 'web',
        ),
        array(
            'controller' => 'auth',
            'action' => 'login',
            'module' => 'web',
        ),
        array(
            'controller' => 'auth',
            'action' => 'logout',
            'module' => 'web',
        ),
        array(
            'controller' => 'auth',
            'action' => 'login',
            'module' => 'mobile',
        ),
        array(
            'controller' => 'auth',
            'action' => 'logout',
            'module' => 'mobile',
        )
    );

    private function verificaData() {
        $file = APPLICATION_PATH . DIRECTORY_SEPARATOR . "data.lk";

        $dataAtual = date("Ymd");

        if (file_exists($file)) {
            $fp = fopen($file,"r");
            $conteudo = fread($fp, filesize($file));
            fclose($fp);
            try {
                if (strlen(trim($conteudo)) <> 8)
                    throw new \Exception("Arquivo data.lk com conteúdo inválido ou corrompido");

                $ultimoDia = substr($conteudo,6,2);
                $ultimoMes = substr($conteudo, 4,2);
                $ultimoAno = substr($conteudo,0 ,4);;

                if (!checkdate($ultimoMes, $ultimoDia, $ultimoAno))
                    throw new \Exception("Arquivo data.lk com conteúdo inválido ou corrompido");


                    $diferenca = intval($dataAtual) - intval($conteudo);
                if ($diferenca <0) {
                    echo "<div>Data do servidor foi retroagida. Favor efetuar a correção da data para que o sistema possa ser usado normalmente</div>";
                    echo "<div>Data Atual: " . date("d/m/Y") . " </div>";
                    echo "<div>Ultima Utilização: " . $ultimoDia . "/".$ultimoMes . "/". $ultimoAno . " </div>";
                    exit;
                }
            } catch (\Exception $e) {
                echo "<div>Falha capturando a data de ultima utilização do sistema</div>";
                echo "<div>" . $e->getMessage() . "</div>";
                exit;
            }
        }

        $fp = fopen($file, "w");
        fwrite($fp, $dataAtual);
        fclose($fp);
    }

    private function VerificaSerial() {
        $config = \Zend_Registry::get('config');

        $key = null;
        $systemTag = $config->system;
        if ($systemTag != null) $key = $config->system->key;

        if ($key == null) {
            echo "Chave de Ativação não localizada";
            exit;
        }

        $serial = new Serial($key);

        if (!$serial->isValid()) {
            echo "Chave de Ativação Inválida";
            exit;
        }

        if ($serial->isExpired() & $serial->expire()) {
            echo "Chave de Ativação Expirada";
            exit;
        }
    }

    /**
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @throws \Exception
     */
    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $this->VerificaSerial();
        $this->verificaData();

        if (!$this->verificaRotas($request))
            return;

        //get view
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer');
        $view = $viewRenderer->view;
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $auth = \Zend_Auth::getInstance();
        $sessao = new \Zend_Session_Namespace('deposito');

        if (isset($sessao->depositosPermitidos) && isset($sessao->idDepositoLogado)) {
            $view->idDepositoLogado = $sessao->idDepositoLogado;
            $view->depositosPermitidos = $sessao->depositosPermitidos;
            $view->centraisPermitidas = $sessao->centraisPermitidas;
            return;
        }

        $usuario = $auth->getIdentity();
        if (!$usuario->isRoot()) {
            $depositosPermitidos = $em->find('wms:Usuario', $usuario->getId())->getDepositos()->toArray();
        }
        else {
            $depositosPermitidos = $em->getRepository(\Wms\Domain\Entity\Deposito::class)->findAll();
        }

        foreach ($depositosPermitidos as $key => $deposito) {
            if ((!$deposito->getFilial()->getIsAtivo()) || (!$deposito->getIsAtivo()))
                continue;

            $arrayDepositos[$deposito->getId()] = $deposito->getDescricao();
            $centrais[] = $deposito->getFilial()->getCodExterno();
        }

        if (!count($arrayDepositos)) {
            $request->setControllerName('error');
            $request->setActionName('sem-permissao-depositos');
            return;
        }

        $sessao->idDepositoLogado = key($arrayDepositos);

        $view->idDepositoLogado = $sessao->idDepositoLogado;
        $view->depositosPermitidos = $arrayDepositos;
        $sessao->depositosPermitidos = $arrayDepositos;
        $view->centraisPermitidas = $centrais;
        $sessao->centraisPermitidas = $centrais;

        //verifica se já tem algum depósito selecionado
        if ($sessao->idDepositoLogado == null && $request->getModuleName() != 'mobile') {
            $request->setControllerName('error');
            $request->setActionName('sem-deposito-logado');
            $sessao->codFilialExterno = '';
        } else {
            /** @var \Wms\Domain\Entity\Deposito $depositoLogado */
            $depositoLogado = $em->find('wms:Deposito', $sessao->idDepositoLogado);
            $sessao->codFilialExterno = $depositoLogado->getFilial()->getCodExterno();
        }
    }

}