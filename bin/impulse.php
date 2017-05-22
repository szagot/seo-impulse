<?php
/**
 * Serviço de otimização de imagens e compactação de CSS em massa
 *
 *      Uso: vendor/bin/impulse pasta/desejada [-r]
 *      Obs: o modificador "-r" é opcional. Quando usado, SEO Impulse irá buscar imagens nas subpastas também.
 *
 * @author    Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2017, TMW E-commerce Solutions
 */

$msgDefault = PHP_EOL .
    'Uso incorreto do comando.  Utilize assim:' . PHP_EOL . PHP_EOL .
    '    vendor/bin/impulse pasta/desejada [-r] [--q:80] [--w:100] [--h:100] [--json:caminho/lista_arquivos.json]' . PHP_EOL . PHP_EOL .
    'Obs: O modificador "-r" é opcional. Quando usado, o otimizador irá buscar imagens nas subpastas também.' . PHP_EOL .
    '     Igualmente, o modificador "--json" é opcional. Quando usado, ' .
    'o otimizador irá otimizar apenas os arquivos informados.' . PHP_EOL .
    '     Já o modificador "--w" e "--h", também opcionais, indicam respectivamente a largura e altura máxima ' .
    'quando o arquivo for uma imagem. Estes devem conter apenas números.' . PHP_EOL .
    '     E o modificador "--q" define a qualidade, onde 1 é muito baixa e 100 é qualidade máxima (padrão = 80).' . PHP_EOL;

// Verifica se foram passados parâmetros
if ($argc == 1) {
    die($msgDefault);
}

// Solicitada ajuda?
if (in_array('--help', $argv)) {
    die(str_replace('Uso incorreto do comando. ', '', $msgDefault));
}

// É recursivo?
$isRecursive = in_array('-r', $argv);

// Definida lista
$isJson = false;
$jsonList = [];
$quality = 80;
$width = null;
$height = null;
foreach ($argv as $arg) {
    // Localizada solicitação JSON
    if (preg_match('/^--json:(.+)$/', $arg, $matches)) {
        $pathJson = $matches[1];
        if (!file_exists($pathJson)) {
            die(str_replace(
                'Uso incorreto do comando', 'Lista de imagens/css não encontrada em ' . $pathJson,
                $msgDefault
            ));
        }
        $jsonList = json_decode(@file_get_contents($pathJson) ?? '[]');
        if (!isset($jsonList[0]) || empty($jsonList[0])) {
            die(str_replace('Uso incorreto do comando', 'Lista de imagens/css está vazia', $msgDefault));
        }
    }
    // Qualidade da imagem
    if (preg_match('/^--q:([0-9]+)$/', $arg, $matches)) {
        $quality = $matches[1];
    }
    // Largura limite
    if (preg_match('/^--w:([0-9]+)$/', $arg, $matches)) {
        $width = $matches[1];
    }
    // Altura limite
    if (preg_match('/^--h:([0-9]+)$/', $arg, $matches)) {
        $height = $matches[1];
    }
}

// Verifica se a pasta existe
$path = $argv[1];
if (!is_dir($path)) {
    die(str_replace('Uso incorreto do comando', 'Caminho inválido', $msgDefault));
}

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
    }
}

echo PHP_EOL . 'Convertendo arquivos...' . PHP_EOL . PHP_EOL;

// Iniciando leitura do diretório
optimizeImages(new \DirectoryIterator($path), $isRecursive, $quality, $width, $height, $jsonList);

echo PHP_EOL . PHP_EOL . 'Finalizado.' . PHP_EOL . PHP_EOL;


/**
 * Lê o diretório e otimiza as imagens nele
 *
 * @param DirectoryIterator $dir
 * @param Bool              $isRecursive
 * @param int               $quality
 * @param int               $width
 * @param int               $height
 * @param array             $jsonList
 */
function optimizeImages(
    \DirectoryIterator $dir,
    $isRecursive = false,
    $quality = 80,
    $width = null,
    $height = null,
    $jsonList = []
) {
    // Tipos aceitos
    $imgs = ['png', 'jpg', 'jpeg', 'gif'];

    /** @var \DirectoryIterator $fileInfo Lê cada arquivo/pasta */
    foreach ($dir as $fileInfo) {
        $pathFile = $fileInfo->getPath();
        $fileName = $fileInfo->getFilename();
        $pathFileName = $pathFile . '/' . $fileName;
        // Se for diretório e não for '.' ou '..'
        if ($fileInfo->isDir() && !$fileInfo->isDot()) {
            // Esrá no modo recursivo?
            if ($isRecursive) {
                // Le as imagens da pasta seguinte
                optimizeImages(new \DirectoryIterator($pathFileName), $isRecursive, $quality, $width, $height,
                    $jsonList);
            }
            continue;
        }

        // Verifica se a extensão é de uma imagem
        $ext = strtolower($fileInfo->getExtension());
        if (in_array($ext, $imgs)) {

            // Verifica se tem uma lista definida
            if (!empty($jsonList)) {
                // Verifica se o nome do arquivo está na lista. Se não estiver, pula
                // Para aceitar todas as imagens, coloque no arquivo '*.img'
                if (!in_array($fileInfo->getFilename(), $jsonList) && ! in_array('*.img', $jsonList)) {
                    continue;
                }
            }

            echo ' • [IMG] - ' . $pathFileName . PHP_EOL;

            // Pega tamanho antes da alteração
            $sizeBefore = round($fileInfo->getSize() / 1024, 2);

            // Otimizando imagens
            $img = (new \Intervention\Image\ImageManager([
                'driver' => 'gd',
            ]))->make($pathFileName);

            // Verifica se tem redimensionamento
            if (is_numeric($width) || is_numeric($height)) {
                $img->resize($width, $height, function (\Intervention\Image\Constraint $constraint) {
                    // Mantém a proporção
                    $constraint->aspectRatio();
                    // Impede que a saída seja maior que a imagem original
                    $constraint->upsize();
                });
            }

            // Salva a imagem
            $img->save($pathFileName, $quality);

            // Pega tamanho depois da alteração
            clearstatcache();
            $sizeAfter = round(filesize($pathFileName) / 1024, 2);

            echo "           {$sizeBefore}kb => {$sizeAfter}kb" . PHP_EOL;

        } elseif ($ext == 'css') {

            // Verifica se tem uma lista definida
            if (!empty($jsonList)) {
                // Verifica se o nome do arquivo está na lista. Se não estiver, pula
                // Para aceitar todas as imagens, coloque no arquivo '*.css'
                if (!in_array($fileInfo->getFilename(), $jsonList) && ! in_array('*.css', $jsonList)) {
                    continue;
                }
            }

            // Compacta CSS
            echo ' ♦ [CSS] - ' . $pathFileName . PHP_EOL;

            // Lê o arquivo
            $cod = file_get_contents($pathFileName);

            // Está vazio?
            if (!empty($cod)) {
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
