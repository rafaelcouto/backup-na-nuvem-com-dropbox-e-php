<?php

class BackupDropbox
{
    /** @var \Spatie\Dropbox\Client */
    private $dropbox;

    /**
     * Inicializa a biblioteca do Dropbox a partir do token
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->dropbox = new \Spatie\Dropbox\Client($token);
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
        $this->dropbox->upload($remoteFile, $fp);

        echo "Arquivo '{$localFile}' enviado para '{$remoteFile}'. " . PHP_EOL;
    }

/**
 * @param string $remoteFolder Caminho da pasta no Dropbox
 * @return array
 */
public function getAllEntries($remoteFolder) {

    // Buscamos as informações da pasta
    try {
        $this->dropbox->getMetadata($remoteFolder);
    } catch (\Spatie\Dropbox\Exceptions\BadRequest $ex) {
        // Se a pasta não existir, criamos ela
        if ($ex->dropboxCode == 'path') {
            $this->dropbox->createFolder($remoteFolder);
        }
    }

    // Buscando todas as entradas da pasta no Dropbox (recursivo)
    $response = $this->dropbox->listFolder($remoteFolder, true);

    return $response['entries'];
}

    /**
     * Copia uma pasta do servidor para o Dropbox recursivamente
     *
     * @param string $localFolder Caminho da pasta no servidor
     * @param string $remoteFolder Caminho da pasta no Dropbox
     * @param array|null $entries Entradas da pasta inicial
     * @return void
     */
    public function uploadFolder($localFolder, $remoteFolder, $entries = null)
    {
        // Se não for uma pasta válida, sai do método
        if (!is_dir($localFolder)) {
            return;
        }

        // Para evitar ficar fazendo uma requisição para cada subpasta, fazemos apenas
        // uma única requisição buscamos todos os arquivos da pasta inicial
        if ($entries === null) {
            $entries = $this->getAllEntries($remoteFolder);
        }

        // Buscando itens da pasta no servidor
        $files = new DirectoryIterator($localFolder);

        // Passando pelos itens no servidor
        foreach ($files as $file) {

            // Se o item for '.' ou '..' passamos para o próximo item
            if ($file->isDot()) {
                continue;
            }

            // Se o item for uma pasta
            if ($file->isDir()) {
                // Chamamos o método novamente passando como parâmetro inicial a pasta atual (recursividade)
                $this->uploadFolder($file->getRealPath(), $remoteFolder . "/$file", $entries);
                continue;
            }

            // Se o item for um arquivo
            if ($file->isFile()) {
                // Verificamos se o arquivo já existe no Dropbox
                $remoteFileExists = $this->checkIfRemoteFileExistsFromEntries($entries, $file->getFilename());
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
 * Verifica se o arquivo existe no Dropbox a partir das entradas
 *
 * @param array $entries Entradas da pasta do Dropbox
 * @param string $fileName Nome do arquivo no Dropbox
 *
 * @return bool
 */
private function checkIfRemoteFileExistsFromEntries($entries, $fileName)
{
    // Passando pelos itens
    foreach ($entries as $remoteFile) {
        // Se não for um arquivo passamos para o próximo item
        if ($remoteFile['.tag'] != 'file') {
            continue;
        }
        // Se for o arquivo que procuramos
        if (basename($remoteFile['path_display']) == $fileName) {
            return true;
        }
    }

    return false;
}

}