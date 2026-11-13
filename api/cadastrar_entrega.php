
<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!is_array($dados)) {
    responderJson(['erro' => 'Dados inválidos.'], 400);
}

$empresaId = filter_var($dados['empresa_id'] ?? null, FILTER_VALIDATE_INT);
$obrigacaoId = filter_var($dados['obrigacao_id'] ?? null, FILTER_VALIDATE_INT);
$competencia = $dados['competencia'] ?? '';
$vencimento = $dados['vencimento'] ?? '';

if (!$empresaId || !$obrigacaoId) {
    responderJson(['erro' => 'Selecione empresa e obrigação válidas.'], 400);
}

if (!is_string($competencia) ||
    !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $competencia)) {
    responderJson(['erro' => 'Competência inválida.'], 400);
}

if (!is_string($vencimento) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) {
    responderJson(['erro' => 'Vencimento inválido.'], 400);
}

[$ano, $mes, $dia] = array_map('intval', explode('-', $vencimento));

if (!checkdate($mes, $dia, $ano)) {
    responderJson(['erro' => 'Informe uma data válida.'], 400);
}

try {
    $repositorio = new EntregaRepository(Database::conexao());

    $repositorio->cadastrar(
        $empresaId,
        $obrigacaoId,
        $competencia,
        $vencimento
    );

    responderJson(['mensagem' => 'Entrega cadastrada com sucesso.'], 201);


} catch (InvalidArgumentException $e) {
    responderJson(['erro' => $e->getMessage()], 400);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        responderJson([
            'erro' => 'Esta entrega já está cadastrada ou possui dados relacionados inválidos.'
        ], 409);
    }

    responderErro($e);

} catch (Throwable $e) {
    responderErro($e);
}
