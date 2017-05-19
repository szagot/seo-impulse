<?php
/**
 * Serviço de otimização de imagens e compactação de CSS em massa
 *
 *      Uso: php vendor/bin/impulse.php pasta/desejada [-r]
 *      Obs: o modificador "-r" é opcional. Quando usado, SEO Impulse irá buscar imagens nas subpastas também.
 *
 * @author    Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2017, TMW E-commerce Solutions
 */

$msgDefault = PHP_EOL .
    'Uso correto do comando.  Utilize assim:' . PHP_EOL . PHP_EOL .
    '    php optimize_images.php caminho/desejado [-r]' . PHP_EOL . PHP_EOL .
    'Obs: o modificador "-r" é opcional. Quando usado, o otimizador irá buscar imagens nas subpastas também.' .
    PHP_EOL;

// Verifica se foram passados parâmetros
if ($argc == 1) {
    die($msgDefault);
}

// É recursivo?
$isRecursive = in_array('-r', $argv);

// Verifica se a pasta existe
$path = $argv[ 1 ];
if (! is_dir($path)) {
    die(str_replace('Uso correto do comando', 'Caminho inválido', $msgDefault));
}

require_once __DIR__ . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR .
    'vendor' . DIRECTORY_SEPARATOR .
    'autoload.php';

echo PHP_EOL . 'Convertendo arquivos...' . PHP_EOL . PHP_EOL;

// Iniciando leitura do diretório
optimizeImages(new \DirectoryIterator($path), $isRecursive);

echo PHP_EOL . PHP_EOL . 'Finalizado.' . PHP_EOL . PHP_EOL;


/**
 * Lê o diretório e otimiza as imagens nele
 *
 * @param DirectoryIterator $dir
 * @param Bool              $isRecursive
 */
function optimizeImages(\DirectoryIterator $dir, $isRecursive = false)
{
    // Tipos aceitos
    $imgs = ['png', 'jpg', 'jpeg', 'gif'];

    /** @var \DirectoryIterator $fileInfo Lê cada arquivo/pasta */
    foreach ($dir as $fileInfo) {
        $pathFile = $fileInfo->getPath();
        $fileName = $fileInfo->getFilename();
        $pathFileName = $pathFile . '/' . $fileName;
        // Se for diretório e não for '.' ou '..'
        if ($fileInfo->isDir() && ! $fileInfo->isDot()) {
            // Esrá no modo recursivo?
            if ($isRecursive) {
                // Le as imagens da pasta seguinte
                optimizeImages(new \DirectoryIterator($pathFileName), $isRecursive);
            }
            continue;
        }

        // Verifica se a extensão é de uma imagem
        $ext = strtolower($fileInfo->getExtension());
        if (in_array($ext, $imgs)) {

            echo ' • [IMG] - ' . $pathFileName . PHP_EOL;

            // Pega tamanho antes da alteração
            $sizeBefore = round($fileInfo->getSize() / 1024, 2);

            // Otimizando imagens
            $img = (new \Intervention\Image\ImageManager([
                'driver' => 'gd',
            ]))->make($pathFileName);
            $img->save($pathFileName, 80);

            // Pega tamanho depois da alteração
            clearstatcache();
            $sizeAfter = round(filesize($pathFileName) / 1024, 2);

            echo "           {$sizeBefore}kb => {$sizeAfter}kb" . PHP_EOL;

        } elseif ($ext == 'css') {

            // Compacta CSS
            echo ' ♦ [CSS] - ' . $pathFileName . PHP_EOL;

            // Lê o arquivo
            $cod = file_get_contents($pathFileName);

            // Está vazio?
            if (! empty($cod)) {
                // Remove espaços extras, tabulações e quebras de linha
                $cod = preg_replace('/[\r\n\t\s]+/', ' ', $cod);
                // Remove comentários
                $cod = preg_replace('/\/\*(.*)\*\//Uis', '', $cod);
                // Remove espaços do início e do fim
                $cod = preg_replace('/(^\s+|\s+$)/', '', $cod);

                // Grava
                file_put_contents($pathFileName, $cod);

                echo '           Compactado!' . PHP_EOL;

            } else {
                // Erro
                echo '           Não foi possível compactar...' . PHP_EOL;
            }
        }
    }
}
