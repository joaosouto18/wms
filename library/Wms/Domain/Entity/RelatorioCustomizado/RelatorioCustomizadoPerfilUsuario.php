<?php

namespace Wms\Domain\Entity\RelatorioCustomizado;
/**
 * @Table(name="RELATORIO_CUST_PERFIL_USUARIO")
 * @Entity(repositoryClass="Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoPerfilUsuarioRepository")
 */
class RelatorioCustomizadoPerfilUsuario
{

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizado")
     * @JoinColumn(name="COD_RELATORIO_CUSTOMIZADO", referencedColumnName="COD_RELATORIO_CUSTOMIZADO")
     */
    protected $relatorio;

    /**
     * @Id
     * @ManyToOne(targetEntity="Wms\Domain\Entity\Acesso\Perfil")
     * @JoinColumn(name="COD_PERFIL_USUARIO", referencedColumnName="COD_PERFIL_USUARIO")
     */
    protected $perfilUsuario;

    /**
     * @return mixed
     */
    public function getRelatorio()
    {
        return $this->relatorio;
    }

    /**
     * @param mixed $relatorio
     */
    public function setRelatorio($relatorio)
    {
        $this->relatorio = $relatorio;
    }

    /**
     * @return mixed
     */
    public function getPerfilUsuario()
    {
        return $this->perfilUsuario;
    }

    /**
     * @param mixed $perfilUsuario
     */
    public function setPerfilUsuario($perfilUsuario)
    {
        $this->perfilUsuario = $perfilUsuario;
    }

}
