# SEO Impulse
Compacta CSS e imagens (jpg, png e gif) de uma pasta (e/ou subpastas) e os otimiza para web. (PHP / Command Line)

Para cada imagem otimizada será criado um arquivo de backup (se o mesmo ainda não existir) no mesmo diretório da imagem original.

## Instalação

    composer require szagot/impulse

Ou, adicione a seguinte linha no seu **composer.json**, conforme versão desejada:
 
    "szagot/impulse": "~1.0"

## Uso
No _bash_ digite:

    vendor/bin/impulse pasta/desejada [-r] [--q:80] [--w:500] [--h:500] [--json:caminho/lista_arquivos.json] [--restore]

### Parâmetros Opcionais

 - `[-r]` Fazer a otimização (ou restauração - vide `[--restore]`) recursivamente (incluir subpastas)
 - `[--restore]` Restaura o backup das imagens
 - `[--q:[0-9]+]` Altera a qualidade padrão (80%) para a desejada
 - `[--w:[0-9]+]` Altera a largura para o máximo desejado
 - `[--h:[0-9]+]` Altera a altura para o máximo desejado
 - `[--json:caminho/arquivo.json]` Arquivo JSON com uma lista de arquivos a serem otimizados
 
**Obs**: Quando quiser otimizar todos as imagens, mas especificar o css, use "*.img" no arquivo JSON

### Exemplos
    
    $ vendor/bin/impulse ./themes -r
    Otimiza todas as imagens da pasta "themes" e de suas subpastas
    
    $ vendor/bin/impulse ./themes --restore
    Restaura os backups todas as imagens da pasta "themes"
    
    $ vendor/bin/impulse ./themes -r --json:./arquivos.json
    Otimiza as imagens da pasta "themes" e de suas subpastas cujos nomes estiverem listados em "arquivos.json"
    
    $ vendor/bin/impulse ./themes --w:100 --q:50
    Otimiza todas as imagens da pasta "themes" redimensionando para no máximo 100px de largura, com qualidade de 50%

_Desenvolvido e Utilizado pela plataforma de e-Commerce TMWxD_
http://wiki.tmw.com.br/