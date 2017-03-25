<?php

// Incluindo o autoload do Composer para carregar a biblioteca
// do Dropbox
require_once 'vendor/autoload.php';

// Incluindo a classe que criamos
require_once 'class/BackupDropbox.php';

// Como o processo de upload pode ser demorado, retiramos
// o limite de excecução do script
set_time_limit(0);

// Dados do aplicativo no Dropbox
$token = "kWj4ECFc8RFAMlaCNLL3Z6Cmh0yAFPYNXYzmJ8q5a9ZRXn8pBq8Pfg1InYxBts08";
$app = "rafaelcouto-backup";

// Instanciando objeto e copiando arquivos e sub-pastas da pasta 'documentos'
$backup = new BackupDropbox($token, $app);
$backup->uploadFolder('documentos', '/documentos');