<?php

namespace Wms\Domain\Entity;

use  Wms\Domain\Entity\Usuario as UsuarioEntity;

class UsuarioRepository extends AtorRepository {


    public function getIdValueByPerfil($idPerfil)
    {
        $SQL = "SELECT DISTINCT P.COD_PESSOA, P.NOM_PESSOA FROM USUARIO U
                LEFT JOIN USUARIO_PERFIL_USUARIO PU ON U.COD_USUARIO = PU.COD_USUARIO
                LEFT JOIN PESSOA P ON P.COD_PESSOA = U.COD_USUARIO
                WHERE PU.COD_PERFIL_USUARIO = $idPerfil";
        $result = $this->getEntityManager()->getConnection()->query($SQL)-> fetchAll(\PDO::FETCH_ASSOC);

        $usuarios = array();
        foreach ($result as $usuario) {
            $usuarios[$usuario['COD_PESSOA']] = $usuario['NOM_PESSOA'];
        }

        return $usuarios;
    }

    public function checkLoginExiste($login)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT COUNT(u) login FROM wms:Usuario u WHERE u.login = :login');

        $query->setParameter('login', $login);
        $usuario = $query->getOneOrNullResult();
        return ((string) $usuario["login"] == 0) ? false : true;
    }

    /**
     * Persiste dados usuario no sistema
     * 
     * @param Usuario $usuario 
     * @param array $values valores vindo de um formulário
     */
    public function save(UsuarioEntity $usuario, array $values)
    {
        extract($values['acesso']);

        $em = $this->getEntityManager();
        
        $this->persistirAtor($usuario, $values);

        if ($usuario->getId() == null || $usuario->getLogin() != $login)
            if ($this->checkLoginExiste($login))
                throw new \Exception('Login ' . $login . ' já cadastrado.');

        // usuário novo, seta senha provisória
        if ($usuario->getSenha() == null) {
            $usuario->setSenha($login);
            $usuario->setIsSenhaProvisoria('S');
        }

        if (!isset($depositos) || count($depositos) == 0)
            throw new \Exception('Escolha pelo menos um depósito para este usuário.');

        //removo depositos existentes e crio novos
        $usuario->getDepositos()->clear();

        foreach ($values['acesso']['depositos'] as $key => $idDeposito) {
            $deposito = $em->getReference('wms:Deposito', $idDeposito);
            $usuario->addDeposito($deposito);
        }

        if (!isset($perfis) || count($perfis) == 0)
            throw new \Exception('Escolha pelo menos um perfil para este usuário.');

        //removo perfis existentes e crio novos
        $usuario->getPerfis()->clear();

        foreach ($values['acesso']['perfis'] as $key => $idPerfil) {
            $perfil = $em->getReference('wms:Acesso\Perfil', $idPerfil);
            $usuario->addPerfil($perfil);
        }

        //apago o cache da acl
        $cache = new \Core\Cache();
        $cache->delete('acl');

        $usuario->setLogin($login);
        $usuario->setIsAtivo($isAtivo);
        $em->persist($usuario);
    }

    /**
     * Remove o registro no banco através do seu id
     * 
     * @param integer $id 
     */
    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Usuario', $id);
        $numErros = 0;

        $dqlAndamento = $em->createQueryBuilder()
                ->select('count(a) qtty')
                ->from('wms:Recebimento\Andamento', 'a')
                ->where('a.usuario = ?1')
                ->setParameter(1, $id);
        $resultSetAndamento = $dqlAndamento->getQuery()->execute();
        $countAndamento = (integer) $resultSetAndamento[0]['qtty'];
        if ($countAndamento > 0) {
            $msg .= "{$countAndamento} andamento(s) ";
            $numErros++;
        }

        $dqlOS = $em->createQueryBuilder()
                ->select('count(os) qtty')
                ->from('wms:OrdemServico', 'os')
                ->where('os.pessoa = ?1')
                ->setParameter(1, $id);
        $resultSetOS = $dqlOS->getQuery()->execute();
        $countOS = (integer) $resultSetOS[0]['qtty'];
        if ($countOS > 0) {
            $msg .= "{$countOS} orden(s) de serviço ";
            $numErros++;
        }

        if ($numErros > 0) {
            throw new \Exception("Não é possível remover o Usuário {$proxy->getPessoa()->getNome()}, 
				   há {$msg} vinculado(s). Só é possível Inativar.");
        }

        // remove
        $em->remove($proxy);
    }

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
        $usuarios = array();

        foreach ($this->findAll() as $usuario)
            $usuarios[$usuario->getId()] = $usuario->getPessoa()->getNome();

        return $usuarios;
    }

    /**
     *
     * @param UsuarioEntity $usuario 
     */
    public function desativar(UsuarioEntity $usuario)
    {
        $em = $this->getEntityManager();

        $usuario->setIsAtivo(false);

        $em->persist($usuario);
    }


    public function getUsuarioByPerfil($perfil, $idPerfil = 0)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('distinct pf.id, u.login, u.isAtivo, pf.nome, u.isSenhaProvisoria')
            ->from('wms:Usuario', 'u')
            ->innerJoin('u.pessoa', 'pf')
            ->innerJoin('u.depositos', 'd')
            ->innerJoin('u.perfis', 'p')
            ->orderBy('pf.nome')
            ->andWhere("p.nome = '$perfil' OR p.id = '$idPerfil'");

        return $source->getQuery()->getResult();
    }

    public function selectUsuario($perfil)
    {
        $result = $this->getUsuarioByPerfil($perfil);

        $usuarios = array();
        foreach ($result as $usuario) {
            $usuarios[$usuario['id']] = $usuario['nome'];
        }

        return $usuarios;
    }

    public function getIdPerfil($perfil)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('distinct p.id')
            ->from('wms:Usuario', 'u')
            ->innerJoin('u.perfis', 'p')
            ->andWhere("p.nome = '$perfil'");

        $result =  $source->getQuery()->getSingleResult();
        return $result['id'];
    }

}
