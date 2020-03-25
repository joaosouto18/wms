<?php

namespace Wms\Util;


 use Wms\Domain\Entity\Usuario;

 class Logger_Exception extends \Exception
{
    private $path;
    private $completePath;
    protected static $_instance;

     /**
      * Logger_Exception constructor.
      * @param $msg
      * @param $code
      * @param $previus
      * @throws \Exception
      */
    public function __construct($msg, $code = 0, \Throwable $previus = null )
    {
        parent::__construct($msg, $code, $previus);

        $config = \Zend_Registry::get('config');
        if (empty($config->logger) || empty($config->logger->fileName) || empty($config->logger->type))
            throw new \Exception('As configurações de LOG não foram completamente definidas, favor entrar em contato com o suporte!');

        $this->path = $config->logger->path;

        self::checkDirectory(true);

        $dateLog = (new \DateTime())->format('d-m-Y') ;
        $this->completePath = $this->path. DIRECTORY_SEPARATOR . $config->logger->fileName . "($dateLog)" . $config->logger->type;

        self::newRegister($msg, parent::getTrace(), \Zend_Auth::getInstance()->getIdentity());
    }


    /**
     * @param bool $cine
     * @throws \Exception
     */
    protected function checkDirectory($cine = false)
    {
        if (!is_dir($this->path)) {
            if ($cine)
                mkdir($this->path, 777);
            else
                throw new \Exception("Diretório de Log não encontrado!");
        }
    }

    /**
     * @param $msg
     * @param $backTrace
     * @param Usuario $user
     * @throws \Exception
     */
    private function newRegister($msg, $backTrace, $user)
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $userId = $user->getId();
        $userName = $user->getPessoa()->getNome();
        $caller = $backTrace[0];
        $newRow = "($now) [User = $userId - $userName] $caller[class] line $caller[line] -> $msg". PHP_EOL;
        if (!empty($caller['args']))
        {
            $newRow .= "Args => " . json_encode($caller['args']) . PHP_EOL . PHP_EOL ;
        }
        $file = fopen($this->completePath, 'a');
        fwrite($file, $newRow);
        fclose($file);
    }
}