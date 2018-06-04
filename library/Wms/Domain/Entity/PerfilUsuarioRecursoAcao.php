<?php

namespace Wms\Domain\Entity;

/**
 * PerfilUsuarioRecursoAcao
 */
class PerfilUsuarioRecursoAcao
{

    /**
     * @var smallint $idPerfilUsuario
     */
    private $idPerfilUsuario;

    /**
     * @var integer $idRecursoAcao
     */
    private $idRecursoAcao;

    public function getIdPerfilUsuario()
    {
        return $this->idPerfilUsuario;
    }

    public function setIdPerfilUsuario($idPerfilUsuario)
    {
        $this->idPerfilUsuario = $idPerfilUsuario;
        return $this;
    }

    public function getIdRecursoAcao()
    {
        return $this->idRecursoAcao;
    }

    public function setIdRecursoAcao($idRecursoAcao)
    {
        $this->idRecursoAcao = $idRecursoAcao;
        return $this;
    }

}