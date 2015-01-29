<?php

namespace Wms\Service;

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
     * @return Doctrine\ORM\EntityManager
     */
    private function getEm()
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
        $em = self::getEm();
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

        $storage = $auth->getStorage();
        $storage->clear();
        $storage->write($usuario);

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