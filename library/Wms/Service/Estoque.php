<?php

namespace Wms\Service;


class Estoque
{
    protected $_em;
    protected $_produto;
    protected $_endereco;
    protected $_qtd;
    protected $_volume;
    protected $_embalagem;
    protected $_tipo;
    protected $_observacoes;
    protected $_unitizador;
    protected $_os;
    protected $_uma;
    protected $_usuario;
    protected $_estoqueRepo;
    protected $_contagemEndEn;
    protected $_validade;

    public function __construct($em, $params)
    {
        $this->_em = $em;
        \Zend\Stdlib\Configurator::configure($this, $params);
    }

    /**
     * @return mixed
     */
    public function getContagemEndEn()
    {
        return $this->_contagemEndEn;
    }

    /**
     * @param mixed $contagemEndEn
     */
    public function setContagemEndEn($contagemEndEn)
    {
        $this->_contagemEndEn = $contagemEndEn;
    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->_em;
    }

    /**
     * @param EntityManager $em
     */
    public function setEm($em)
    {
        $this->_em = $em;
    }

    /**
     * @return mixed
     */
    public function getEmbalagem()
    {
        return $this->_embalagem;
    }

    /**
     * @param mixed $embalagem
     */
    public function setEmbalagem($embalagem)
    {
        if ($embalagem == "0") {
            $contagemEndEn = $this->getContagemEndEn();
            if ($contagemEndEn == null) {
                $embalagensEn = $this->_em->getRepository("wms:Produto\Embalagem")->findBy(
                    array('codProduto' => $this->getProduto()->getId(), 'grade' => $this->getProduto()->getGrade()), array('quantidade' => 'ASC')
                );
                $this->_embalagem = $embalagensEn[0];
                return true;
            }
            $embalagensEn = $this->_em->getRepository("wms:Produto\Embalagem")->findBy(
                array('codProduto' => $contagemEndEn->getCodProduto(), 'grade' => $contagemEndEn->getGrade()), array('quantidade' => 'ASC')
            );
            $this->_embalagem = $embalagensEn[0];
        } else {
            $this->_embalagem = $embalagem;
        }
    }

    /**
     * @return mixed
     */
    public function getEstoqueRepo()
    {
        return $this->_estoqueRepo;
    }

    /**
     * @param mixed $estoqueRepo
     */
    public function setEstoqueRepo($estoqueRepo)
    {
        $this->_estoqueRepo = $estoqueRepo;
    }

    /**
     * @return mixed
     */
    public function getObservacoes()
    {
        return $this->_observacoes;
    }

    /**
     * @param mixed $observacoes
     */
    public function setObservacoes($observacoes)
    {
        $this->_observacoes = $observacoes;
    }

    /**
     * @return mixed
     */
    public function getOs()
    {
        return $this->_os;
    }

    /**
     * @param mixed $os
     */
    public function setOs($os)
    {
        $this->_os = $os;
    }

    /**
     * @return mixed
     */
    public function getQtd()
    {
        return $this->_qtd;
    }

    /**
     * @param mixed $qtd
     */
    public function setQtd($qtd)
    {
        $this->_qtd = $qtd;
    }

    /**
     * @return mixed
     */
    public function getTipo()
    {
        return $this->_tipo;
    }

    /**
     * @param mixed $tipo
     */
    public function setTipo($tipo)
    {
        $this->_tipo = $tipo;
    }

    /**
     * @return mixed
     */
    public function getUma()
    {
        return $this->_uma;
    }

    /**
     * @param mixed $uma
     */
    public function setUma($uma)
    {
        $this->_uma = $uma;
    }

    /**
     * @return mixed
     */
    public function getUnitizador()
    {
        return $this->_unitizador;
    }

    /**
     * @param mixed $unitizador
     */
    public function setUnitizador($unitizador)
    {
        $this->_unitizador = $unitizador;
    }

    /**
     * @return mixed
     */
    public function getUsuario()
    {
        return $this->_usuario;
    }

    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario)
    {
        $this->_usuario = $usuario;
    }

    /**
     * @return mixed
     */
    public function getVolume()
    {
        return $this->_volume;
    }

    /**
     * @param mixed $volume
     */
    public function setVolume($volume)
    {
        $this->_volume = $volume;
    }

    /**
     * @return mixed
     */
    public function getEndereco()
    {
        return $this->_endereco;
    }

    /**
     * @param mixed $endereco
     */
    public function setEndereco($endereco)
    {
        $this->_endereco = $endereco;
    }

    /**
     * @return mixed
     */
    public function getProduto()
    {
        return $this->_produto;
    }

    /**
     * @param mixed $produto
     */
    public function setProduto($produto)
    {
        $this->_produto = $produto;
    }

    /**
     * @return mixed
     */
    public function getValidade()
    {
        return $this->_validade;
    }

    /**
     * @param mixed $validade
     */
    public function setValidade($validade)
    {
        $this->_validade = $validade;
    }

    public function movimentaEstoque()
    {
        /** @var  $estoqueRepo */
        $estoqueRepo    = $this->getEstoqueRepo();
        $array = array(
            'produto' =>  $this->getProduto(),
            'endereco' =>  $this->getEndereco(),
            'qtd'   => $this->getQtd(),
            'volume'   => $this->getVolume(),
            'embalagem'   => $this->getEmbalagem(),
            'tipo' => $this->getTipo(),
            'observacoes' => $this->getObservacoes(),
            'os' => $this->getOs(),
            'usuario' => $this->getUsuario(),
            'estoqueRepo' => $this->getEstoqueRepo(),
            'validade' => $this->getValidade()
        );
        if (is_null($array['produto'])) {
            return false;
        }
        return $estoqueRepo->movimentaEstoque($array);
    }

}