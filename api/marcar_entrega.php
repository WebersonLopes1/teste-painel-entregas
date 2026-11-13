
<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

$dados = json_decode(file_get_contents('php://input'), true);
$id = filter_var($dados['id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    responderJson(['erro' => 'ID da entrega inválido.'], 400);
}

try {
    $repositorio = new EntregaRepository(Database::conexao());

    if (!$repositorio->marcarComoEntregue($id)) {
        responderJson([
            'erro' => 'Entrega não encontrada ou já concluída.'
        ], 409);
    }

    responderJson([
        'mensagem' => '1 entrega marcada como concluída.',
        'quantidade' => 1
    ]);
} catch (Throwable $e) {
    responderErro($e);
}
