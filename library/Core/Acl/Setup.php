<?php

namespace Core\Acl;

use \Zend_Controller_Action_HelperBroker as HelperBroker;

/**
 * Description of Acl
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Desenvolvimento
 */
class Setup
{

    /**
     * @var Zend_Acl
     */
    protected $acl = null;
    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function __construct()
    {
        $this->acl = new \Zend_Acl();
        $this->conn = \Zend_Registry::get('doctrine')->getEntityManager()->getConnection();
        $this->initialize();
    }

    protected function initialize()
    {
        $cache = new \Core\Cache();

        // if the cache don't exist
        if (!$cache->load('acl')) {
            // load information
            $this->setupRoles();
            $this->setupResources();
            $this->setupPrivileges();
            // caching information
            $cache->save($this->acl, 'acl');
        }

        $this->acl = $cache->load('acl');

        $this->saveAcl();

        // if the cache for menu don't exist
        if (!$cache->load('navConfig')) {
            $navConfig = $this->setupNavigation();
            // caching information
            $cache->save($navConfig, 'navConfig');
        } else
            $this->acl = $cache->load('navConfig');
    }

    /**
     * Busta e retorno de Roles do sistema
     * 
     * @return array Matriz de roles
     */
    protected function getRoles()
    {
        $sql = "
            SELECT U.COD_USUARIO, UPU.COD_PERFIL_USUARIO, PU.NOM_PERFIL_USUARIO
            FROM USUARIO U
            INNER JOIN USUARIO_PERFIL_USUARIO UPU ON (UPU.COD_USUARIO = U.COD_USUARIO)
            INNER JOIN PERFIL_USUARIO PU ON (PU.COD_PERFIL_USUARIO = UPU.COD_PERFIL_USUARIO)
            ORDER BY U.COD_USUARIO ASC, UPU.COD_PERFIL_USUARIO ASC";

        $userRoles = $this->conn->query($sql);

        //matriz de arrays
        $roles = array();

        while ($row = $userRoles->fetch()) {
            if (!isset($roles[$row['COD_USUARIO']])) {
                $roles[$row['COD_USUARIO']]['PERFIL'] = $row['NOM_PERFIL_USUARIO'];
                // matriz de cod de perfis
                $roles[$row['COD_USUARIO']]['COD_PERFIL'] = array();
            }
            else
                $roles[$row['COD_USUARIO']]['PERFIL'] .= '-' . $row['NOM_PERFIL_USUARIO'];

            array_push($roles[$row['COD_USUARIO']]['COD_PERFIL'], $row['COD_PERFIL_USUARIO']);
        }

        //matriz de controle de perfis
        $uniqueRoles = array();

        foreach ($roles AS $key => $role) {
            //caso ja exista perfil no array, removo
            if (in_array($role['PERFIL'], $uniqueRoles))
                unset($roles[$key]);
            else
            //adiciono no controlador
                array_push($uniqueRoles, $role['PERFIL']);
        }

        return $roles;
    }

    /**
     * 
     */
    protected function setupRoles()
    {
        $this->acl->addRole(new \Zend_Acl_Role('CONVIDADO'));
        $this->acl->addRole(new \Zend_Acl_Role('LOGADO'), 'CONVIDADO');

        foreach ($this->getRoles() as $role) {
            $this->acl->addRole(new \Zend_Acl_Role($role['PERFIL']), 'LOGADO');
        }
    }

    /**
     * 
     */
    protected function setupResources()
    {
        //recurso padrao de autenticaÃ§Ã£o.
        $this->acl->addResource(new \Zend_Acl_Resource('auth'));
        //recurso padrao de erros.
        $this->acl->addResource(new \Zend_Acl_Resource('error'));

        $sql = "SELECT R.NOM_RECURSO FROM RECURSO R 
		ORDER BY R.NOM_RECURSO ASC";
        $sqluery = $this->conn->query($sql);

        while ($row = $sqluery->fetch()) {
            $this->acl->addResource(new \Zend_Acl_Resource($row['NOM_RECURSO']));
        }
    }

    /**
     * 
     */
    protected function setupPrivileges()
    {
        //permitindo acesso para login
        $this->acl->allow('CONVIDADO', 'auth', array('login', 'logout'));
        //permitindo acesso para ver erros
        $this->acl->allow('CONVIDADO', 'error', array(
            'error',
            'forbidden',
            'mudar-deposito-logado',
            'sem-deposito-logado',
            'sem-permissao-depositos'
        ));

        //permitindo acesso para ver erros
        $this->acl->allow('LOGADO', 'usuario', array(
            'mudar-senha-provisoria',
            'alterar-senha',
        ));

        //permitindo acesso para ver erros
        $this->acl->allow('LOGADO', 'deposito', array(
            'mudar-deposito-logado',
        ));

        // busca Perfis        
        foreach ($this->getRoles() as $role) {
            //monto lista de cod de perfis
            $codPerfil = implode(',', $role['COD_PERFIL']);

            $sql = "
                SELECT DISTINCT PURA.COD_RECURSO_ACAO, '" . $role['PERFIL'] . "' AS NOM_PERFIL_USUARIO, R.NOM_RECURSO, A.NOM_ACAO
                FROM PERFIL_USUARIO_RECURSO_ACAO PURA
                INNER JOIN RECURSO_ACAO RA ON (PURA.COD_RECURSO_ACAO = RA.COD_RECURSO_ACAO)
                INNER JOIN RECURSO R ON (RA.COD_RECURSO = R.COD_RECURSO)
                INNER JOIN ACAO A ON (RA.COD_ACAO = A.COD_ACAO)
                WHERE PURA.COD_PERFIL_USUARIO IN (" . $codPerfil . ")
                ORDER BY R.NOM_RECURSO ASC, A.NOM_ACAO";

            //role permissions
            $permissoes = $this->conn->query($sql);

            //atribuo permissoes para o perfil
            while ($permissao = $permissoes->fetch()) {
                $role = $permissao['NOM_PERFIL_USUARIO'];
                $resource = $permissao['NOM_RECURSO'];
                $action = $permissao['NOM_ACAO'];

                $this->acl->allow($role, $resource, $action);
            }
        }
    }

