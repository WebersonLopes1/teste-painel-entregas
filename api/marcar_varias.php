<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

$dados = json_decode(file_get_contents('php://input'), true);
$ids = $dados['ids'] ?? null;

if (!is_array($ids) || empty($ids) || count($ids) > 1000) {
    responderJson(['erro' => 'Selecione de 1 a 1000 entregas.'], 400);
}

$idsValidados = [];

foreach ($ids as $id) {
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);

    if ($idValidado === false || $idValidado <= 0) {
        responderJson(['erro' => 'ID de entrega inválido.'], 400);
    }

    $idsValidados[] = $idValidado;
}

$idsValidados = array_values(array_unique($idsValidados));

try {
    $repositorio = new EntregaRepository(Database::conexao());
    $quantidade = $repositorio->marcarVariasComoEntregues($idsValidados);

    responderJson([
        'mensagem' => "$quantidade entrega(s) marcada(s) como concluída(s).",
        'quantidade' => $quantidade
    ]);

} catch (Throwable $e) {
    responderErro($e);
}
