<?php
require_once 'conexao.php';

$file = $_GET['file'] ?? null;

if (!$file) {
    header('HTTP/1.0 400 Bad Request');
    die('Nome do arquivo não especificado');
}

// Verificar se o arquivo existe no banco de dados
$stmt = $pdo->prepare("SELECT * FROM (
    SELECT caminho_arquivo, 'documento' as tipo FROM documentos_processos WHERE caminho_arquivo = ?
    UNION SELECT caminho_arquivo, 'averbação' as tipo FROM averbacoes_processos WHERE caminho_arquivo = ?
    UNION SELECT caminho_arquivo, 'rgi' as tipo FROM rgi_processos WHERE caminho_arquivo = ?
    UNION SELECT caminho_imagem as caminho_arquivo, 'foto' as tipo FROM fotos_processos WHERE caminho_imagem = ?
    UNION SELECT caminho_imagem as caminho_arquivo, 'localização' as tipo FROM localizacao_processos WHERE caminho_imagem = ?
) as arquivos LIMIT 1");

$stmt->execute([$file, $file, $file, $file, $file]);
$arquivo = $stmt->fetch();

if (!$arquivo) {
    header('HTTP/1.0 404 Not Found');
    die('Arquivo não encontrado');
}

$filePath = UPLOAD_DIR . $file;

if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    die('Arquivo não encontrado no servidor');
}

// Determinar o tipo de conteúdo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Configurar cabeçalhos para download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Enviar o arquivo
readfile($filePath);
exit;
?>