    /**
     *
     * @param int $codPai
     * @return array matriz of items 
     */
    protected function setupNavigation($codPai = 0)
    {
        $menu = array();

        $sql = "
            SELECT MI.COD_PAI, MI.DSC_TARGET, MI.COD_MENU_ITEM AS ID, MI.DSC_MENU_ITEM AS LABEL, MI.COD_RECURSO_ACAO,
                MI.DSC_URL, MI.NUM_PESO, R.NOM_RECURSO AS CONTROLLER, RA.COD_RECURSO, RA.COD_ACAO, 
                A.NOM_ACAO AS ACTION, '' AS PRIVIGELE, 'web' AS MODULE,
		(
		    SELECT COUNT(*) 
		    FROM MENU_ITEM MI2
		    WHERE MI2.COD_PAI = MI.COD_MENU_ITEM
		) AS CHILDREN
            FROM MENU_ITEM MI
            LEFT JOIN RECURSO_ACAO RA ON (RA.COD_RECURSO_ACAO = MI.COD_RECURSO_ACAO)
            LEFT JOIN RECURSO R ON (R.COD_RECURSO = RA.COD_RECURSO)
            LEFT JOIN ACAO A ON (A.COD_ACAO = RA.COD_ACAO)
            WHERE MI.COD_PAI = " . (int) $codPai . " AND MI.SHOW = 'S'";



        $items = $this->conn->fetchAll($sql);

        $Acl = new \Wms\Configuration\Acl();

        foreach ($items as $key => $item) {

            if ($Acl->checkModuleExists($item['CONTROLLER'])) {
                $item['MODULE']     = $Acl->getModule();
                $item['CONTROLLER'] = $Acl->getController();
            }

            // case first level on the menu
            if ($item['COD_PAI'] == 0) {
                // add item - permission
                $menu[$key] = array(
                    'module' => $item['MODULE'],
                    'label' => $item['LABEL'],
                    'uri' => $item['DSC_URL'],
                    'target' => $item['DSC_TARGET'],
                    'order' => $item['NUM_PESO'],
                );
                // only links
            } elseif ($item['COD_RECURSO_ACAO'] == 0) {
                // add item - permission
                $menu[$key] = array(
                    'label' => $item['LABEL'],
                    'uri' => $item['DSC_URL'],
                    'target' => $item['DSC_TARGET'],
                    'order' => $item['NUM_PESO'],
                );
                // links with controllers/actions
            } else {
                // add item - permission
                $menu[$key] = array(
                    'module' => $item['MODULE'],
                    'label' => $item['LABEL'],
                    'controller' => $item['CONTROLLER'],
                    'action' => $item['ACTION'],
                    'target' => $item['DSC_TARGET'],
                    'order' => $item['NUM_PESO'],
                        #'resource' => $item['resource'],
                        #'privilege' => $item['privilege'],
                );

                // search extra actions
                $sql = "
		    SELECT RA.DSC_RECURSO_ACAO AS LABEL, A.NOM_ACAO AS ACTION
		    FROM RECURSO_ACAO RA
                    INNER JOIN ACAO A ON (A.COD_ACAO = RA.COD_ACAO)
		    WHERE RA.COD_RECURSO = " . (int) $item['COD_RECURSO'] . "
			AND RA.COD_ACAO != " . (int) $item['COD_ACAO'];

                $actions = $this->conn->fetchAll($sql);

                if (count($actions) > 0) {
                    foreach ($actions as $key2 => $action) {
                        $menu[$key]['pages'][$key2] = array(
                            'module' => $item['MODULE'],
                            'controller' => $item['CONTROLLER'],
                            'label' => $action['LABEL'],
                            'action' => $action['ACTION'],
                            'visible' => 0,
                        );
                    }
                }
            }

            // has CHILDREN
            if ($item['CHILDREN'] > 0)
                $menu[$key]['pages'] = $this->setupNavigation($item['ID']);
        }

        return $menu;
    }

    /**
     * 
     */
    protected function saveAcl()
    {
        $registry = \Zend_Registry::getInstance();
        $registry->set('acl', $this->acl);
    }

}