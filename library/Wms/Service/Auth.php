<?php

namespace Wms\Service;

use Wms\Controller\Action;
use Wms\Domain\Entity\Usuario;

/**
 * Description of Auth
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Desenvolvimento
 */
class Auth {

    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private static function getEm()
    {
        return \Zend_Registry::get('doctrine')->getEntityManager();
    }

    /**
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function login($username, $password)
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        // adapter
        $adapter = new \Core\Auth\Adapter\Doctrine2($em, 'wms:Usuario', 'login', 'senha');
        $adapter->setIdentity($username)
                ->setCredential($password);

        // now try and authenticate....	
        $result = \Zend_Auth::getInstance()->authenticate($adapter);

        if (!$result->isValid()) {
            // temporary redirection
            throw new \Exception(implode(' ', $result->getMessages()));
        }
        
        $auth = \Zend_Auth::getInstance();
        $identity = $auth->getIdentity();

        // get user's infos
        $usuarioDb = $em->createQueryBuilder()
                        ->select('u, p')
                        ->from('wms:Usuario', 'u')
                        ->innerJoin('u.pessoa', 'p')
                        ->where("u.login = '{$identity}'")
                        ->getQuery()->getOneOrNullResult();

        $usuario = $em->find('wms:Usuario', $usuarioDb->getPessoa()->getId());

        // relacao de perfis
        $perfis = array();
        foreach ($usuario->getPerfis() as $perfil) {
            $perfis[$perfil->getId()] = $perfil->getNome();
        }
        //ordeno crescentemente pelos codigos do perfil 
        ksort($perfis);
        //gero o perfil
        $perfil = implode('-', $perfis);
        //seto o RoleId para o zf
        $usuario->setRoleId($perfil);

        /** @var \Zend_Auth_Storage_Session $storage */
        $storage = $auth->getStorage();
        $storage->clear();
        $storage->write($usuario);
        
        /* Tempo permitido de inatividade na sessão */
        $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => 'TEMPO_INATIVIDADE'));
        $tempo = (!empty($parametro)) ? $parametro->getValor() : 60;//Se não encontrar o tempo no registro vai se adotar como padrão 60 min
        $session = new \Zend_Session_Namespace($storage->getNamespace());
        $session->timeout = time() + (60 * $tempo);
        
        return true;
    }

    /**
     * Executa o logout do usuário logado
     */
    public static function logout()
    {
        //exclui o deposito logado do usuário
        $sessao = new \Zend_Session_Namespace('deposito');
        unset($sessao->idDepositoLogado);

        //limpar a sessão do usuário
        \Zend_Auth::getInstance()->clearIdentity();
        $cache = \Zend_Cache::factory('Core', 'File');
        $cache->clean();
    }

}