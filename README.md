Base PHP5 com oci8 com Vagrant
===========================

1)Baixe o cliente oracle:
http://www.oracle.com/technetwork/topics/linuxx86-64soft-092277.html

Versões: 

instantclient-basic-linux.x64-11.2.0.3.0.zip
instantclient-sdk-linux.x64-11.2.0.3.0.zip

2) Coloque na pasta raiz do projeto

3) Baixe o vagrant:
http://downloads.vagrantup.com/tags/v1.2.2

4) Baixe o virtual box:

https://www.virtualbox.org/wiki/Downloads

5) Abra o cmd ou terminal

6) Navegue ate a pasta do projeto 

7) Digite vagrant up

8) Acesse http://localhost:8080/


Usando WAMP
===========
Após instalação do wamp, caso ocorra erro 500
no menu do wamp acesse Apache -> httpd.conf e remova a # do elemento 
"LoadModule rewrite_module modules/mod_rewrite.so"
caso não encontre no arquivo enclua abaixo dos outros "loadModule"

Habilite a extensão php do php_oci8 em PHP -> PHP extensions

Configuração banco local
===========================

DROP USER wms_develop CASCADE;
DROP TABLESPACE wms_develop;

ALTER SYSTEM SET DB_16K_CACHE_SIZE=16M SCOPE=BOTH;

CREATE TABLESPACE wms_develop
LOGGING DATAFILE 'E:\tablespace\wms_develop.dbf' SIZE 10M
AUTOEXTEND ON NEXT 512k MAXSIZE 2000M
BLOCKSIZE 16k
EXTENT MANAGEMENT LOCAL UNIFORM SIZE 512K
SEGMENT SPACE MANAGEMENT AUTO
ONLINE;

CREATE USER wms_develop
IDENTIFIED BY wms_adm
DEFAULT TABLESPACE wms_develop;

GRANT ALL PRIVILEGES TO wms_develop;

-- Desabilita expiração de senha no Oracle --
ALTER PROFILE DEFAULT LIMIT
FAILED_LOGIN_ATTEMPTS UNLIMITED
PASSWORD_LIFE_TIME UNLIMITED;

Exemplo exportação/importação banco
===========================
exp wms_adm/wms_adm@orams-cluster.simonet.com.br/Pwms file=wms_simonetti.dmp owner=wms_adm compress=Y grants=Y indexes=Y triggers=Y constraints=Y

exp wms_develop_linhares/wms_adm@xe file=develop_linhares.dmp owner=wms_develop_linhares compress=Y grants=Y indexes=Y triggers=Y constraints=Y
imp wms_develop/wms_adm@xe file=develop_linhares.dmp full =Y grants=Y indexes=Y constraints=Y

exp wms_adm/wms_adm@10.150.5.248/xe file=F:\dmp.dmp owner=wms_adm compress=Y grants=Y indexes=Y triggers=Y constraints=Y
imp wms_develop/wms_adm@localhost/xe file=D:\dmp.dmp full =Y grants=Y indexes=Y constraints=Y

Documentação
===========================

library\bin\phpdoc.bat -d library\Wms\WebService -t docs\phpDoc



Build nos arquivos JS e CSS
===========================
navegar até a pasta wms/public

js wms/scripts/build.js