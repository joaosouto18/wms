/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Luis Fernando
 * Created: 21/09/2017
 */

INSERT INTO SIGLA (COD_SIGLA, COD_TIPO_SIGLA, DSC_SIGLA) Values (618, 79, 'INTEGRACAO_PEDIDO_VENDA');

INSERT INTO ACAO_INTEGRACAO (COD_ACAO_INTEGRACAO, COD_CONEXAO_INTEGRACAO, DSC_QUERY, COD_TIPO_ACAO_INTEGRACAO,IND_UTILIZA_LOG,DTH_ULTIMA_EXECUCAO, IND_EXECUCAO)
VALUES (25,1,
        'SELECT NFS_ITEM.CODPRO, '||'''UNICA'''||' AS GRADE,
   SUM(NFS_ITEM.QTDFAT) AS QTDFAT,
   MAXD.DTH
  FROM E140NFV NFS  
  LEFT JOIN E140IPV NFS_ITEM 
    ON NFS_ITEM.CODEMP = NFS.CODEMP
   AND NFS_ITEM.CODFIL = NFS.CODFIL
   AND NFS_ITEM.CODSNF = NFS.CODSNF
   AND NFS_ITEM.NUMNFV = NFS.NUMNFV 
  LEFT JOIN E001TNS TNS 
    ON TNS.CODEMP = NFS_ITEM.CODEMP
   AND TNS.CODTNS = NFS_ITEM.TNSPRO  
 INNER JOIN (SELECT MAX(CONVERT(DATETIME,CONVERT(VARCHAR(10),NF.DATEMI,120) + '||''' '''||' + (CAST (NF.HOREMI / 60 AS VARCHAR) + '||''':'''||' + CAST (NF.HOREMI - ((NF.HOREMI / 60) * 60) AS VARCHAR)))) AS DTH,
                    NF.CODEMP, 
                    NF.CODFIL 
               FROM E140NFV NF 
              GROUP BY  NF.CODEMP, NF.CODFIL ) MAXD
     ON MAXD.CODEMP = NFS.CODEMP
    AND MAXD.CODFIL = NFS.CODFIL
 WHERE NFS.NOPPRO NOT IN ('||'''5929'''||','||'''6929'''||','||'''1202'''||','||'''2202'''||') AND 
       TNS.VENFAT = '||'''S'''||' AND             
       NFS_ITEM.VLRFIN > 0 AND               
       NFS.SITNFV = '||'''2'''||' AND  
       NFS.CODEMP = 1   AND 
       NFS.CODFIL = 2   AND
       (CONVERT(DATETIME,CONVERT(VARCHAR(10),NFS.DATEMI,120) + '||''' '''||' + (CAST (NFS.HOREMI / 60 AS VARCHAR) + '||''':'''||' + CAST (NFS.HOREMI - ((NFS.HOREMI / 60) * 60) AS VARCHAR))) > CONVERT(DATETIME,'||''':?1'''||'))
GROUP BY NFS_ITEM.CODPRO,    MAXD.DTH
ORDER BY 2',618,'S',SYSDATE,'N');

INSERT INTO CONEXAO_INTEGRACAO (COD_CONEXAO_INTEGRACAO,DSC_CONEXAO_INTEGRACAO,SERVIDOR,PORTA,USUARIO,SENHA,DBNAME,PROVEDOR)
  VALUES (1,'INTEGRACAO PEDIDO VENDA','192.168.1.34','1433','wms','wms','sapiens','MSSQL');
