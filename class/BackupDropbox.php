<?php

class BackupDropbox
{
    /** @var \Dropbox\Client */
    private $dropbox;

    /**
     * Inicializa a biblioteca do Dropbox a partir do token e
     * do nome do aplicativo
     *
     * @param $token
     * @param $app
     */
    public function __construct($token, $app)
    {
        $this->dropbox = new \Dropbox\Client($token, $app);
    }

    /**
     * Envia um arquivo para o Dropbox
     *
     * @param string $localFile Caminho do arquivo no servidor
     * @param string $remoteFile Caminho do arquivo no Dropbox
     *
     * @return void
     */
    public function uploadFile($localFile, $remoteFile)
    {
        $fp = fopen($localFile, "rb");
        $this->dropbox->uploadFile($remoteFile, \Dropbox\WriteMode::add(), $fp);
        fclose($fp);

        echo "Arquivo '{$localFile}' enviado para '{$remoteFile}'. " . PHP_EOL;
    }

    /**
     * Copia uma pasta do servidor para o Dropbox recursivamente
     *
     * @param string $localFolder Caminho da pasta no servidor
     * @param string $remoteFolder Caminho da pasta no Dropbox
     *
     * @return void
     */
    public function uploadFolder($localFolder, $remoteFolder)
    {
        // Se não for uma pasta válida, sai do método
        if (!is_dir($localFolder)) {
            return;
        }

        // Buscando itens da pasta no servidor
        $files = new \DirectoryIterator($localFolder);

        // Buscando itens da pasta no Dropbox
        $metadata = $this->dropbox->getMetadataWithChildren($remoteFolder);

        // Passando pelos itens no servidor
        foreach ($files as $file) {

            // Se o item for '.' ou '..' passamos para o próximo item
            if ($file->isDot()) {
                continue;
            }

            // Se o item for uma pasta
            if ($file->isDir()) {
                // Chamamos o método novamente passando como parâmetro inicial a pasta atual (recursividade)
                $this->uploadFolder($file->getRealPath(), $remoteFolder . "/$file");
                continue;
            }

            // Se o item for um arquivo
            if ($file->isFile()) {
                // Verificamos se o arquivo já existe no Dropbox
                $remoteFileExists = $this->checkIfRemoteFileExistsFromMetadata($metadata, $file->getFilename());
                // Se não existir
                if (!$remoteFileExists) {
                    // Fazemos upload do arquivo para o Dropbox
                    $remoteFile = $remoteFolder . '/' . $file->getFilename();
                    $this->uploadFile($file->getPathname(), $remoteFile);
                }
            }

        }
    }

    /**
     * Verifica se o arquivo existe no Dropbox a partir do metadata
     *
     * @param array $metadata Dados da pasta do Dropbox
     * @param string $fileName Nome do arquivo no Dropbox
     *
     * @return bool
     */
    private function checkIfRemoteFileExistsFromMetadata($metadata, $fileName)
    {
        // Se não houver dados, o arquivo não existe
        if (empty($metadata)) {
            return false;
        }

        // Passando pelos itens
        foreach ($metadata['contents'] as $remoteFile) {
            // Se for uma pasta passamos para o próximo item
            if ($remoteFile['is_dir']) {
                continue;
            }
            // Se for o arquivo que procuramos
            if (basename($remoteFile['path']) == $fileName) {
                return true;
            }
        }

        return false;
    }

}