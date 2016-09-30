GRANT ALL ON *.* TO 'dba'@'%' IDENTIFIED BY 'qwerty' WITH GRANT OPTION;
GRANT ALL ON *.* TO 'dba'@'localhost' IDENTIFIED BY 'qwerty' WITH GRANT OPTION;
GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%' IDENTIFIED BY 'slavepass';
GRANT PROCESS, SUPER ON *.* TO 'raingauge'@'localhost' IDENTIFIED BY 'SuperSecurePass'
